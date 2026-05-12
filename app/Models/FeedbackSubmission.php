<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackSubmission extends Model
{
    protected $fillable = ['user_id', 'event_id', 'submitted_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }
}
