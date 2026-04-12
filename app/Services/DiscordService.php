<?php

namespace App\Services;

use App\Models\DiscordNotificationLog;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordService
{
    private string $botToken;
    private string $baseUrl = 'https://discord.com/api/v10';

    public function __construct()
    {
        $this->botToken = config('services.discord.bot_token');
    }

    public function sendDirectMessage(User $user, string $content, array $embeds = []): bool
    {
        try {
            // First, create a DM channel
            $channelResponse = Http::withToken($this->botToken, 'Bot')
                ->post("{$this->baseUrl}/users/@me/channels", [
                    'recipient_id' => $user->discord_id,
                ]);

            if (!$channelResponse->successful()) {
                $this->logFailure($user, 'dm', ['content' => $content], 'Failed to create DM channel: ' . $channelResponse->body());
                return false;
            }

            $channelId = $channelResponse->json('id');

            $payload = ['content' => $content];
            if (!empty($embeds)) {
                $payload['embeds'] = $embeds;
            }

            $messageResponse = Http::withToken($this->botToken, 'Bot')
                ->post("{$this->baseUrl}/channels/{$channelId}/messages", $payload);

            if ($messageResponse->successful()) {
                $messageId = $messageResponse->json('id');
                $this->logSuccess($user, 'dm', ['content' => $content], $messageId);
                return true;
            }

            $this->logFailure($user, 'dm', ['content' => $content], $messageResponse->body());
            return false;

        } catch (\Throwable $e) {
            $this->logFailure($user, 'dm', ['content' => $content], $e->getMessage());
            Log::error("Discord DM error for user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    private function logSuccess(User $user, string $type, array $payload, string $messageId): void
    {
        DiscordNotificationLog::create([
            'user_id' => $user->id,
            'type' => $type,
            'payload' => $payload,
            'discord_message_id' => $messageId,
            'delivered' => true,
            'sent_at' => now(),
        ]);
    }

    private function logFailure(User $user, string $type, array $payload, string $error): void
    {
        DiscordNotificationLog::create([
            'user_id' => $user->id,
            'type' => $type,
            'payload' => $payload,
            'delivered' => false,
            'error' => $error,
            'sent_at' => now(),
        ]);
    }
}
