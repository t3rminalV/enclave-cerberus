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
import { Plus, Save, Eye, ChevronRight, Type, AlignLeft, Hash, Mail, Phone, List, CheckSquare, Upload, Image, Calendar, Minus, Heading, FileText } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import FieldEditor from '@/components/forms/FieldEditor.vue';
import FieldPreview from '@/components/forms/FieldPreview.vue';

let uidCounter = 0;
function uid() { return ++uidCounter; }

const props = defineProps<{
  event: any;
  form: any;
  stage: number;
}>();

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

function removeField(index: number) {
  formData.value.fields.splice(index, 1);
}

function save() {
  saving.value = true;
  router.put(route('admin.events.forms.save', [props.event.id, props.stage]), formData.value, {
    onFinish: () => { saving.value = false; },
  });
}
</script>
