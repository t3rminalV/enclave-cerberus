<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'color'];

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'application_tags')->withTimestamps();
    }
}
