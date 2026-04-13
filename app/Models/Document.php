<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = [
        'event_id', 'title', 'description', 'disk', 'path',
        'original_filename', 'mime_type', 'size_bytes', 'visibility', 'uploaded_by',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'document_team_access')->withTimestamps();
    }

    public function getUrl(): string
    {
        return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes(30));
    }

    public function getSizeFormattedAttribute(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function isVisibleToUser(User $user, Event $event): bool
    {
        if ($user->is_admin) return true;

        if ($this->visibility === 'public') return true;

        $application = $user->getApplicationForEvent($event->id);
        if (!$application) return false;

        return match ($this->visibility) {
            'all_volunteers' => in_array($application->status, ['submitted', 'under_review', 'accepted']),
            'accepted_only' => $application->status === 'accepted',
            'specific_teams' => $application->status === 'accepted' &&
                $this->teams()->whereHas('members', fn($q) => $q->where('users.id', $user->id))->exists(),
            default => false,
        };
    }
}
