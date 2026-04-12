<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotaRules extends Model
{
    protected $fillable = [
        'event_id',
        'min_shift_minutes',
        'max_shift_minutes',
        'min_rest_minutes',
        'max_shifts_per_volunteer',
    ];

    protected function casts(): array
    {
        return [
            'min_shift_minutes' => 'integer',
            'max_shift_minutes' => 'integer',
            'min_rest_minutes' => 'integer',
            'max_shifts_per_volunteer' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
