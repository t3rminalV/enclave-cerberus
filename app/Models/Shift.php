<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shift extends Model
{
    protected $fillable = [
        'event_id', 'team_id', 'name', 'starts_at', 'ends_at',
        'min_volunteers', 'max_volunteers', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'min_volunteers' => 'integer',
            'max_volunteers' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RotaAssignment::class);
    }

    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rota_assignments')
            ->withPivot(['is_manual', 'notes'])
            ->withTimestamps();
    }

    public function getDurationMinutesAttribute(): int
    {
        return $this->starts_at->diffInMinutes($this->ends_at);
    }

    public function isFull(): bool
    {
        if (!$this->max_volunteers) return false;
        return $this->assignments()->count() >= $this->max_volunteers;
    }
}
