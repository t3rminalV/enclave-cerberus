<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Event $event)
    {
        $teams = $event->teams()->with(['members'])->withCount('members')->get();

        $acceptedVolunteers = \App\Models\User::whereHas('applications', function ($q) use ($event) {
            $q->where('event_id', $event->id)->where('status', 'accepted');
        })->get();

        return Inertia::render('Admin/Teams/Index', [
            'event' => $event,
            'teams' => $teams,
            'acceptedVolunteers' => $acceptedVolunteers,
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $validated['event_id'] = $event->id;
        $validated['order'] = $event->teams()->max('order') + 1;

        $team = Team::create($validated);

        AuditLog::record('team.created', $team, [], $team->toArray());

        return back()->with('success', 'Team created.');
    }

    public function update(Request $request, Event $event, Team $team)
    {
        abort_if($team->event_id !== $event->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $old = $team->only(array_keys($validated));
        $team->update($validated);

        AuditLog::record('team.updated', $team, $old, $team->fresh()->only(array_keys($validated)));

        return back()->with('success', 'Team updated.');
    }

    public function destroy(Event $event, Team $team)
    {
        abort_if($team->event_id !== $event->id, 404);
        $old = $team->toArray();
        $team->delete();

        AuditLog::record('team.deleted', $team, $old, []);

        return back()->with('success', 'Team deleted.');
    }

    public function syncMembers(Request $request, Event $event, Team $team)
    {
        abort_if($team->event_id !== $event->id, 404);

        $validated = $request->validate([
            'members' => 'array',
            'members.*.user_id' => 'required|integer|exists:users,id',
            'members.*.role' => 'nullable|string|max:100',
            'members.*.is_lead' => 'boolean',
        ]);

        // Verify all users are accepted volunteers for this event
        $acceptedUserIds = Application::where('event_id', $event->id)
            ->where('status', 'accepted')
            ->pluck('user_id')
            ->toArray();

        $memberData = collect($validated['members'] ?? [])
            ->filter(fn($m) => in_array($m['user_id'], $acceptedUserIds))
            ->keyBy('user_id')
            ->map(fn($m) => ['role' => $m['role'] ?? null, 'is_lead' => $m['is_lead'] ?? false])
            ->toArray();

        $oldMembers = $team->members()->pluck('users.id')->all();
        $team->members()->sync($memberData);

        AuditLog::record('team.members_synced', $team, ['user_ids' => $oldMembers], [
            'user_ids' => array_keys($memberData),
        ]);

        return back()->with('success', 'Team members updated.');
    }

    public function reorder(Request $request, Event $event)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:teams,id',
        ]);

        foreach ($validated['order'] as $index => $teamId) {
            Team::where('id', $teamId)->where('event_id', $event->id)->update(['order' => $index]);
        }

        AuditLog::record('team.reordered', $event, [], ['team_ids' => $validated['order']]);

        return back()->with('success', 'Teams reordered.');
    }
}
