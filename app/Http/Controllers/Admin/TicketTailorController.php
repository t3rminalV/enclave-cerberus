<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TicketTailorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TicketTailorController extends Controller
{
    public function __construct(private TicketTailorService $tickets) {}

    public function ping(): JsonResponse
    {
        if (!$this->tickets->isConfigured()) {
            return response()->json(['ok' => false, 'message' => 'TICKETTAILOR_API_KEY is not set.'], 422);
        }
        try {
            $ok = $this->tickets->ping();
            return response()->json(['ok' => $ok, 'message' => $ok ? 'Connected.' : 'API returned an error.']);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function events(Request $request): JsonResponse
    {
        if (!$this->tickets->isConfigured()) {
            return response()->json(['error' => 'TICKETTAILOR_API_KEY is not set.'], 422);
        }
        try {
            $payload = $this->tickets->listEvents(
                startingAfter: $request->query('starting_after') ?: null,
                search: $request->query('q') ?: null,
                limit: (int) ($request->query('limit') ?: 50),
            );
            return response()->json($this->shapeEvents($payload));
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    private function shapeEvents(array $payload): array
    {
        $events = collect($payload['data'] ?? [])->map(function ($ev) {
            return [
                'id' => $ev['id'] ?? null,
                'name' => $ev['name'] ?? '(unnamed)',
                'status' => $ev['status'] ?? null,
                'start' => $ev['start']['iso'] ?? null,
                'end' => $ev['end']['iso'] ?? null,
                'event_series_id' => $ev['event_series_id'] ?? null,
                'ticket_types' => collect($ev['ticket_types'] ?? [])->map(fn ($tt) => [
                    'id' => $tt['id'] ?? null,
                    'name' => $tt['name'] ?? '(unnamed)',
                    'status' => $tt['status'] ?? null,
                    'price' => $tt['price'] ?? null,
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'events' => $events,
            'next_cursor' => $payload['links']['next'] ?? null,
        ];
    }
}
