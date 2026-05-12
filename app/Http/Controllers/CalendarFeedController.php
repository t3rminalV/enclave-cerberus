<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Response;

class CalendarFeedController extends Controller
{
    public function show(string $token): Response
    {
        $user = User::where('calendar_token', $token)->first();
        abort_if(!$user, 404);

        $shifts = Shift::query()
            ->whereHas('volunteers', fn ($q) => $q->where('users.id', $user->id))
            ->whereHas('event.rotaPublication', fn ($q) => $q->where('is_live', true))
            ->with(['event:id,name,location', 'team:id,name'])
            ->orderBy('starts_at')
            ->get();

        $ics = $this->buildIcs($user, $shifts);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="cerberus.ics"',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    private function buildIcs(User $user, $shifts): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Cerberus//Volunteer Rota//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Cerberus Volunteer Rota',
            'X-WR-TIMEZONE:UTC',
        ];

        $now = now()->utc()->format('Ymd\THis\Z');

        foreach ($shifts as $shift) {
            $eventName = $shift->event?->name ?? 'Event';
            $teamName = $shift->team?->name ?? null;
            $title = $teamName ? "{$eventName} — {$teamName}" : $eventName;
            if ($shift->name) {
                $title .= " · {$shift->name}";
            }

            $description = trim(implode("\\n", array_filter([
                $teamName ? "Team: {$teamName}" : null,
                $shift->notes,
            ])));

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:shift-' . $shift->id . '-user-' . $user->id . '@cerberus';
            $lines[] = 'DTSTAMP:' . $now;
            $lines[] = 'DTSTART:' . $shift->starts_at->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTEND:' . $shift->ends_at->utc()->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:' . $this->escape($title);
            if ($description) {
                $lines[] = 'DESCRIPTION:' . $this->escape($description);
            }
            if ($shift->event?->location) {
                $lines[] = 'LOCATION:' . $this->escape($shift->event->location);
            }
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    private function escape(string $value): string
    {
        return str_replace(
            ["\\", ";", ",", "\n"],
            ["\\\\", "\\;", "\\,", "\\n"],
            $value,
        );
    }
}
