<template>
  <Head :title="`Documents — ${event.name}`" />
  <AppLayout>
    <div class="page-header">
      <div>
        <Link :href="route('volunteer.dashboard')" class="text-sm text-surface-400 hover:text-surface-200 flex items-center gap-1 mb-2">
          <ChevronLeft class="w-3 h-3" /> Dashboard
        </Link>
        <h1 class="page-title">Documents</h1>
        <p class="page-subtitle">{{ event.name }}</p>
      </div>
    </div>

    <div v-if="!documents.length" class="card card-body text-center py-12 text-surface-400">
      <FolderOpen class="w-8 h-8 mx-auto mb-2 opacity-30" />
      <p>No documents available yet.</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="doc in documents" :key="doc.id" class="card card-body flex flex-col gap-3">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-lg bg-surface-700 flex items-center justify-center shrink-0">
            <component :is="fileIcon(doc.mime_type)" class="w-5 h-5 text-surface-300" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-white text-sm">{{ doc.title }}</p>
            <p v-if="doc.description" class="text-xs text-surface-400 mt-0.5">{{ doc.description }}</p>
            <p class="text-xs text-surface-500 mt-1">{{ doc.size_formatted }} · {{ doc.original_filename }}</p>
          </div>
        </div>
        <a :href="route('volunteer.documents.download', [event.id, doc.id])" class="btn-secondary btn-sm">
          <Download class="w-3.5 h-3.5" /> Download
        </a>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, FolderOpen, Download, FileText, Image, FileCode, File } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';

defineProps<{ event: any; documents: any[] }>();

function fileIcon(mime: string) {
  if (mime.startsWith('image/')) return Image;
  if (mime.includes('pdf')) return FileText;
  if (mime.includes('code') || mime.includes('json')) return FileCode;
  return File;
}
</script>
