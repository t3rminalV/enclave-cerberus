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

        return Inertia::render('Admin/FormBuilder', [
            'event' => $event,
            'form' => $form,
            'stage' => (int) $stage,
        ]);
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
