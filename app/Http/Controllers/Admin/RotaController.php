<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\RotaAssignment;
use App\Models\RotaPublication;
use App\Models\RotaRules;
use App\Models\Shift;
use App\Services\NotificationService;
use App\Services\RotaGeneratorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RotaController extends Controller
{
    public function __construct(
        private RotaGeneratorService $generator,
        private NotificationService $notifications,
    ) {}

    public function index(Event $event)
    {
        $event->load([
            'teams.members',
            'shifts.assignments.user',
            'shifts.team',
            'rotaRules',
            'rotaPublication.publisher',
        ]);

        $acceptedVolunteers = \App\Models\User::whereHas('applications', function ($q) use ($event) {
            $q->where('event_id', $event->id)->where('status', 'accepted');
        })->get();

        return Inertia::render('Admin/Rota/Index', [
            'event' => $event,
            'acceptedVolunteers' => $acceptedVolunteers,
        ]);
    }

    public function saveRules(Request $request, Event $event)
    {
        $validated = $request->validate([
            'min_shift_minutes' => 'required|integer|min:30|max:1440',
            'max_shift_minutes' => 'required|integer|min:30|max:1440|gte:min_shift_minutes',
            'min_rest_minutes' => 'required|integer|min:0|max:1440',
            'max_shifts_per_volunteer' => 'required|integer|min:1|max:20',
        ]);

        $old = RotaRules::where('event_id', $event->id)->first()?->only(array_keys($validated)) ?? [];

        $rules = RotaRules::updateOrCreate(
            ['event_id' => $event->id],
            $validated
        );

        AuditLog::record('rota.rules_saved', $event, $old, $rules->only(array_keys($validated)));

        return back()->with('success', 'Rules saved.');
    }

    public function storeShift(Request $request, Event $event)
    {
        $validated = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
            'name' => 'required|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'min_volunteers' => 'required|integer|min:0',
            'max_volunteers' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        // Ensure team belongs to this event
        $team = \App\Models\Team::where('id', $validated['team_id'])
            ->where('event_id', $event->id)
            ->firstOrFail();

        $validated['event_id'] = $event->id;
        $shift = Shift::create($validated);

        AuditLog::record('shift.created', $shift, [], $shift->toArray());

        return back()->with('success', 'Shift created.');
    }

    public function updateShift(Request $request, Event $event, Shift $shift)
    {
        abort_if($shift->event_id !== $event->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'min_volunteers' => 'required|integer|min:0',
            'max_volunteers' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $old = $shift->only(array_keys($validated));
        $shift->update($validated);

        AuditLog::record('shift.updated', $shift, $old, $shift->fresh()->only(array_keys($validated)));

        return back()->with('success', 'Shift updated.');
    }

    public function destroyShift(Event $event, Shift $shift)
    {
        abort_if($shift->event_id !== $event->id, 404);
        $old = $shift->toArray();
        $shift->delete();

        AuditLog::record('shift.deleted', $shift, $old, []);

        return back()->with('success', 'Shift deleted.');
    }

    public function generate(Event $event)
    {
        $result = $this->generator->generate($event);
        $this->generator->applyGenerated($event, $result['assignments']);

        AuditLog::record('rota.generated', $event, [], ['count' => count($result['assignments'])]);

        return back()->with([
            'success' => 'Rota generated with ' . count($result['assignments']) . ' assignments.',
            'warnings' => $result['warnings'],
        ]);
    }

    public function assignVolunteer(Request $request, Event $event)
    {
        $validated = $request->validate([
            'shift_id' => 'required|integer|exists:shifts,id',
            'user_id' => 'required|integer|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $shift = Shift::where('id', $validated['shift_id'])
            ->where('event_id', $event->id)
            ->firstOrFail();

        $assignment = RotaAssignment::updateOrCreate(
            ['shift_id' => $shift->id, 'user_id' => $validated['user_id']],
            ['is_manual' => true, 'notes' => $validated['notes'] ?? null]
        );

        AuditLog::record('rota.assignment_saved', $shift, [], [
            'assignment_id' => $assignment->id,
            'user_id' => $validated['user_id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Volunteer assigned.');
    }

    public function removeAssignment(Event $event, Shift $shift, int $userId)
    {
        abort_if($shift->event_id !== $event->id, 404);
        $deleted = RotaAssignment::where('shift_id', $shift->id)->where('user_id', $userId)->delete();

        AuditLog::record('rota.assignment_removed', $shift, ['user_id' => $userId], [
            'deleted' => $deleted,
        ]);

        return back()->with('success', 'Assignment removed.');
    }

    public function publish(Request $request, Event $event)
    {
        $validated = $request->validate(['is_live' => 'required|boolean']);

        $publication = RotaPublication::updateOrCreate(
            ['event_id' => $event->id],
            [
                'published_by' => auth()->id(),
                'is_live' => $validated['is_live'],
                'published_at' => $validated['is_live'] ? now() : null,
            ]
        );

        if ($validated['is_live']) {
            $this->notifications->notifyRotaPublished($event);
            AuditLog::record('rota.published', $event);
        } else {
            AuditLog::record('rota.unpublished', $event);
        }

        return back()->with('success', $validated['is_live'] ? 'Rota published and volunteers notified.' : 'Rota unpublished.');
    }

    public function exportCsv(Event $event)
    {
        $event->load(['shifts.team', 'shifts.assignments.user']);

        AuditLog::record('rota.exported', $event, [], [
            'shifts_count' => $event->shifts->count(),
            'assignments_count' => $event->shifts->sum(fn($shift) => $shift->assignments->count()),
        ]);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"rota-{$event->slug}.csv\"",
        ];

        $callback = function () use ($event) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Team', 'Shift', 'Starts At', 'Ends At', 'Duration (hrs)', 'Volunteer', 'Discord Username', 'Manual Override']);

            foreach ($event->shifts as $shift) {
                foreach ($shift->assignments as $assignment) {
                    fputcsv($handle, [
                        $shift->team->name,
                        $shift->name,
                        $shift->starts_at->format('Y-m-d H:i'),
                        $shift->ends_at->format('Y-m-d H:i'),
                        round($shift->starts_at->diffInMinutes($shift->ends_at) / 60, 1),
                        $assignment->user->name,
                        $assignment->user->discord_username,
                        $assignment->is_manual ? 'Yes' : 'No',
                    ]);
                }
            }

            fclose($handle);
        };

        return \Illuminate\Support\Facades\Response::stream($callback, 200, $headers);
    }
}
