<template>
  <Head title="Changelog" />
  <AppLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">Changelog</h1>
        <p class="page-subtitle">Cerberus v{{ version }}</p>
      </div>
    </div>

    <div class="max-w-3xl space-y-6">
      <section v-for="(release, index) in releases" :key="release.version" class="card card-body">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-white">v{{ release.version }}</h2>
            <p class="text-sm text-surface-400 mt-1">{{ release.date }}</p>
          </div>
          <span v-if="index === 0" class="badge-blue">Current</span>
        </div>

        <div class="divider" />

        <div class="space-y-5">
          <div v-for="section in releaseSections(release)" :key="section.title">
            <h3 class="text-sm font-semibold text-surface-100 mb-2">{{ section.title }}</h3>
            <ul class="space-y-2 text-sm text-surface-300">
              <li v-for="item in section.items" :key="item">{{ item }}</li>
            </ul>
          </div>
        </div>
      </section>

      <section v-if="!releases.length" class="card card-body">
        <h2 class="text-lg font-semibold text-white">No release notes yet</h2>
        <p class="text-sm text-surface-400 mt-1">Run the release command to generate changelog entries from Conventional Commits.</p>
      </section>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/components/layout/AppLayout.vue';

defineProps<{
  version: string;
  releases: Array<{
    version: string;
    date: string;
    sections: Record<string, string[]>;
  }>;
}>();

function releaseSections(release: { sections: Record<string, string[]> }) {
  return Object.entries(release.sections)
    .filter(([, items]) => items.length > 0)
    .map(([title, items]) => ({ title, items }));
}
</script>
