<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Application extends Model
{
    protected $fillable = [
        'user_id', 'event_id', 'status', 'current_stage',
        'submitted_at', 'reviewed_by', 'reviewed_at', 'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'current_stage' => 'integer',
        ];
    }

    const STATUSES = ['draft', 'submitted', 'under_review', 'accepted', 'rejected', 'waitlisted', 'withdrawn'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ApplicationResponse::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ApplicationFile::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->orderBy('created_at');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'application_tags')->withTimestamps();
    }

    public function getResponseForField(int $fieldId): ?ApplicationResponse
    {
        return $this->responses->firstWhere('form_field_id', $fieldId);
    }

    public function transitionTo(string $newStatus, ?User $changedBy = null, ?string $note = null): void
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => $newStatus,
            'reviewed_by' => $changedBy?->id ?? $this->reviewed_by,
            'reviewed_at' => now(),
        ]);

        if ($newStatus === 'accepted' && $oldStatus !== 'accepted') {
            $this->update(['current_stage' => 2]);
        }

        $this->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $changedBy?->id,
            'note' => $note,
        ]);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'submitted']);
    }
}
