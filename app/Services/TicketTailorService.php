<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TicketTailorService
{
    public function isConfigured(): bool
    {
        return filled(config('services.tickettailor.api_key'));
    }

    public function issueVolunteerTicket(Application $application): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('TicketTailor API key is not configured.');
        }

        $application->loadMissing(['user', 'event']);

        $event = $application->event;
        if (!$event->tickettailor_event_id || !$event->tickettailor_ticket_type_id) {
            throw new RuntimeException('This event has no TicketTailor mapping set.');
        }

        if ($application->tickettailor_ticket_id) {
            return; // already issued
        }

        $user = $application->user;
        $fullName = $this->fullName($user);
        $email = $user->email;

        $response = $this->client()
            ->asForm()
            ->post('/v1/issued_tickets', array_filter([
                'event_id' => $event->tickettailor_event_id,
                'ticket_type_id' => $event->tickettailor_ticket_type_id,
                'full_name' => $fullName,
                'email' => $email,
                'reference' => "volunteer-app-{$application->id}",
                'send_email' => $email ? 'true' : null,
            ], fn ($v) => $v !== null && $v !== ''));

        if ($response->failed()) {
            $message = $this->extractError($response);
            $application->update(['tickettailor_last_error' => $message]);
            Log::warning('TicketTailor ticket issue failed', [
                'application_id' => $application->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException($message);
        }

        // TicketTailor wraps the created ticket in { data: [IssuedTicket] }
        $ticket = $response->json('data.0') ?? $response->json('data') ?? $response->json();
        $ticketId = $ticket['id'] ?? null;
        $reference = $ticket['reference'] ?? $ticket['barcode'] ?? null;

        $application->update([
            'tickettailor_ticket_id' => $ticketId,
            'tickettailor_ticket_reference' => $reference,
            'tickettailor_issued_at' => now(),
            'tickettailor_last_error' => null,
        ]);
    }

    public function listEvents(?string $startingAfter = null, ?string $search = null, int $limit = 100): array
    {
        $response = $this->client()
            ->get('/v1/events', array_filter([
                'limit' => $limit,
                'starting_after' => $startingAfter,
                'name' => $search,
            ]));

        if ($response->failed()) {
            throw new RuntimeException($this->extractError($response));
        }

        return $response->json();
    }

    public function ping(): bool
    {
        $response = $this->client()->get('/v1/ping');
        return $response->successful();
    }

    private function client()
    {
        return Http::withBasicAuth(config('services.tickettailor.api_key'), '')
            ->baseUrl(rtrim(config('services.tickettailor.base_url'), '/'))
            ->acceptJson()
            ->timeout(15);
    }

    private function fullName($user): string
    {
        if (filled($user->first_name) || filled($user->last_name)) {
            return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        }
        return (string) ($user->name ?: 'Volunteer');
    }

    private function extractError(Response $response): string
    {
        $json = $response->json();
        if (is_array($json)) {
            if (!empty($json['errors']) && is_array($json['errors'])) {
                $first = $json['errors'][0] ?? null;
                if (is_array($first)) return $first['message'] ?? json_encode($first);
                if (is_string($first)) return $first;
            }
            if (!empty($json['error'])) return is_string($json['error']) ? $json['error'] : json_encode($json['error']);
            if (!empty($json['message'])) return (string) $json['message'];
        }
        return "TicketTailor API returned HTTP {$response->status()}.";
    }
}
