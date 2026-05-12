<?php

namespace App\Services;

use App\Jobs\SendDiscordNotification;
use App\Models\Application;
use App\Models\Document;
use App\Models\Event;
use App\Models\MessageTemplate;
use App\Models\User;

class NotificationService
{
    public function notifyApplicationReceived(Application $application): void
    {
        $event = $application->event;
        $user = $application->user;

        SendDiscordNotification::dispatchAfterResponse($user,
            "**Application Received** :white_check_mark:",
            [[
                'title' => "Your application for {$event->name} has been received",
                'description' => "We'll review it shortly and keep you updated here.",
                'color' => 0x6366f1,
                'fields' => [
                    ['name' => 'Event', 'value' => $event->name, 'inline' => true],
                    ['name' => 'Status', 'value' => 'Submitted', 'inline' => true],
                ],
            ]]
        );
    }

    public function notifyStatusChanged(Application $application, string $oldStatus): void
    {
        $event = $application->event;
        $user = $application->user;
        $newStatus = $application->status;

        $statusLabels = [
            'under_review' => ['label' => 'Under Review', 'color' => 0xf59e0b, 'message' => "Your application is now being reviewed by our team."],
            'accepted' => ['label' => 'Accepted! :tada:', 'color' => 0x22c55e, 'message' => "Congratulations! You've been accepted as a volunteer. Please check the portal for next steps."],
            'rejected' => ['label' => 'Unsuccessful', 'color' => 0xef4444, 'message' => "Unfortunately your application was not successful this time. Thank you for applying."],
            'waitlisted' => ['label' => 'Waitlisted', 'color' => 0x8b5cf6, 'message' => "You've been placed on our waitlist. We'll contact you if a spot opens up."],
        ];

        if (!isset($statusLabels[$newStatus])) return;

        $info = $statusLabels[$newStatus];

        SendDiscordNotification::dispatchAfterResponse($user,
            "**Application Update** — {$event->name}",
            [[
                'title' => "Application Status: {$info['label']}",
                'description' => $info['message'],
                'color' => $info['color'],
            ]]
        );
    }

    public function notifyStage2Unlocked(Application $application): void
    {
        $event = $application->event;
        $user = $application->user;

        SendDiscordNotification::dispatchAfterResponse($user,
            "**Additional Information Required** — {$event->name}",
            [[
                'title' => "Stage 2 of your application is now available",
                'description' => "Now that you've been accepted, please complete the additional information form in the volunteer portal.",
                'color' => 0x6366f1,
            ]]
        );
    }

    public function notifyRotaPublished(Event $event): void
    {
        $acceptedVolunteers = User::whereHas('applications', function ($q) use ($event) {
            $q->where('event_id', $event->id)->where('status', 'accepted');
        })->get();

        foreach ($acceptedVolunteers as $volunteer) {
            SendDiscordNotification::dispatchAfterResponse($volunteer,
                "**Rota Published** :calendar: — {$event->name}",
                [[
                    'title' => "The volunteer rota for {$event->name} is now live",
                    'description' => "Log in to the volunteer portal to view your shifts and team assignments.",
                    'color' => 0x6366f1,
                ]]
            );
        }
    }

    public function notifyDocumentShared(Document $document, array $users): void
    {
        $eventName = $document->event?->name ?? 'the event';

        foreach ($users as $user) {
            SendDiscordNotification::dispatchAfterResponse($user,
                "**New Document Available** :page_facing_up: — {$eventName}",
                [[
                    'title' => $document->title,
                    'description' => ($document->description ?? '') . "\n\nLog in to the volunteer portal to view and download this document.",
                    'color' => 0x6366f1,
                ]]
            );
        }
    }

    public function notifyBulkMessage(array $users, string $subject, string $message, ?Event $event = null): void
    {
        $eventContext = $event ? " — {$event->name}" : '';

        foreach ($users as $user) {
            $vars = $this->templateVarsFor($user, $event);
            $renderedSubject = MessageTemplate::render($subject, $vars);
            $renderedBody = MessageTemplate::render($message, $vars);

            SendDiscordNotification::dispatchAfterResponse($user,
                "**{$renderedSubject}**{$eventContext}",
                [[
                    'description' => $renderedBody,
                    'color' => 0x6366f1,
                ]]
            );
        }
    }

    private function templateVarsFor(User $user, ?Event $event): array
    {
        $teamName = null;
        if ($event) {
            $teamName = $user->teams()->where('teams.event_id', $event->id)->value('teams.name');
        }

        return [
            'name' => $user->name,
            'first_name' => $user->first_name ?: $user->name,
            'discord_username' => $user->discord_username,
            'event' => $event?->name ?? '',
            'team' => $teamName ?? '',
        ];
    }
}
