<template>
  <Head :title="`Documents — ${event.name}`" />
  <AppLayout>
    <div class="page-header">
      <div>
        <div class="flex items-center gap-2 text-sm text-surface-400 mb-1">
          <Link :href="route('admin.events.show', event.id)" class="hover:text-surface-200">{{ event.name }}</Link>
          <ChevronRight class="w-3 h-3" />
        </div>
        <h1 class="page-title">Documents</h1>
      </div>
      <button @click="showUpload = true" class="btn-primary">
        <Upload class="w-4 h-4" /> Upload Document
      </button>
    </div>

    <!-- Documents grid -->
    <div v-if="!documents.length" class="card card-body text-center py-12 text-surface-400">
      <FolderOpen class="w-8 h-8 mx-auto mb-2 opacity-30" />
      <p>No documents yet.</p>
    </div>

    <div class="card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Document</th>
              <th>Visibility</th>
              <th>Uploaded By</th>
              <th>Date</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in documents" :key="doc.id">
              <td>
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-surface-700 flex items-center justify-center shrink-0">
                    <FileText class="w-4 h-4 text-surface-400" />
                  </div>
                  <div>
                    <p class="font-medium text-surface-100 text-sm">{{ doc.title }}</p>
                    <p class="text-xs text-surface-400">{{ doc.original_filename }} · {{ doc.size_formatted }}</p>
                  </div>
                </div>
              </td>
              <td>
                <span :class="visibilityBadge(doc.visibility)">{{ visibilityLabel(doc.visibility) }}</span>
              </td>
              <td class="text-surface-300 text-sm">{{ doc.uploader?.name }}</td>
              <td class="text-surface-400 text-xs whitespace-nowrap">{{ formatDate(doc.created_at) }}</td>
              <td>
                <div class="flex items-center gap-1 justify-end">
                  <a :href="route('admin.events.documents.download', [event.id, doc.id])" class="btn-ghost btn-sm">
                    <Download class="w-3.5 h-3.5" />
                  </a>
                  <button @click="deleteDoc(doc)" class="btn-ghost btn-icon text-red-400">
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Upload Modal -->
    <Modal :show="showUpload" @close="showUpload = false" size="lg">
      <div class="card-body">
        <h2 class="font-semibold text-white text-lg mb-4">Upload Document</h2>
        <form @submit.prevent="upload" class="space-y-4" enctype="multipart/form-data">
          <div>
            <label class="label">Title</label>
            <input v-model="uploadForm.title" class="input" required placeholder="Document title" />
          </div>
          <div>
            <label class="label">Description <span class="text-surface-500 font-normal">(optional)</span></label>
            <textarea v-model="uploadForm.description" class="textarea" rows="2" />
          </div>
          <div>
            <label class="label">Visibility</label>
            <select v-model="uploadForm.visibility" class="select">
              <option value="accepted_only">Accepted Volunteers Only</option>
              <option value="all_volunteers">All Applicants</option>
              <option value="specific_teams">Specific Teams Only</option>
            </select>
          </div>
          <div v-if="uploadForm.visibility === 'specific_teams'">
            <label class="label">Teams</label>
            <div class="space-y-2">
              <label v-for="team in teams" :key="team.id" class="flex items-center gap-2 text-sm text-surface-200 cursor-pointer">
                <input type="checkbox" :value="team.id" v-model="uploadForm.team_ids" class="rounded" />
                <span class="w-3 h-3 rounded-full" :style="{ background: team.color }"></span>
                {{ team.name }}
              </label>
            </div>
          </div>
          <div>
            <label class="label">File</label>
            <input type="file" @change="(e) => uploadForm.file = (e.target as HTMLInputElement).files?.[0] ?? null" class="input" required />
          </div>
          <label class="flex items-center gap-2 text-sm text-surface-300 cursor-pointer">
            <input type="checkbox" v-model="uploadForm.notify" class="rounded" />
            Notify eligible volunteers via Discord DM
          </label>
          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="uploading" class="btn-primary">{{ uploading ? 'Uploading…' : 'Upload' }}</button>
            <button type="button" @click="showUpload = false" class="btn-ghost">Cancel</button>
          </div>
        </form>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Upload, FolderOpen, FileText, Download, Trash2 } from 'lucide-vue-next';
import { format } from 'date-fns';
import AppLayout from '@/components/layout/AppLayout.vue';
import Modal from '@/components/ui/Modal.vue';

const props = defineProps<{ event: any; documents: any[]; teams: any[] }>();

const showUpload = ref(false);
const uploading = ref(false);
const uploadForm = ref({ title: '', description: '', visibility: 'accepted_only', team_ids: [] as number[], file: null as File | null, notify: false });

function formatDate(d: string) { return format(new Date(d), 'dd MMM yyyy'); }

function visibilityLabel(v: string) {
  return { all_volunteers: 'All Applicants', accepted_only: 'Accepted Only', specific_teams: 'Specific Teams' }[v] ?? v;
}

function visibilityBadge(v: string) {
  return { all_volunteers: 'badge-blue', accepted_only: 'badge-green', specific_teams: 'badge-purple' }[v] ?? 'badge-gray';
}

function upload() {
  if (!uploadForm.value.file) return;
  const fd = new FormData();
  fd.append('title', uploadForm.value.title);
  fd.append('description', uploadForm.value.description);
  fd.append('visibility', uploadForm.value.visibility);
  fd.append('file', uploadForm.value.file);
  fd.append('notify', uploadForm.value.notify ? '1' : '0');
  uploadForm.value.team_ids.forEach(id => fd.append('team_ids[]', String(id)));

  uploading.value = true;
  router.post(route('admin.events.documents.store', props.event.id), fd, {
    forceFormData: true,
    onSuccess: () => { showUpload.value = false; uploadForm.value = { title: '', description: '', visibility: 'accepted_only', team_ids: [], file: null, notify: false }; },
    onFinish: () => { uploading.value = false; },
  });
}

function deleteDoc(doc: any) {
  if (!confirm(`Delete "${doc.title}"?`)) return;
  router.delete(route('admin.events.documents.destroy', [props.event.id, doc.id]));
}
</script>
