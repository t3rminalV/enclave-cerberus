<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FormBuilderController extends Controller
{
    public function show(Event $event, string $stage)
    {
        abort_if(!in_array($stage, ['1', '2']), 404);

        $form = Form::with('fields')->firstOrNew([
            'event_id' => $event->id,
            'stage' => $stage,
        ]);

        if (!$form->exists) {
            $form->title = $stage === '1' ? 'Application Form' : 'Additional Information';
        }

        $cloneableForms = Form::with('event:id,name')
            ->where('id', '!=', $form->id ?? 0)
            ->whereHas('fields')
            ->orderByDesc('updated_at')
            ->get(['id', 'event_id', 'stage', 'title'])
            ->map(fn ($f) => [
                'id' => $f->id,
                'stage' => $f->stage,
                'title' => $f->title,
                'event_name' => $f->event?->name,
                'field_count' => $f->fields()->count(),
            ]);

        return Inertia::render('Admin/FormBuilder', [
            'event' => $event,
            'form' => $form,
            'stage' => (int) $stage,
            'cloneableForms' => $cloneableForms,
        ]);
    }

    public function clone(Request $request, Event $event, string $stage)
    {
        abort_if(!in_array($stage, ['1', '2']), 404);

        $validated = $request->validate([
            'source_form_id' => 'required|integer|exists:forms,id',
            'mode' => 'required|in:replace,append',
        ]);

        $source = Form::with('fields')->findOrFail($validated['source_form_id']);

        $target = Form::firstOrCreate(
            ['event_id' => $event->id, 'stage' => $stage],
            ['title' => $source->title, 'description' => $source->description, 'is_active' => true]
        );

        if ($validated['mode'] === 'replace') {
            $target->fields()->delete();
            $target->update(['title' => $source->title, 'description' => $source->description]);
            $startOrder = 0;
        } else {
            $startOrder = ($target->fields()->max('order') ?? -1) + 1;
        }

        foreach ($source->fields as $i => $field) {
            $attrs = $field->only([
                'type', 'label', 'placeholder', 'help_text', 'required',
                'options', 'validation_rules', 'accepted_file_types',
                'max_file_size_kb', 'max_files', 'content',
            ]);
            $attrs['form_id'] = $target->id;
            $attrs['order'] = $startOrder + $i;
            FormField::create($attrs);
        }

        AuditLog::record('form.cloned', $target, [], [
            'source_form_id' => $source->id,
            'source_event_id' => $source->event_id,
            'mode' => $validated['mode'],
            'fields_copied' => $source->fields->count(),
        ]);

        return back()->with('success', "Cloned {$source->fields->count()} field(s) from \"{$source->event?->name}\".");
    }

    public function save(Request $request, Event $event, string $stage)
    {
        abort_if(!in_array($stage, ['1', '2']), 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'fields' => 'array',
            'fields.*.id' => 'nullable|integer',
            'fields.*.type' => 'required|string',
            'fields.*.label' => 'required_unless:fields.*.type,divider|string|max:255|nullable',
            'fields.*.placeholder' => 'nullable|string',
            'fields.*.help_text' => 'nullable|string',
            'fields.*.required' => 'boolean',
            'fields.*.order' => 'integer',
            'fields.*.options' => 'nullable|array',
            'fields.*.content' => 'nullable|string',
            'fields.*.accepted_file_types' => 'nullable|array',
            'fields.*.max_file_size_kb' => 'nullable|integer',
            'fields.*.max_files' => 'nullable|integer',
        ]);

        $form = Form::where('event_id', $event->id)->where('stage', $stage)->first();
        $old = $form ? [
            'title' => $form->title,
            'description' => $form->description,
            'is_active' => $form->is_active,
            'fields_count' => $form->fields()->count(),
        ] : [];

        $form = Form::updateOrCreate(
            ['event_id' => $event->id, 'stage' => $stage],
            [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        $incomingIds = [];
        foreach ($validated['fields'] ?? [] as $index => $fieldData) {
            $fieldData['order'] = $index;
            $fieldData['form_id'] = $form->id;

            if (!empty($fieldData['id'])) {
                $field = FormField::find($fieldData['id']);
                if ($field && $field->form_id === $form->id) {
                    $field->update($fieldData);
                    $incomingIds[] = $field->id;
                    continue;
                }
            }

            $field = FormField::create($fieldData);
            $incomingIds[] = $field->id;
        }

        // Remove deleted fields
        FormField::where('form_id', $form->id)
            ->whereNotIn('id', $incomingIds)
            ->delete();

        AuditLog::record('form.saved', $form, $old, [
            'event_id' => $event->id,
            'stage' => $stage,
            'title' => $form->title,
            'description' => $form->description,
            'is_active' => $form->is_active,
            'fields_count' => count($incomingIds),
        ]);

        return back()->with('success', 'Form saved.');
    }
}
