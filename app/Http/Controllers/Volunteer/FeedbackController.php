<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\FeedbackResponse;
use App\Models\FeedbackSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    public function show(Event $event)
    {
        $user = auth()->user();
        $this->guard($event, $user);

        $form = $event->feedbackForm()->with('fields')->first();
        abort_if(!$form || !$form->is_active, 404, 'No feedback survey is available for this event.');

        $submitted = FeedbackSubmission::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();

        $existingResponses = FeedbackResponse::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->pluck('value', 'form_field_id');

        return Inertia::render('Volunteer/Feedback', [
            'event' => $event,
            'form' => $form,
            'submitted' => $submitted,
            'existingResponses' => $existingResponses,
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $user = auth()->user();
        $this->guard($event, $user);

        $form = $event->feedbackForm()->with('fields')->first();
        abort_if(!$form || !$form->is_active, 404);

        $alreadySubmitted = FeedbackSubmission::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();
        abort_if($alreadySubmitted, 422, 'You have already submitted feedback for this event.');

        $values = $request->input('fields', []);
        foreach ($form->fields as $field) {
            if ($field->isDisplayOnly()) continue;
            $value = $values[$field->id] ?? null;
            FeedbackResponse::updateOrCreate(
                ['user_id' => $user->id, 'event_id' => $event->id, 'form_field_id' => $field->id],
                ['value' => is_array($value) ? json_encode($value) : $value],
            );
        }

        FeedbackSubmission::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'submitted_at' => now(),
        ]);

        AuditLog::record('feedback.submitted', $event, [], ['user_id' => $user->id]);

        return redirect()->route('volunteer.dashboard')->with('success', 'Thanks for your feedback!');
    }

    private function guard(Event $event, $user): void
    {
        $accepted = $user->applications()
            ->where('event_id', $event->id)
            ->where('status', 'accepted')
            ->exists();
        abort_unless($accepted, 403, 'Only accepted volunteers can leave feedback.');
        abort_if(now()->lt($event->ends_at), 403, 'Feedback opens after the event ends.');
    }
}
