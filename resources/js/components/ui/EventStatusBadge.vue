<template>
  <span :class="badgeClass">{{ label }}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ status: string }>();

const map: Record<string, { cls: string; label: string }> = {
  draft:     { cls: 'badge-gray',   label: 'Draft' },
  open:      { cls: 'badge-green',  label: 'Open' },
  closed:    { cls: 'badge-yellow', label: 'Closed' },
  completed: { cls: 'badge-purple', label: 'Completed' },
};

const badgeClass = computed(() => map[props.status]?.cls ?? 'badge-gray');
const label = computed(() => map[props.status]?.label ?? props.status);
</script>
