<template>
  <div class="card card-body flex items-center gap-4">
    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', iconBg]">
      <component :is="iconComponent" class="w-5 h-5" :class="iconColor" />
    </div>
    <div>
      <p class="text-2xl font-bold text-white">{{ value.toLocaleString() }}</p>
      <p class="text-xs text-surface-400 mt-0.5">{{ label }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Users, Calendar, Activity, ClipboardList, FileText, CheckCircle } from 'lucide-vue-next';

const props = defineProps<{
  label: string;
  value: number;
  icon: string;
  color?: 'brand' | 'blue' | 'green' | 'yellow' | 'red';
}>();

const icons: Record<string, any> = { Users, Calendar, Activity, ClipboardList, FileText, CheckCircle };
const iconComponent = computed(() => icons[props.icon] ?? FileText);

const colorMap = {
  brand: { bg: 'bg-brand-900/40', icon: 'text-brand-400' },
  blue: { bg: 'bg-blue-900/40', icon: 'text-blue-400' },
  green: { bg: 'bg-green-900/40', icon: 'text-green-400' },
  yellow: { bg: 'bg-yellow-900/40', icon: 'text-yellow-400' },
  red: { bg: 'bg-red-900/40', icon: 'text-red-400' },
};

const iconBg = computed(() => colorMap[props.color ?? 'brand'].bg);
const iconColor = computed(() => colorMap[props.color ?? 'brand'].icon);
</script>
