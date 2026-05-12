<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'location',
        'starts_at', 'ends_at', 'applications_open_at', 'applications_close_at',
        'status', 'created_by',
        'tickettailor_event_id', 'tickettailor_ticket_type_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'applications_open_at' => 'datetime',
            'applications_close_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->name);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function forms(): HasMany
    {
        return $this->hasMany(Form::class);
    }

    public function stageOneForm(): HasOne
    {
        return $this->hasOne(Form::class)->where('stage', '1');
    }

    public function stageTwoForm(): HasOne
    {
        return $this->hasOne(Form::class)->where('stage', '2');
    }

    public function feedbackForm(): HasOne
    {
        return $this->hasOne(Form::class)->where('stage', '3');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class)->orderBy('order');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class)->orderBy('starts_at');
    }

    public function rotaRules(): HasOne
    {
        return $this->hasOne(RotaRules::class);
    }

    public function rotaPublication(): HasOne
    {
        return $this->hasOne(RotaPublication::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function isAcceptingApplications(): bool
    {
        if ($this->status !== 'open') return false;
        $now = now();
        if ($this->applications_open_at && $now->lt($this->applications_open_at)) return false;
        if ($this->applications_close_at && $now->gt($this->applications_close_at)) return false;
        return true;
    }

    public function getRotaPublishedAttribute(): bool
    {
        return $this->rotaPublication?->is_live ?? false;
    }
}
