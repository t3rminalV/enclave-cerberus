<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\ApplicationResponse;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Form;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function create(Event $event)
    {
        abort_unless($event->isAcceptingApplications(), 403, 'Applications are not currently open for this event.');

        $user = auth()->user();
        $existing = $user->getApplicationForEvent($event->id);

        if ($existing) {
            return redirect()->route('volunteer.applications.show', $existing);
        }

        if (! $user->hasCompleteProfile()) {
            return redirect()->route('profile.edit')
                ->with('error', 'Please complete your profile before applying. First name, last name, contact number, and emergency contact details are required.');
        }

        $form = $event->stageOneForm()->with('fields')->firstOrFail();

        return Inertia::render('Volunteer/Applications/Apply', [
            'event' => $event,
            'form' => $form,
            'user' => $user,
        ]);
    }

    public function show(Application $application)
    {
        abort_if($application->user_id !== auth()->id(), 403);

        $application->load(['event', 'statusHistory', 'responses.field', 'files.field']);

        $form = Form::with('fields')
            ->where('event_id', $application->event_id)
            ->where('stage', $application->current_stage)
            ->where('is_active', true)
            ->first();

        return Inertia::render('Volunteer/Applications/Show', [
            'application' => $application,
            'form' => $form,
            'user' => auth()->user(),
        ]);
    }

    public function store(Request $request, Event $event)
    {
        abort_unless($event->isAcceptingApplications(), 403);

        $user = auth()->user();
        abort_if($user->getApplicationForEvent($event->id) !== null, 409, 'You have already applied to this event.');
        if (! $user->hasCompleteProfile()) {
            return redirect()->route('profile.edit')
                ->with('error', 'Please complete your profile before applying. First name, last name, contact number, and emergency contact details are required.');
        }

        $form = $event->stageOneForm()->with('fields')->firstOrFail();
        $shouldSubmit = $request->boolean('_submit');

        if ($shouldSubmit) {
            $this->validateRequiredFields($request, $form);
        }

        $application = DB::transaction(function () use ($request, $event, $user, $form, $shouldSubmit) {
            $application = Application::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'status' => 'draft',
                'current_stage' => 1,
            ]);

            $this->saveFormResponses($request, $application, $form);

            if ($shouldSubmit) {
                $application->update(['status' => 'submitted', 'submitted_at' => now()]);
                $application->statusHistory()->create([
                    'from_status' => 'draft',
                    'to_status' => 'submitted',
                    'changed_by' => $user->id,
                ]);
                $this->notifications->notifyApplicationReceived($application);
            }

            AuditLog::record(
                $shouldSubmit ? 'application.submitted' : 'application.draft_created',
                $application,
                [],
                ['event_id' => $event->id, 'status' => $application->status],
                $user
            );

            return $application;
        });

        $message = $shouldSubmit ? 'Application submitted successfully!' : 'Application saved as draft.';
        return redirect()->route('volunteer.applications.show', $application)->with('success', $message);
    }

    public function update(Request $request, Application $application)
    {
        abort_if($application->user_id !== auth()->id(), 403);
        abort_unless($application->isEditable(), 403, 'This application can no longer be edited.');

        $form = Form::with('fields')
            ->where('event_id', $application->event_id)
            ->where('stage', $application->current_stage)
            ->where('is_active', true)
            ->firstOrFail();

        DB::transaction(function () use ($request, $application, $form) {
            $this->saveFormResponses($request, $application, $form);
        });

        AuditLog::record('application.updated', $application, [], [
            'event_id' => $application->event_id,
            'status' => $application->status,
        ]);

        return back()->with('success', 'Application saved.');
    }

    public function submit(Request $request, Application $application)
    {
        abort_if($application->user_id !== auth()->id(), 403);
        abort_unless(in_array($application->status, ['draft', 'submitted']), 403);

        $form = Form::with('fields')
            ->where('event_id', $application->event_id)
            ->where('stage', $application->current_stage)
            ->where('is_active', true)
            ->firstOrFail();

        $this->validateRequiredFields($request, $form, $application);

        DB::transaction(function () use ($request, $application, $form) {
            $this->saveFormResponses($request, $application, $form);

            $isFirstSubmission = $application->status === 'draft';

            $application->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $application->statusHistory()->create([
                'from_status' => $isFirstSubmission ? 'draft' : 'submitted',
                'to_status' => 'submitted',
                'changed_by' => auth()->id(),
            ]);

            if ($isFirstSubmission) {
                $this->notifications->notifyApplicationReceived($application);
            }

            AuditLog::record('application.submitted', $application, [
                'status' => $isFirstSubmission ? 'draft' : 'submitted',
            ], [
                'event_id' => $application->event_id,
                'status' => 'submitted',
            ]);
        });

        return redirect()->route('volunteer.applications.show', $application)
            ->with('success', 'Application submitted successfully!');
    }

    public function downloadFile(Application $application, ApplicationFile $file)
    {
        abort_if($application->user_id !== auth()->id(), 403);
        abort_if($file->application_id !== $application->id, 404);

        AuditLog::record('application_file.downloaded', $file, [], [
            'application_id' => $application->id,
        ]);

        return Storage::disk($file->disk)->download($file->path, $file->original_filename);
    }

    private function validateRequiredFields(Request $request, Form $form, ?Application $application = null): void
    {
        $errors = [];
        $responsesById = $this->collectResponsesByFieldId($request, $form);

        foreach ($form->fields as $field) {
            if ($field->isDisplayOnly() || ! $field->required) {
                continue;
            }

            if (! $field->isVisibleGiven($responsesById)) {
                continue;
            }

            $inputKey = "fields.{$field->id}";

            if ($field->isFileType()) {
                $hasNew = $request->hasFile($inputKey);
                $hasExisting = $application
                    ? ApplicationFile::where('application_id', $application->id)
                        ->where('form_field_id', $field->id)
                        ->exists()
                    : false;

                if (! $hasNew && ! $hasExisting) {
                    $errors[$inputKey] = "The {$field->label} field is required.";
                }
            } else {
                $value = $request->input($inputKey);
                if ($value === null || $value === '' || $value === []) {
                    $errors[$inputKey] = "The {$field->label} field is required.";
                }
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function collectResponsesByFieldId(Request $request, Form $form): array
    {
        $map = [];
        foreach ($form->fields as $field) {
            $map[$field->id] = $request->input("fields.{$field->id}");
        }
        return $map;
    }

    private function saveFormResponses(Request $request, Application $application, Form $form): void
    {
        foreach ($form->fields as $field) {
            if ($field->isDisplayOnly()) continue;

            $inputKey = "fields.{$field->id}";

            if ($field->isFileType()) {
                if ($request->hasFile($inputKey)) {
                    $uploadedFiles = is_array($request->file($inputKey))
                        ? $request->file($inputKey)
                        : [$request->file($inputKey)];

                    // Delete old files for this field if replacing
                    ApplicationFile::where('application_id', $application->id)
                        ->where('form_field_id', $field->id)
                        ->each(function ($f) {
                            Storage::disk($f->disk)->delete($f->path);
                            $f->delete();
                        });

                    $disk = config('filesystems.default');
                    foreach (array_slice($uploadedFiles, 0, $field->max_files ?? 1) as $file) {
                        $path = $file->store("applications/{$application->id}", $disk);
                        ApplicationFile::create([
                            'application_id' => $application->id,
                            'form_field_id' => $field->id,
                            'disk' => $disk,
                            'path' => $path,
                            'original_filename' => $file->getClientOriginalName(),
                            'mime_type' => $file->getMimeType(),
                            'size_bytes' => $file->getSize(),
                        ]);
                    }
                }
            } else {
                $value = $request->input($inputKey);
                if ($value !== null) {
                    ApplicationResponse::updateOrCreate(
                        ['application_id' => $application->id, 'form_field_id' => $field->id],
                        ['value' => is_array($value) ? json_encode($value) : $value]
                    );
                }
            }
        }
    }
}
