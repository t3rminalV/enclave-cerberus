<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Event;
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

        $openEvents = Event::where('status', 'open')
            ->whereDoesntHave('applications', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('starts_at')
            ->get();

        return Inertia::render('Volunteer/Dashboard', [
            'myApplications' => $myApplications,
            'openEvents' => $openEvents,
        ]);
    }
}
