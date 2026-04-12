<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotaAssignment extends Model
{
    protected $fillable = ['shift_id', 'user_id', 'is_manual', 'notes'];

    protected function casts(): array
    {
        return ['is_manual' => 'boolean'];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
