<template>
  <Link :href="href" class="card card-body flex items-start gap-4 hover:border-brand-500/40 hover:bg-surface-700/40 transition-all group">
    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', bgClass]">
      <component :is="iconComponent" class="w-5 h-5" :class="iconClass" />
    </div>
    <div class="flex-1 min-w-0">
      <p class="font-semibold text-white group-hover:text-brand-300 transition-colors">{{ title }}</p>
      <p class="text-sm text-surface-400 mt-0.5">{{ description }}</p>
    </div>
    <ArrowRight class="w-4 h-4 text-surface-500 group-hover:text-brand-400 transition-colors mt-1 shrink-0" />
  </Link>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, ClipboardList, FileText, Users, Calendar, Folder, Bell, Settings } from 'lucide-vue-next';

const props = defineProps<{
  title: string;
  description: string;
  icon: string;
  href: string;
  color?: string;
}>();

const icons: Record<string, any> = { ClipboardList, FileText, Users, Calendar, Folder, Bell, Settings };
const iconComponent = computed(() => icons[props.icon] ?? FileText);

const colorMap: Record<string, { bg: string; icon: string }> = {
  brand:  { bg: 'bg-brand-900/40',  icon: 'text-brand-400' },
  blue:   { bg: 'bg-blue-900/40',   icon: 'text-blue-400' },
  green:  { bg: 'bg-green-900/40',  icon: 'text-green-400' },
  yellow: { bg: 'bg-yellow-900/40', icon: 'text-yellow-400' },
  purple: { bg: 'bg-purple-900/40', icon: 'text-purple-400' },
  orange: { bg: 'bg-orange-900/40', icon: 'text-orange-400' },
  pink:   { bg: 'bg-pink-900/40',   icon: 'text-pink-400' },
};

const bgClass = computed(() => colorMap[props.color ?? 'brand']?.bg ?? colorMap.brand.bg);
const iconClass = computed(() => colorMap[props.color ?? 'brand']?.icon ?? colorMap.brand.icon);
</script>
