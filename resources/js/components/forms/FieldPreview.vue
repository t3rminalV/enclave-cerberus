<template>
  <div>
    <!-- Display-only elements -->
    <template v-if="field.type === 'divider'">
      <hr class="border-surface-700/60 my-4" />
    </template>
    <template v-else-if="field.type === 'heading'">
      <h3 class="text-lg font-semibold text-white">{{ field.content || 'Heading' }}</h3>
    </template>
    <template v-else-if="field.type === 'paragraph'">
      <p class="text-sm text-surface-300">{{ field.content || 'Paragraph text' }}</p>
    </template>

    <!-- Input fields -->
    <div v-else class="space-y-1.5">
      <label class="label">
        {{ field.label || 'Untitled Field' }}
        <span v-if="field.required" class="text-red-400 ml-0.5">*</span>
      </label>

      <template v-if="field.type === 'textarea'">
        <textarea class="textarea" :placeholder="field.placeholder" disabled rows="3" />
      </template>
      <template v-else-if="field.type === 'select'">
        <select class="select" disabled>
          <option value="">{{ field.placeholder || 'Select an option' }}</option>
          <option v-for="opt in (field.options ?? [])" :key="opt">{{ opt }}</option>
        </select>
      </template>
      <template v-else-if="field.type === 'radio'">
        <div class="space-y-2">
          <label v-for="opt in (field.options ?? [])" :key="opt" class="flex items-center gap-2 text-sm text-surface-300 cursor-pointer">
            <input type="radio" disabled class="text-brand-500" /> {{ opt }}
          </label>
        </div>
      </template>
      <template v-else-if="field.type === 'checkbox'">
        <div class="space-y-2">
          <label v-for="opt in (field.options ?? [])" :key="opt" class="flex items-center gap-2 text-sm text-surface-300 cursor-pointer">
            <input type="checkbox" disabled class="rounded text-brand-500" /> {{ opt }}
          </label>
        </div>
      </template>
      <template v-else-if="field.type === 'file' || field.type === 'image'">
        <div class="border-2 border-dashed border-surface-600 rounded-lg p-6 text-center text-surface-400 text-sm">
          <Upload class="w-6 h-6 mx-auto mb-2 opacity-50" />
          <p>Click to upload {{ field.type === 'image' ? 'an image' : 'a file' }}</p>
          <p v-if="field.accepted_file_types?.length" class="text-xs mt-1">
            Accepted: {{ field.accepted_file_types.join(', ') }}
          </p>
          <p v-if="field.max_file_size_kb" class="text-xs mt-0.5">
            Max {{ (field.max_file_size_kb / 1024).toFixed(1) }} MB
          </p>
        </div>
      </template>
      <template v-else>
        <input :type="inputType" class="input" :placeholder="field.placeholder" disabled />
      </template>

      <p v-if="field.help_text" class="form-hint">{{ field.help_text }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Upload } from 'lucide-vue-next';

const props = defineProps<{ field: any }>();

const inputType = computed(() => {
  const map: Record<string, string> = { email: 'email', phone: 'tel', number: 'number', date: 'date' };
  return map[props.field.type] ?? 'text';
});
</script>
