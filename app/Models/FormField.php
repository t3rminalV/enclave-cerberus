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
        'visible_when',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'options' => 'array',
            'validation_rules' => 'array',
            'accepted_file_types' => 'array',
            'visible_when' => 'array',
            'max_file_size_kb' => 'integer',
            'max_files' => 'integer',
            'order' => 'integer',
        ];
    }

    public function isVisibleGiven(array $responsesByFieldId): bool
    {
        if (empty($this->visible_when)) return true;
        $rule = $this->visible_when;
        $depFieldId = $rule['field_id'] ?? null;
        $expected = $rule['equals'] ?? null;
        if (!$depFieldId) return true;
        $actual = $responsesByFieldId[$depFieldId] ?? null;
        if (is_array($expected)) return in_array($actual, $expected, true);
        if (is_array($actual)) return in_array($expected, $actual, true);
        return (string) $actual === (string) $expected;
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
