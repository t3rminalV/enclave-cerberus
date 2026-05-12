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
        [$firstName, $lastName] = $this->splitName($user);
        $email = $user->email;
        if (!$email) {
            throw new RuntimeException("Cannot issue a TicketTailor ticket: user #{$user->id} has no email.");
        }

        $response = Http::withBasicAuth(config('services.tickettailor.api_key'), '')
            ->acceptJson()
            ->asForm()
            ->timeout(15)
            ->post(config('services.tickettailor.base_url') . '/issued_tickets', [
                'event_id' => $event->tickettailor_event_id,
                'ticket_type_id' => $event->tickettailor_ticket_type_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'reference' => "volunteer-app-{$application->id}",
            ]);

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

        $data = $response->json();
        $ticketId = $data['id'] ?? null;
        $reference = $data['reference'] ?? $data['barcode'] ?? null;

        $application->update([
            'tickettailor_ticket_id' => $ticketId,
            'tickettailor_ticket_reference' => $reference,
            'tickettailor_issued_at' => now(),
            'tickettailor_last_error' => null,
        ]);
    }

    private function splitName($user): array
    {
        if (filled($user->first_name) || filled($user->last_name)) {
            return [$user->first_name ?: '—', $user->last_name ?: ''];
        }
        $parts = preg_split('/\s+/', trim((string) $user->name), 2);
        return [$parts[0] ?? '—', $parts[1] ?? ''];
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
