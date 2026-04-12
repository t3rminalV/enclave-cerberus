<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\ApplicationResponse;
use App\Models\Event;
use App\Models\Form;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        ]);
    }

    public function store(Request $request, Event $event)
    {
        abort_unless($event->isAcceptingApplications(), 403);

        $user = auth()->user();
        abort_if($user->getApplicationForEvent($event->id) !== null, 409, 'You have already applied to this event.');

        $form = $event->stageOneForm()->with('fields')->firstOrFail();

        $application = DB::transaction(function () use ($request, $event, $user, $form) {
            $application = Application::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'status' => 'draft',
                'current_stage' => 1,
            ]);

            $this->saveFormResponses($request, $application, $form);

            return $application;
        });

        return redirect()->route('volunteer.applications.show', $application)->with('success', 'Application saved as draft.');
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
        });

        return redirect()->route('volunteer.applications.show', $application)
            ->with('success', 'Application submitted successfully!');
    }

    public function downloadFile(Application $application, ApplicationFile $file)
    {
        abort_if($application->user_id !== auth()->id(), 403);
        abort_if($file->application_id !== $application->id, 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_filename);
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

                    foreach (array_slice($uploadedFiles, 0, $field->max_files ?? 1) as $file) {
                        $path = $file->store("applications/{$application->id}", 'local');
                        ApplicationFile::create([
                            'application_id' => $application->id,
                            'form_field_id' => $field->id,
                            'disk' => 'local',
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
