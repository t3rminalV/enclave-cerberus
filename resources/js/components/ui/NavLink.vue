<template>
  <Link :href="href" :class="['nav-link', active && 'nav-link-active']" v-bind="$attrs">
    <slot />
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
defineProps<{ href: string; active?: boolean }>();
</script>
