<template>
  <span :class="`status-${status}`">{{ label }}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ status: string }>();

const labels: Record<string, string> = {
  draft: 'Draft', submitted: 'Submitted', under_review: 'Under Review',
  accepted: 'Accepted', rejected: 'Rejected', waitlisted: 'Waitlisted', withdrawn: 'Withdrawn',
};

const label = computed(() => labels[props.status] ?? props.status);
</script>
