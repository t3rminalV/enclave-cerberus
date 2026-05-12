<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\TicketTailorService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

class IssueTicketTailorTicket
{
    use Dispatchable;

    public function __construct(public readonly int $applicationId) {}

    public function handle(TicketTailorService $tickets): void
    {
        $application = Application::with(['user', 'event'])->find($this->applicationId);
        if (!$application) return;
        if ($application->status !== 'accepted') return;
        if ($application->tickettailor_ticket_id) return;
        if (!$tickets->isConfigured()) return;
        if (!$application->event->tickettailor_event_id) return;

        try {
            $tickets->issueVolunteerTicket($application);
        } catch (Throwable $e) {
            Log::warning('TicketTailor ticket dispatch failed', [
                'application_id' => $this->applicationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
