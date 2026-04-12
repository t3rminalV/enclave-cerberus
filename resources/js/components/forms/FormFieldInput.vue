<template>
  <div v-if="field.type === 'divider'">
    <hr class="border-surface-700/60 my-2" />
  </div>
  <div v-else-if="field.type === 'heading'">
    <h3 class="text-lg font-semibold text-white mt-2">{{ field.content }}</h3>
  </div>
  <div v-else-if="field.type === 'paragraph'">
    <p class="text-sm text-surface-300">{{ field.content }}</p>
  </div>
  <div v-else>
    <label class="label">
      {{ field.label }}
      <span v-if="field.required" class="text-red-400 ml-0.5">*</span>
    </label>

    <textarea v-if="field.type === 'textarea'"
      :value="modelValue" @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
      class="textarea" :class="{ 'input-error': error }" :placeholder="field.placeholder" rows="4" />

    <select v-else-if="field.type === 'select'"
      :value="modelValue" @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
      class="select" :class="{ 'input-error': error }">
      <option value="">{{ field.placeholder || 'Select…' }}</option>
      <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
    </select>

    <div v-else-if="field.type === 'multiselect'" class="space-y-2">
      <label v-for="opt in field.options" :key="opt" class="flex items-center gap-2 text-sm text-surface-200 cursor-pointer">
        <input type="checkbox" :value="opt" :checked="(modelValue ?? []).includes(opt)"
          @change="toggleMulti(opt, $event)" class="rounded text-brand-500" />
        {{ opt }}
      </label>
    </div>

    <div v-else-if="field.type === 'radio'" class="space-y-2">
      <label v-for="opt in field.options" :key="opt" class="flex items-center gap-2 text-sm text-surface-200 cursor-pointer">
        <input type="radio" :name="`field_${field.id}`" :value="opt" :checked="modelValue === opt"
          @change="$emit('update:modelValue', opt)" class="text-brand-500" />
        {{ opt }}
      </label>
    </div>

    <div v-else-if="field.type === 'checkbox'" class="space-y-2">
      <label v-for="opt in field.options" :key="opt" class="flex items-center gap-2 text-sm text-surface-200 cursor-pointer">
        <input type="checkbox" :value="opt" :checked="(modelValue ?? []).includes(opt)"
          @change="toggleMulti(opt, $event)" class="rounded text-brand-500" />
        {{ opt }}
      </label>
    </div>

    <div v-else-if="field.type === 'file' || field.type === 'image'">
      <label class="block">
        <div :class="['border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors', dragOver ? 'border-brand-400 bg-brand-900/20' : 'border-surface-600 hover:border-surface-500']"
          @dragover.prevent="dragOver = true" @dragleave="dragOver = false" @drop.prevent="handleDrop">
          <Upload class="w-6 h-6 mx-auto mb-2 text-surface-400" />
          <p class="text-sm text-surface-300">Drag & drop or <span class="text-brand-400">browse</span></p>
          <p v-if="field.accepted_file_types?.length" class="text-xs text-surface-500 mt-1">{{ field.accepted_file_types.join(', ') }}</p>
          <p v-if="field.max_file_size_kb" class="text-xs text-surface-500">Max {{ (field.max_file_size_kb / 1024).toFixed(1) }} MB</p>
          <input type="file" class="hidden" :multiple="(field.max_files ?? 1) > 1" :accept="field.accepted_file_types?.join(',')"
            @change="handleFileChange" ref="fileInput" />
        </div>
        <div v-if="selectedFiles.length" class="mt-2 space-y-1">
          <div v-for="f in selectedFiles" :key="f.name" class="flex items-center gap-2 text-xs text-surface-300">
            <Paperclip class="w-3 h-3" /> {{ f.name }}
          </div>
        </div>
      </label>
    </div>

    <input v-else
      :type="inputType" :value="modelValue"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      class="input" :class="{ 'input-error': error }" :placeholder="field.placeholder" />

    <p v-if="field.help_text" class="form-hint">{{ field.help_text }}</p>
    <p v-if="error" class="form-error">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Upload, Paperclip } from 'lucide-vue-next';

const props = defineProps<{
  field: any;
  modelValue?: any;
  error?: string;
}>();

const emit = defineEmits(['update:modelValue', 'file-change']);

const dragOver = ref(false);
const selectedFiles = ref<File[]>([]);
const fileInput = ref<HTMLInputElement>();

const inputType = computed(() => {
  const map: Record<string, string> = { email: 'email', phone: 'tel', number: 'number', date: 'date' };
  return map[props.field.type] ?? 'text';
});

function toggleMulti(opt: string, event: Event) {
  const current = [...(props.modelValue ?? [])];
  const checked = (event.target as HTMLInputElement).checked;
  if (checked) { if (!current.includes(opt)) current.push(opt); }
  else { const i = current.indexOf(opt); if (i >= 0) current.splice(i, 1); }
  emit('update:modelValue', current);
}

function handleFileChange(event: Event) {
  const files = Array.from((event.target as HTMLInputElement).files ?? []);
  selectedFiles.value = files;
  emit('file-change', files);
}

function handleDrop(event: DragEvent) {
  dragOver.value = false;
  const files = Array.from(event.dataTransfer?.files ?? []);
  selectedFiles.value = files;
  emit('file-change', files);
}
</script>
