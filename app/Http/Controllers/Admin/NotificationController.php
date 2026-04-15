<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Event $event)
    {
        return Inertia::render('Admin/Notifications/Index', [
            'event' => $event,
            'teams' => $event->teams()->get(),
            'recentLogs' => \App\Models\DiscordNotificationLog::with('user')
                ->whereHas('user', fn($q) => $q->whereHas('applications', fn($aq) => $aq->where('event_id', $event->id)))
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function sendBulk(Request $request, Event $event)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'recipient_type' => 'required|in:all,accepted,submitted,team',
            'team_id' => 'nullable|integer|exists:teams,id',
        ]);

        $query = User::whereHas('applications', function ($q) use ($event, $validated) {
            $q->where('event_id', $event->id);
            match ($validated['recipient_type']) {
                'accepted' => $q->where('status', 'accepted'),
                'submitted' => $q->whereIn('status', ['submitted', 'under_review']),
                'team' => $q->where('status', 'accepted'),
                default => null,
            };
        });

        if ($validated['recipient_type'] === 'team' && $validated['team_id']) {
            $query->whereHas('teams', fn($tq) => $tq->where('teams.id', $validated['team_id']));
        }

        $users = $query->get();
        $this->notifications->notifyBulkMessage($users->all(), $validated['subject'], $validated['message'], $event);

        AuditLog::record('notification.bulk_sent', $event, [], [
            'subject' => $validated['subject'],
            'recipient_type' => $validated['recipient_type'],
            'team_id' => $validated['team_id'] ?? null,
            'count' => $users->count(),
        ]);

        return back()->with('success', "Message queued for {$users->count()} volunteers.");
    }
}
