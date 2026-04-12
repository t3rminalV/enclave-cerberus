<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $fillable = [
        'form_id', 'type', 'label', 'placeholder', 'help_text',
        'required', 'order', 'options', 'validation_rules',
        'accepted_file_types', 'max_file_size_kb', 'max_files', 'content',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'options' => 'array',
            'validation_rules' => 'array',
            'accepted_file_types' => 'array',
            'max_file_size_kb' => 'integer',
            'max_files' => 'integer',
            'order' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function isFileType(): bool
    {
        return in_array($this->type, ['file', 'image']);
    }

    public function isDisplayOnly(): bool
    {
        return in_array($this->type, ['heading', 'paragraph', 'divider']);
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, ['select', 'multiselect', 'radio', 'checkbox']);
    }
}
