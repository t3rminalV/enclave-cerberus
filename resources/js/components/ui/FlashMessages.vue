<template>
  <div class="px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
    <Transition
      v-for="(msg, type) in visible"
      :key="type"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
      enter-active-class="transition duration-200"
      leave-active-class="transition duration-150"
    >
      <div v-if="msg" :class="alertClass(type as string)" class="flex items-start gap-3">
        <component :is="icon(type as string)" class="w-4 h-4 mt-0.5 shrink-0" />
        <span>{{ msg }}</span>
        <button @click="dismiss(type as string)" class="ml-auto shrink-0 opacity-60 hover:opacity-100">
          <X class="w-4 h-4" />
        </button>
      </div>
    </Transition>

    <!-- Warnings list -->
    <Transition
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      enter-active-class="transition duration-200"
    >
      <div v-if="warnings.length" class="alert-warning">
        <div class="font-medium mb-1">Warnings:</div>
        <ul class="list-disc list-inside space-y-0.5">
          <li v-for="w in warnings" :key="w">{{ w }}</li>
        </ul>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

const page = usePage();
const visible = ref<Record<string, string | null>>({ success: null, error: null });
const warnings = ref<string[]>([]);

watch(() => page.props.flash, (flash: any) => {
  if (flash?.success) { visible.value.success = flash.success; setTimeout(() => dismiss('success'), 5000); }
  if (flash?.error) { visible.value.error = flash.error; setTimeout(() => dismiss('error'), 6000); }
  if (flash?.warnings?.length) { warnings.value = flash.warnings; setTimeout(() => { warnings.value = []; }, 8000); }
}, { immediate: true, deep: true });

function dismiss(type: string) { visible.value[type] = null; }

function alertClass(type: string) {
  return { success: 'alert-success', error: 'alert-error', warning: 'alert-warning' }[type] ?? 'alert-info';
}

function icon(type: string) {
  return { success: CheckCircle, error: AlertCircle, warning: AlertTriangle }[type] ?? Info;
}
</script>
