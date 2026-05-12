<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\AuditLog;
use App\Models\AppSetting;
use App\Models\Event;
use App\Models\FormField;
use App\Models\Tag;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Event $event)
    {
        $anonymiseApplications = AppSetting::get('anonymise_applications', false);

        $applications = Application::with(['user', 'tags', 'reviewer'])
            ->where('event_id', $event->id)
            ->when(request('status'), fn($q, $status) => $q->where('status', $status))
            ->when(request('search'), function ($q, $search) use ($anonymiseApplications) {
                if ($anonymiseApplications) {
                    $q->when(
                        is_numeric($search),
                        fn($query) => $query->where('id', (int) $search),
                        fn($query) => $query->whereRaw('1 = 0')
                    );

                    return;
                }

                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'ilike', "%{$search}%")
                        ->orWhere('discord_username', 'ilike', "%{$search}%");
                });
            })
            ->orderByDesc('submitted_at')
            ->paginate(25)
            ->withQueryString();

        if ($anonymiseApplications) {
            $applications->getCollection()->transform(fn(Application $application) => $this->anonymiseApplication($application));
        }

        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/Applications/Index', [
            'event' => $event,
            'applications' => $applications,
            'tags' => $tags,
            'filters' => request()->only(['status', 'search']),
            'statusCounts' => $this->getStatusCounts($event),
            'anonymiseApplications' => $anonymiseApplications,
            'canExportFullData' => auth()->user()->is_admin,
        ]);
    }

    public function show(Event $event, Application $application)
    {
        $application->load([
            'user', 'reviewer', 'statusHistory.changedBy', 'tags',
            'responses.field', 'files.field',
            'event.forms.fields',
        ]);

        $anonymiseApplications = AppSetting::get('anonymise_applications', false);

        $priorApplications = Application::where('user_id', $application->user_id)
            ->where('id', '!=', $application->id)
            ->with('event:id,name,slug,starts_at')
            ->orderByDesc('created_at')
            ->get(['id', 'event_id', 'status', 'created_at'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'status' => $a->status,
                'created_at' => $a->created_at,
                'event' => $a->event,
            ]);

        return Inertia::render('Admin/Applications/Show', [
            'event' => $event,
            'application' => $anonymiseApplications ? $this->anonymiseApplication($application, true) : $application,
            'tags' => Tag::orderBy('name')->get(),
            'anonymiseApplications' => $anonymiseApplications,
            'priorApplications' => $anonymiseApplications ? [] : $priorApplications,
        ]);
    }

    public function updateStatus(Request $request, Event $event, Application $application)
    {
        $validated = $request->validate([
            'status' => 'required|in:submitted,under_review,accepted,rejected,waitlisted',
            'note' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $application->status;
        $application->transitionTo($validated['status'], auth()->user(), $validated['note'] ?? null);

        AuditLog::record('application.status_changed', $application, ['status' => $oldStatus], ['status' => $validated['status']]);

        // Notify volunteer
        if ($oldStatus !== $validated['status']) {
            $this->notifications->notifyStatusChanged($application, $oldStatus);
        }

        return back()->with('success', 'Application status updated.');
    }

    public function updateNotes(Request $request, Event $event, Application $application)
    {
        $validated = $request->validate(['admin_notes' => 'nullable|string|max:5000']);
        $old = ['admin_notes' => $application->admin_notes];
        $application->update($validated);

        AuditLog::record('application.notes_updated', $application, $old, $validated);

        return back()->with('success', 'Notes saved.');
    }

    public function syncTags(Request $request, Event $event, Application $application)
    {
        $validated = $request->validate(['tag_ids' => 'array', 'tag_ids.*' => 'integer|exists:tags,id']);
        $old = ['tag_ids' => $application->tags()->pluck('tags.id')->all()];
        $application->tags()->sync($validated['tag_ids'] ?? []);

        AuditLog::record('application.tags_synced', $application, $old, [
            'tag_ids' => $validated['tag_ids'] ?? [],
        ]);

        return back()->with('success', 'Tags updated.');
    }

    public function bulkUpdateStatus(Request $request, Event $event)
    {
        $validated = $request->validate([
            'application_ids' => 'required|array|min:1',
            'application_ids.*' => 'integer|exists:applications,id',
            'status' => 'required|in:submitted,under_review,accepted,rejected,waitlisted',
            'note' => 'nullable|string|max:1000',
        ]);

        $applications = Application::whereIn('id', $validated['application_ids'])
            ->where('event_id', $event->id)
            ->get();

        foreach ($applications as $application) {
            $oldStatus = $application->status;
            $application->transitionTo($validated['status'], auth()->user(), $validated['note'] ?? null);
            if ($oldStatus !== $validated['status']) {
                $this->notifications->notifyStatusChanged($application, $oldStatus);
            }
        }

        AuditLog::record('application.bulk_status_changed', null, [], [
            'count' => $applications->count(),
            'status' => $validated['status'],
        ]);

        return back()->with('success', "{$applications->count()} applications updated.");
    }

    public function export(Event $event)
    {
        return $this->exportApplications($event, AppSetting::get('anonymise_applications', false), 'application.exported', 'applications');
    }

    public function exportFullData(Event $event)
    {
        return $this->exportApplications($event, false, 'application.full_data_exported', 'applications-full-data');
    }

    private function exportApplications(Event $event, bool $anonymise, string $auditAction, string $filenamePrefix)
    {
        $applications = Application::with(['user', 'tags', 'responses.field'])
            ->where('event_id', $event->id)
            ->orderByDesc('submitted_at')
            ->get();

        AuditLog::record($auditAction, $event, [], [
            'count' => $applications->count(),
            'anonymised' => $anonymise,
        ]);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filenamePrefix}-{$event->slug}.csv\"",
        ];

        $callback = function () use ($applications, $event, $anonymise) {
            $handle = fopen('php://output', 'w');

            // Build headers from stage 1 form fields
            $form = $event->stageOneForm;
            $fields = $form?->fields()->whereNotIn('type', ['heading', 'paragraph', 'divider'])->get() ?? collect();

            $csvHeaders = ['ID', 'Name', 'Discord Username', 'Email', 'Status', 'Submitted At', 'Tags'];
            foreach ($fields as $field) {
                $csvHeaders[] = $field->label;
            }

            fputcsv($handle, $csvHeaders);

            foreach ($applications as $application) {
                $row = [
                    $application->id,
                    $anonymise ? $this->anonymousApplicantLabel($application) : $application->user->name,
                    $anonymise ? 'Hidden' : $application->user->discord_username,
                    $anonymise ? 'Hidden' : ($application->user->email ?? ''),
                    $application->status,
                    $application->submitted_at?->format('Y-m-d H:i'),
                    $application->tags->pluck('name')->join(', '),
                ];

                foreach ($fields as $field) {
                    $response = $application->getResponseForField($field->id);
                    $row[] = $this->exportResponseValue($field, $response?->value, $anonymise);
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function anonymiseApplication(Application $application, bool $includeResponses = false): Application
    {
        $originalUserId = $application->user_id;

        $application->setRelation('user', $this->anonymisedUser($application));
        $application->user_id = null;
        $application->admin_notes = filled($application->admin_notes) ? 'Hidden' : null;

        if ($includeResponses && $application->relationLoaded('responses')) {
            $application->setRelation('responses', $application->responses->map(function ($response) {
                if ($this->isPersonalField($response->field)) {
                    $response->value = 'Hidden';
                }

                return $response;
            }));
        }

        if ($includeResponses && $application->relationLoaded('files')) {
            $application->setRelation('files', $application->files->map(function (ApplicationFile $file) {
                if ($this->isPersonalField($file->field)) {
                    $file->disk = null;
                    $file->path = null;
                    $file->original_filename = 'Hidden';
                    $file->mime_type = 'Hidden';
                    $file->size_bytes = 0;
                    $file->setAttribute('size_formatted', 'Hidden');
                }

                return $file;
            }));
        }

        if ($includeResponses && $application->relationLoaded('statusHistory')) {
            $application->setRelation('statusHistory', $application->statusHistory->map(function ($entry) use ($application, $originalUserId) {
                if ($entry->relationLoaded('changedBy') && $entry->changedBy?->id === $originalUserId) {
                    $entry->setRelation('changedBy', $this->anonymisedUser($application));
                }

                if (filled($entry->note)) {
                    $entry->note = 'Hidden';
                }

                return $entry;
            }));
        }

        return $application;
    }

    private function anonymisedUser(Application $application): User
    {
        $user = $application->user->replicate();
        $label = $this->anonymousApplicantLabel($application);

        $user->id = null;
        $user->name = $label;
        $user->email = null;
        $user->discord_username = 'hidden';
        $user->discord_avatar = null;
        $user->first_name = null;
        $user->last_name = null;
        $user->phone = null;
        $user->emergency_contact_name = null;
        $user->emergency_contact_phone = null;
        $user->dietary_requirements = null;
        $user->medical_info = null;
        $user->tshirt_size = null;

        return $user;
    }

    private function anonymousApplicantLabel(Application $application): string
    {
        return 'Applicant #' . $application->id;
    }

    private function exportResponseValue(?FormField $field, mixed $value, bool $anonymise): mixed
    {
        if (! $anonymise || ! $this->isPersonalField($field)) {
            return $value ?? '';
        }

        return filled($value) ? 'Hidden' : '';
    }

    private function isPersonalField(?FormField $field): bool
    {
        if (! $field) {
            return false;
        }

        if (in_array($field->type, ['email', 'phone', 'file', 'image'])) {
            return true;
        }

        return str($field->label)->lower()->contains([
            'name',
            'email',
            'phone',
            'contact',
            'address',
            'postcode',
            'dob',
            'birth',
            'age',
            'medical',
            'dietary',
            'emergency',
            'discord',
            'social',
        ]);
    }

    private function getStatusCounts(Event $event): array
    {
        return Application::where('event_id', $event->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
