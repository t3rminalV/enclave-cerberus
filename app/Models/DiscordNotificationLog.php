<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordNotificationLog extends Model
{
    protected $table = 'discord_notification_log';

    protected $fillable = [
        'user_id', 'type', 'payload', 'discord_message_id', 'delivered', 'error', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'delivered' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
