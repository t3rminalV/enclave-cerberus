<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Event;
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
        $applications = Application::with(['user', 'tags', 'reviewer'])
            ->where('event_id', $event->id)
            ->when(request('status'), fn($q, $status) => $q->where('status', $status))
            ->when(request('search'), fn($q, $search) => $q->whereHas('user', function ($uq) use ($search) {
                $uq->where('name', 'ilike', "%{$search}%")
                    ->orWhere('discord_username', 'ilike', "%{$search}%");
            }))
            ->orderByDesc('submitted_at')
            ->paginate(25)
            ->withQueryString();

        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/Applications/Index', [
            'event' => $event,
            'applications' => $applications,
            'tags' => $tags,
            'filters' => request()->only(['status', 'search']),
            'statusCounts' => $this->getStatusCounts($event),
        ]);
    }

    public function show(Event $event, Application $application)
    {
        $application->load([
            'user', 'reviewer', 'statusHistory.changedBy', 'tags',
            'responses.field', 'files.field',
            'event.forms.fields',
        ]);

        return Inertia::render('Admin/Applications/Show', [
            'event' => $event,
            'application' => $application,
            'tags' => Tag::orderBy('name')->get(),
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
        $application->update($validated);
        return back()->with('success', 'Notes saved.');
    }

    public function syncTags(Request $request, Event $event, Application $application)
    {
        $validated = $request->validate(['tag_ids' => 'array', 'tag_ids.*' => 'integer|exists:tags,id']);
        $application->tags()->sync($validated['tag_ids'] ?? []);
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
        $applications = Application::with(['user', 'tags', 'responses.field'])
            ->where('event_id', $event->id)
            ->orderByDesc('submitted_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"applications-{$event->slug}.csv\"",
        ];

        $callback = function () use ($applications, $event) {
            $handle = fopen('php://output', 'w');

            // Build headers from stage 1 form fields
            $form = $event->stageOneForm;
            $fields = $form?->fields()->where('type', 'not in', ['heading', 'paragraph', 'divider'])->get() ?? collect();

            $csvHeaders = ['ID', 'Name', 'Discord Username', 'Email', 'Status', 'Submitted At', 'Tags'];
            foreach ($fields as $field) {
                $csvHeaders[] = $field->label;
            }

            fputcsv($handle, $csvHeaders);

            foreach ($applications as $application) {
                $row = [
                    $application->id,
                    $application->user->name,
                    $application->user->discord_username,
                    $application->user->email ?? '',
                    $application->status,
                    $application->submitted_at?->format('Y-m-d H:i'),
                    $application->tags->pluck('name')->join(', '),
                ];

                foreach ($fields as $field) {
                    $response = $application->getResponseForField($field->id);
                    $row[] = $response?->value ?? '';
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
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
