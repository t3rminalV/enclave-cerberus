<template>
  <div class="card border-surface-600/60 group">
    <!-- Header bar -->
    <div class="px-4 py-3 flex items-center gap-3 border-b border-surface-700/60">
      <span class="drag-handle cursor-grab active:cursor-grabbing text-surface-500 hover:text-surface-300">
        <GripVertical class="w-4 h-4" />
      </span>
      <span class="text-xs font-medium text-surface-400 uppercase tracking-wide">{{ fieldTypeLabel }}</span>
      <div class="ml-auto flex items-center gap-1">
        <label v-if="!isDisplayOnly" class="flex items-center gap-1.5 text-xs text-surface-400 cursor-pointer">
          <input type="checkbox" :checked="field.required" @change="update('required', ($event.target as HTMLInputElement).checked)" class="rounded" />
          Required
        </label>
        <button @click="$emit('remove')" class="btn-ghost btn-icon text-red-400 hover:text-red-300 hover:bg-red-900/20">
          <Trash2 class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>

    <!-- Field config -->
    <div class="p-4 space-y-3">
      <!-- Label / Content -->
      <div v-if="field.type === 'heading' || field.type === 'paragraph'">
        <label class="label">{{ field.type === 'heading' ? 'Heading Text' : 'Paragraph Text' }}</label>
        <textarea v-if="field.type === 'paragraph'" :value="field.content" @input="update('content', ($event.target as HTMLTextAreaElement).value)" class="textarea" rows="3" placeholder="Enter text..." />
        <input v-else :value="field.content" @input="update('content', ($event.target as HTMLInputElement).value)" class="input" placeholder="Enter heading..." />
      </div>

      <div v-else-if="field.type !== 'divider'" class="grid sm:grid-cols-2 gap-3">
        <div>
          <label class="label">Label</label>
          <input :value="field.label" @input="update('label', ($event.target as HTMLInputElement).value)" class="input" placeholder="Field label" />
        </div>
        <div>
          <label class="label">Placeholder <span class="text-surface-500 font-normal">(optional)</span></label>
          <input :value="field.placeholder" @input="update('placeholder', ($event.target as HTMLInputElement).value)" class="input" placeholder="Hint text..." />
        </div>
      </div>

      <!-- Help text -->
      <div v-if="!isDisplayOnly && field.type !== 'divider'">
        <label class="label">Help Text <span class="text-surface-500 font-normal">(optional)</span></label>
        <input :value="field.help_text" @input="update('help_text', ($event.target as HTMLInputElement).value)" class="input" placeholder="Additional guidance for the volunteer" />
      </div>

      <!-- Options for select/radio/checkbox -->
      <div v-if="hasOptions">
        <label class="label">Options</label>
        <div class="space-y-2">
          <div v-for="(opt, i) in (field.options ?? [])" :key="i" class="flex gap-2">
            <input :value="opt" @input="updateOption(i, ($event.target as HTMLInputElement).value)" class="input flex-1" :placeholder="`Option ${i + 1}`" />
            <button @click="removeOption(i)" class="btn-ghost btn-icon shrink-0">
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
          <button @click="addOption" class="btn-ghost btn-sm gap-1">
            <Plus class="w-3 h-3" /> Add Option
          </button>
        </div>
      </div>

      <!-- File upload settings -->
      <div v-if="isFileType" class="grid sm:grid-cols-3 gap-3">
        <div>
          <label class="label">Max File Size</label>
          <select :value="field.max_file_size_kb" @change="update('max_file_size_kb', Number(($event.target as HTMLSelectElement).value))" class="select">
            <option :value="512">512 KB</option>
            <option :value="1024">1 MB</option>
            <option :value="5120">5 MB</option>
            <option :value="10240">10 MB</option>
            <option :value="25600">25 MB</option>
            <option :value="51200">50 MB</option>
          </select>
        </div>
        <div>
          <label class="label">Max Files</label>
          <input type="number" :value="field.max_files ?? 1" @input="update('max_files', Number(($event.target as HTMLInputElement).value))" class="input" min="1" max="10" />
        </div>
        <div>
          <label class="label">Accepted Types</label>
          <input :value="(field.accepted_file_types ?? []).join(', ')" @input="update('accepted_file_types', ($event.target as HTMLInputElement).value.split(',').map(s => s.trim()).filter(Boolean))" class="input" placeholder=".pdf, .doc" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { GripVertical, Trash2, X, Plus } from 'lucide-vue-next';

const props = defineProps<{ field: any }>();
const emit = defineEmits(['update', 'remove']);

const fieldTypeLabels: Record<string, string> = {
  text: 'Short Text', textarea: 'Long Text', email: 'Email', phone: 'Phone', number: 'Number',
  select: 'Dropdown', multiselect: 'Multi-select', radio: 'Radio', checkbox: 'Checkboxes',
  file: 'File Upload', image: 'Image Upload', date: 'Date', heading: 'Heading', paragraph: 'Paragraph', divider: 'Divider',
};

const fieldTypeLabel = computed(() => fieldTypeLabels[props.field.type] ?? props.field.type);
const isDisplayOnly = computed(() => ['heading', 'paragraph', 'divider'].includes(props.field.type));
const isFileType = computed(() => ['file', 'image'].includes(props.field.type));
const hasOptions = computed(() => ['select', 'multiselect', 'radio', 'checkbox'].includes(props.field.type));

function update(key: string, value: any) {
  emit('update', { ...props.field, [key]: value });
}

function addOption() {
  const opts = [...(props.field.options ?? []), `Option ${(props.field.options?.length ?? 0) + 1}`];
  update('options', opts);
}

function removeOption(i: number) {
  const opts = [...(props.field.options ?? [])];
  opts.splice(i, 1);
  update('options', opts);
}

function updateOption(i: number, value: string) {
  const opts = [...(props.field.options ?? [])];
  opts[i] = value;
  update('options', opts);
}
</script>
