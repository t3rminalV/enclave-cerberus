<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::with('creator')
            ->withCount(['applications', 'teams'])
            ->orderByDesc('starts_at')
            ->paginate(20);

        return Inertia::render('Admin/Events/Index', ['events' => $events]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'applications_open_at' => 'nullable|date',
            'applications_close_at' => 'nullable|date',
            'status' => 'required|in:draft,open,closed,completed',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['created_by'] = auth()->id();

        // Ensure unique slug
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Event::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter++;
        }

        $event = Event::create($validated);
        AuditLog::record('event.created', $event, [], $event->toArray());

        return redirect()->route('admin.events.show', $event)->with('success', 'Event created.');
    }

    public function show(Event $event): Response
    {
        $event->load(['forms.fields', 'stageOneForm', 'stageTwoForm', 'feedbackForm', 'teams', 'rotaPublication']);
        $stats = [
            'applications_total' => $event->applications()->count(),
            'applications_submitted' => $event->applications()->where('status', 'submitted')->count(),
            'applications_accepted' => $event->applications()->where('status', 'accepted')->count(),
            'applications_rejected' => $event->applications()->where('status', 'rejected')->count(),
            'applications_waitlisted' => $event->applications()->where('status', 'waitlisted')->count(),
            'teams_count' => $event->teams()->count(),
            'shifts_count' => $event->shifts()->count(),
        ];

        return Inertia::render('Admin/Events/Show', ['event' => $event, 'stats' => $stats]);
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Admin/Events/Edit', ['event' => $event]);
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'applications_open_at' => 'nullable|date',
            'applications_close_at' => 'nullable|date',
            'status' => 'required|in:draft,open,closed,completed',
        ]);

        $old = $event->toArray();
        $event->update($validated);
        AuditLog::record('event.updated', $event, $old, $event->fresh()->toArray());

        return redirect()->route('admin.events.show', $event)->with('success', 'Event updated.');
    }

    public function destroy(Event $event)
    {
        AuditLog::record('event.deleted', $event, $event->toArray(), []);
        $event->delete();
        return redirect()->route('admin.events.index')->with('success', 'Event deleted.');
    }
}
