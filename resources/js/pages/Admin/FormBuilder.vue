<template>
  <Head :title="`Form Builder — Stage ${stage}`" />
  <AppLayout>
    <div class="page-header">
      <div>
        <div class="flex items-center gap-2 text-sm text-surface-400 mb-1">
          <Link :href="route('admin.events.show', event.id)" class="hover:text-surface-200">{{ event.name }}</Link>
          <ChevronRight class="w-3 h-3" />
          <span>Form Builder</span>
        </div>
        <h1 class="page-title">Stage {{ stage }} Form</h1>
        <p class="page-subtitle">{{ stage === 1 ? 'Initial application form' : 'Additional info (unlocks on acceptance)' }}</p>
      </div>
      <div class="flex items-center gap-2">
        <button @click="preview = !preview" class="btn-secondary">
          <Eye class="w-4 h-4" /> {{ preview ? 'Edit' : 'Preview' }}
        </button>
        <button @click="save" :disabled="saving" class="btn-primary">
          <Save class="w-4 h-4" /> {{ saving ? 'Saving…' : 'Save Form' }}
        </button>
      </div>
    </div>

    <div class="grid lg:grid-cols-4 gap-6">
      <!-- Field palette (left) -->
      <div v-if="!preview" class="lg:col-span-1">
        <div class="card card-body sticky top-8">
          <h3 class="text-sm font-semibold text-surface-200 mb-3">Add Field</h3>
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="type in fieldTypes"
              :key="type.value"
              @click="addField(type.value)"
              class="flex flex-col items-center gap-1.5 p-3 rounded-lg border border-surface-600 bg-surface-700/30 hover:bg-surface-700 hover:border-brand-500/50 text-surface-300 hover:text-surface-100 transition-all text-xs"
            >
              <component :is="type.icon" class="w-4 h-4" />
              {{ type.label }}
            </button>
          </div>

          <h3 class="text-sm font-semibold text-surface-200 mt-5 mb-3 flex items-center gap-2">
            <Library class="w-4 h-4" /> Field Library
          </h3>
          <div class="flex flex-col gap-1.5">
            <button
              v-for="item in fieldLibrary"
              :key="item.key"
              @click="addFromLibrary(item.key)"
              class="text-left text-xs px-3 py-2 rounded-md border border-surface-600 bg-surface-700/30 hover:bg-surface-700 hover:border-brand-500/50 text-surface-200 transition-all"
            >
              {{ item.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form canvas (middle/right) -->
      <div :class="preview ? 'lg:col-span-4' : 'lg:col-span-3'">
        <!-- Form settings -->
        <div class="card card-body mb-4" v-if="!preview">
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="label">Form Title</label>
              <input v-model="formData.title" class="input" placeholder="Application Form" />
            </div>
            <div>
              <label class="label">Description</label>
              <input v-model="formData.description" class="input" placeholder="Optional intro text" />
            </div>
          </div>
        </div>

        <!-- Clone from existing form -->
        <details v-if="!preview && cloneableForms.length" class="card card-body mb-4 group">
          <summary class="flex items-center justify-between cursor-pointer text-sm font-semibold text-surface-200">
            <span class="flex items-center gap-2"><Copy class="w-4 h-4" /> Clone from another form</span>
            <span class="text-xs text-surface-400 group-open:hidden">{{ cloneableForms.length }} available</span>
          </summary>
          <div class="grid sm:grid-cols-[1fr_auto_auto] gap-3 mt-4">
            <select v-model="cloneSourceId" class="input">
              <option :value="null">Choose a form…</option>
              <option v-for="f in cloneableForms" :key="f.id" :value="f.id">
                {{ f.event_name }} — Stage {{ f.stage }} ({{ f.field_count }} field{{ f.field_count === 1 ? '' : 's' }})
              </option>
            </select>
            <select v-model="cloneMode" class="input">
              <option value="append">Append</option>
              <option value="replace">Replace all</option>
            </select>
            <button @click="cloneFrom" :disabled="!cloneSourceId || cloning" class="btn-secondary">
              {{ cloning ? 'Cloning…' : 'Clone' }}
            </button>
          </div>
          <p class="text-xs text-surface-400 mt-2">Cloned fields are inserted as new — saving the form persists them. Unsaved local edits will be lost.</p>
        </details>

        <!-- Preview header -->
        <div v-if="preview" class="card card-body mb-4">
          <h2 class="text-xl font-bold text-white">{{ formData.title }}</h2>
          <p v-if="formData.description" class="text-surface-300 mt-1 text-sm">{{ formData.description }}</p>
        </div>

        <!-- Fields list -->
        <template v-if="!preview">
          <VueDraggable
            v-model="formData.fields"
            handle=".drag-handle"
            class="space-y-3"
            :animation="150"
          >
            <FieldEditor
              v-for="(field, index) in formData.fields"
              :key="field.uid"
              :field="field"
              :sibling-fields="formData.fields"
              @update="(f) => formData.fields[index] = f"
              @remove="removeField(index)"
            />
          </VueDraggable>

          <div v-if="!formData.fields.length" class="card card-body text-center text-surface-400 text-sm py-12">
            <Plus class="w-8 h-8 mx-auto mb-2 opacity-30" />
            <p>No fields yet. Add fields from the palette on the left.</p>
          </div>
        </template>

        <!-- Preview mode -->
        <template v-else>
          <div class="space-y-4">
            <FieldPreview
              v-for="field in formData.fields"
              :key="field.uid"
              :field="field"
            />
          </div>

          <div v-if="!formData.fields.length" class="card card-body text-center text-surface-400 text-sm py-12">
            <p>No fields to preview yet.</p>
          </div>
        </template>

        <div v-if="preview && formData.fields.length" class="mt-6 flex justify-end">
          <button class="btn-primary" disabled>Submit Application (Preview)</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { VueDraggable } from 'vue-draggable-plus';
import { Plus, Save, Eye, ChevronRight, Type, AlignLeft, Hash, Mail, Phone, List, CheckSquare, Upload, Image, Calendar, Minus, Heading, FileText, Copy, Library } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import FieldEditor from '@/components/forms/FieldEditor.vue';
import FieldPreview from '@/components/forms/FieldPreview.vue';

let uidCounter = 0;
function uid() { return ++uidCounter; }

const props = defineProps<{
  event: any;
  form: any;
  stage: number;
  cloneableForms: Array<{
    id: number;
    stage: number;
    title: string;
    event_name: string;
    field_count: number;
  }>;
}>();

const cloneSourceId = ref<number | null>(null);
const cloneMode = ref<'replace' | 'append'>('append');
const cloning = ref(false);

const preview = ref(false);
const saving = ref(false);

const formData = ref({
  title: props.form?.title ?? (props.stage === 1 ? 'Application Form' : 'Additional Information'),
  description: props.form?.description ?? '',
  is_active: props.form?.is_active ?? true,
  fields: (props.form?.fields ?? []).map((f: any) => ({ ...f, uid: uid() })),
});

const fieldTypes = [
  { value: 'text', label: 'Text', icon: Type },
  { value: 'textarea', label: 'Long Text', icon: AlignLeft },
  { value: 'number', label: 'Number', icon: Hash },
  { value: 'email', label: 'Email', icon: Mail },
  { value: 'phone', label: 'Phone', icon: Phone },
  { value: 'select', label: 'Dropdown', icon: List },
  { value: 'radio', label: 'Radio', icon: CheckSquare },
  { value: 'checkbox', label: 'Checkboxes', icon: CheckSquare },
  { value: 'file', label: 'File Upload', icon: Upload },
  { value: 'image', label: 'Image Upload', icon: Image },
  { value: 'date', label: 'Date', icon: Calendar },
  { value: 'heading', label: 'Heading', icon: Heading },
  { value: 'paragraph', label: 'Paragraph', icon: FileText },
  { value: 'divider', label: 'Divider', icon: Minus },
];

function addField(type: string) {
  formData.value.fields.push({
    uid: uid(),
    id: null,
    type,
    label: '',
    placeholder: '',
    help_text: '',
    required: false,
    options: type === 'select' || type === 'radio' || type === 'checkbox' || type === 'multiselect'
      ? ['Option 1', 'Option 2']
      : null,
    content: '',
    accepted_file_types: null,
    max_file_size_kb: 5120,
    max_files: 1,
    order: formData.value.fields.length,
  });
}

const fieldLibrary: Array<{ key: string; label: string; preset: Record<string, any> }> = [
  { key: 'tshirt', label: 'T-Shirt Size', preset: { type: 'select', label: 'T-Shirt size', required: true, options: ['XS','S','M','L','XL','XXL','3XL'] } },
  { key: 'dietary', label: 'Dietary Requirements', preset: { type: 'textarea', label: 'Dietary requirements or allergies', help_text: 'Tell us about any allergies, intolerances, or dietary preferences.', placeholder: 'e.g. vegetarian, nut allergy' } },
  { key: 'emergency_contact', label: 'Emergency Contact', preset: { type: 'text', label: 'Emergency contact (name and phone)', required: true, placeholder: 'Jane Doe — 07700 900000' } },
  { key: 'dob', label: 'Date of Birth', preset: { type: 'date', label: 'Date of birth', required: true } },
  { key: 'address', label: 'Postal Address', preset: { type: 'textarea', label: 'Postal address', placeholder: 'Street, City, Postcode' } },
  { key: 'previous_experience', label: 'Previous Experience', preset: { type: 'textarea', label: 'Previous volunteering or relevant experience', help_text: 'No experience required — but tell us if you have any!' } },
  { key: 'availability', label: 'Availability', preset: { type: 'checkbox', label: 'Which days can you attend?', options: ['Friday','Saturday','Sunday'], required: true } },
  { key: 'team_preference', label: 'Team Preference', preset: { type: 'multiselect', label: 'Which teams would you like to be considered for?', options: ['Stage','Tech','Tournament','Front of House','Security','Catering'], help_text: 'Pick as many as you\'d like — we can\'t guarantee assignment.' } },
  { key: 'travel', label: 'Travel & Accommodation', preset: { type: 'select', label: 'How will you be travelling to the event?', options: ['Driving','Train','Coach','Lift share','Other'] } },
  { key: 'photo_consent', label: 'Photo Consent', preset: { type: 'radio', label: 'Are you happy to appear in event photos and promotional material?', required: true, options: ['Yes','No','Ask me in person'] } },
  { key: 'tshirt_fit', label: 'T-Shirt Fit', preset: { type: 'select', label: 'T-Shirt fit', options: ['Unisex','Fitted'] } },
  { key: 'pronouns', label: 'Pronouns', preset: { type: 'text', label: 'Pronouns', placeholder: 'e.g. she/her, they/them' } },
  { key: 'access_needs', label: 'Access Needs', preset: { type: 'textarea', label: 'Access or accessibility needs', help_text: 'Anything we should know to make sure you can take part comfortably.' } },
  { key: 'first_aid', label: 'First-Aid Trained', preset: { type: 'radio', label: 'Are you a trained first-aider?', options: ['Yes — in-date certificate','Yes — out of date','No'] } },
];

function addFromLibrary(key: string) {
  const item = fieldLibrary.find(i => i.key === key);
  if (!item) return;
  const base = {
    uid: uid(),
    id: null,
    type: 'text',
    label: '',
    placeholder: '',
    help_text: '',
    required: false,
    options: null as string[] | null,
    content: '',
    accepted_file_types: null,
    max_file_size_kb: 5120,
    max_files: 1,
    order: formData.value.fields.length,
  };
  formData.value.fields.push({ ...base, ...item.preset });
}

function removeField(index: number) {
  formData.value.fields.splice(index, 1);
}

function save() {
  saving.value = true;
  router.put(route('admin.events.forms.save', [props.event.id, props.stage]), formData.value, {
    onFinish: () => { saving.value = false; },
  });
}

function cloneFrom() {
  if (!cloneSourceId.value) return;
  if (cloneMode.value === 'replace' && formData.value.fields.length) {
    if (!confirm('Replace all current fields with the cloned ones?')) return;
  }
  cloning.value = true;
  router.post(
    route('admin.events.forms.clone', [props.event.id, props.stage]),
    { source_form_id: cloneSourceId.value, mode: cloneMode.value },
    { onFinish: () => { cloning.value = false; } },
  );
}
</script>
