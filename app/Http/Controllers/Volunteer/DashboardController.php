<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FeedbackSubmission;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $myApplications = $user->applications()
            ->with(['event', 'statusHistory'])
            ->orderByDesc('created_at')
            ->get();

        $now = now();
        $openEvents = Event::where('status', 'open')
            ->where(fn($q) => $q->whereNull('applications_open_at')->orWhere('applications_open_at', '<=', $now))
            ->where(fn($q) => $q->whereNull('applications_close_at')->orWhere('applications_close_at', '>=', $now))
            ->whereDoesntHave('applications', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('starts_at')
            ->get();

        $submittedEventIds = FeedbackSubmission::where('user_id', $user->id)->pluck('event_id');
        $pendingFeedback = Event::query()
            ->whereHas('applications', fn ($q) => $q->where('user_id', $user->id)->where('status', 'accepted'))
            ->whereHas('feedbackForm', fn ($q) => $q->where('is_active', true))
            ->where('ends_at', '<', $now)
            ->whereNotIn('id', $submittedEventIds)
            ->orderByDesc('ends_at')
            ->get(['id', 'name', 'starts_at', 'ends_at']);

        return Inertia::render('Volunteer/Dashboard', [
            'myApplications' => $myApplications,
            'openEvents' => $openEvents,
            'pendingFeedback' => $pendingFeedback,
        ]);
    }
}
