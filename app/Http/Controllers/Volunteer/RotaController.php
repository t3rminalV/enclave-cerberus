<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Inertia\Inertia;

class RotaController extends Controller
{
    public function show(Event $event)
    {
        $user = auth()->user();
        $application = $user->getApplicationForEvent($event->id);

        abort_if(!$application || $application->status !== 'accepted', 403, 'You must be an accepted volunteer to view the rota.');
        abort_unless($event->rotaPublished, 403, 'The rota has not been published yet.');

        $event->load([
            'teams.members',
            'shifts.assignments.user',
            'shifts.team',
            'rotaPublication',
        ]);

        return Inertia::render('Volunteer/Rota', [
            'event' => $event,
            'currentUser' => $user,
        ]);
    }
}
