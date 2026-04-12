<template>
  <div class="min-h-screen flex items-center justify-center bg-surface-950 p-4">
    <div class="text-center max-w-md">
      <p class="text-8xl font-black text-surface-800 mb-4">{{ status }}</p>
      <h1 class="text-2xl font-bold text-white mb-2">{{ title }}</h1>
      <p class="text-surface-400 mb-8">{{ description }}</p>
      <Link href="/" class="btn-primary">Go Home</Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{ status: number }>();

const messages: Record<number, { title: string; description: string }> = {
  403: { title: 'Forbidden', description: "You don't have permission to access this page." },
  404: { title: 'Page Not Found', description: "We couldn't find what you were looking for." },
  500: { title: 'Server Error', description: "Something went wrong on our end. Please try again later." },
  503: { title: 'Service Unavailable', description: "We're down for maintenance. Be right back." },
};

const title = computed(() => messages[props.status]?.title ?? 'Error');
const description = computed(() => messages[props.status]?.description ?? 'Something went wrong.');
</script>
