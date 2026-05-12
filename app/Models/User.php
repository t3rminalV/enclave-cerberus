<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'discord_id',
        'name',
        'email',
        'discord_username',
        'discord_avatar',
        'role',
        'first_name',
        'last_name',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'tshirt_size',
        'dietary_requirements',
        'medical_info',
        'calendar_token',
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'role' => 'string',
        ];
    }

    // Convenience accessors — used throughout the app
    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'admin';
    }

    public function getIsOrganiserAttribute(): bool
    {
        return $this->role === 'organiser';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'organiser']);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['role', 'is_lead'])
            ->withTimestamps();
    }

    public function rotaAssignments(): HasMany
    {
        return $this->hasMany(RotaAssignment::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(DiscordNotificationLog::class);
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->discord_avatar && str_starts_with($this->discord_avatar, 'http')) {
            return $this->discord_avatar;
        }

        if ($this->discord_id && $this->discord_avatar) {
            $extension = str_starts_with($this->discord_avatar, 'a_') ? 'gif' : 'png';
            return "https://cdn.discordapp.com/avatars/{$this->discord_id}/{$this->discord_avatar}.{$extension}";
        }

        return "https://cdn.discordapp.com/embed/avatars/0.png";
    }

    public function hasCompleteProfile(): bool
    {
        return filled($this->first_name)
            && filled($this->last_name)
            && filled($this->phone)
            && filled($this->emergency_contact_name)
            && filled($this->emergency_contact_phone);
    }

    public function getApplicationForEvent(int $eventId): ?Application
    {
        return $this->applications()->where('event_id', $eventId)->first();
    }

    public function ensureCalendarToken(): string
    {
        if (!$this->calendar_token) {
            $this->calendar_token = bin2hex(random_bytes(24));
            $this->save();
        }
        return $this->calendar_token;
    }
}
