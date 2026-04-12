<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotaPublication extends Model
{
    protected $fillable = ['event_id', 'published_by', 'is_live', 'published_at'];

    protected function casts(): array
    {
        return [
            'is_live' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
