<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\DiscordService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDiscordNotification
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly string $content,
        public readonly array $embeds = [],
    ) {}

    public function handle(DiscordService $discord): void
    {
        try {
            $discord->sendDirectMessage($this->user, $this->content, $this->embeds);
        } catch (Throwable $e) {
            Log::warning('Discord notification failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
