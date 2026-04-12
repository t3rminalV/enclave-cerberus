<template>
  <Head :title="`${application.user.name} — Application`" />
  <AppLayout>
    <div class="page-header">
      <div>
        <div class="flex items-center gap-2 text-sm text-surface-400 mb-1">
          <Link :href="route('admin.events.applications.index', event.id)" class="hover:text-surface-200">Applications</Link>
          <ChevronRight class="w-3 h-3" />
        </div>
        <h1 class="page-title flex items-center gap-3">
          {{ application.user.name }}
          <ApplicationStatusBadge :status="application.status" />
        </h1>
        <p class="page-subtitle">@{{ application.user.discord_username }} · {{ event.name }}</p>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
      <!-- Left: form responses -->
      <div class="lg:col-span-2 space-y-4">
        <div v-for="stage in [1, 2]" :key="stage">
          <div v-if="getFormForStage(stage)" class="card">
            <div class="card-header">
              <h2 class="font-semibold text-white">Stage {{ stage }} Responses</h2>
            </div>
            <div class="divide-y divide-surface-700/40">
              <div v-for="field in getFormForStage(stage).fields" :key="field.id" class="px-6 py-4">
                <p class="text-xs font-semibold text-surface-400 uppercase tracking-wide mb-1">{{ field.label }}</p>
                <template v-if="field.type === 'file' || field.type === 'image'">
                  <div v-for="file in getFilesForField(field.id)" :key="file.id" class="flex items-center gap-2 text-sm text-brand-300">
                    <Paperclip class="w-3.5 h-3.5" />
                    <span>{{ file.original_filename }}</span>
                    <span class="text-surface-500">({{ file.size_formatted }})</span>
                  </div>
                  <p v-if="!getFilesForField(field.id).length" class="text-surface-500 text-sm">No file uploaded</p>
                </template>
                <template v-else>
                  <p class="text-sm text-surface-200">{{ getResponseForField(field.id) || '—' }}</p>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- Status History -->
        <div class="card">
          <div class="card-header">
            <h2 class="font-semibold text-white">Status History</h2>
          </div>
          <div class="px-6 py-4">
            <ol class="relative border-l border-surface-700 space-y-4 ml-2">
              <li v-for="entry in application.status_history" :key="entry.id" class="ml-4">
                <div class="absolute -left-1.5 w-3 h-3 rounded-full bg-surface-600 border-2 border-surface-800" />
                <div class="flex items-center gap-2 flex-wrap">
                  <ApplicationStatusBadge :status="entry.to_status" />
                  <span class="text-xs text-surface-400">
                    by {{ entry.changed_by?.name ?? 'System' }} ·
                    {{ format(new Date(entry.created_at), 'dd MMM yyyy HH:mm') }}
                  </span>
                </div>
                <p v-if="entry.note" class="text-xs text-surface-400 mt-1 italic">{{ entry.note }}</p>
              </li>
            </ol>
          </div>
        </div>
      </div>

      <!-- Right: actions -->
      <div class="space-y-4">
        <!-- Change status -->
        <div class="card card-body">
          <h3 class="font-semibold text-white mb-3">Update Status</h3>
          <form @submit.prevent="submitStatus">
            <div class="space-y-3">
              <div>
                <label class="label">New Status</label>
                <select v-model="statusForm.status" class="select">
                  <option value="submitted">Submitted</option>
                  <option value="under_review">Under Review</option>
                  <option value="accepted">Accepted</option>
                  <option value="rejected">Rejected</option>
                  <option value="waitlisted">Waitlisted</option>
                </select>
              </div>
              <div>
                <label class="label">Note <span class="text-surface-500 font-normal">(optional, visible in history)</span></label>
                <textarea v-model="statusForm.note" class="textarea" rows="2" placeholder="Reason or comment…" />
              </div>
              <button type="submit" :disabled="statusForm.processing" class="btn-primary w-full">
                Update Status
              </button>
            </div>
          </form>
        </div>

        <!-- Tags -->
        <div class="card card-body">
          <h3 class="font-semibold text-white mb-3">Tags</h3>
          <div class="flex flex-wrap gap-1.5 mb-3">
            <span
              v-for="tag in tags"
              :key="tag.id"
              @click="toggleTag(tag.id)"
              class="badge cursor-pointer transition-all"
              :style="selectedTagIds.includes(tag.id)
                ? { background: tag.color + '33', color: tag.color, borderColor: tag.color + '55' }
                : { background: 'var(--color-surface-700)', color: 'var(--color-surface-400)', borderColor: 'var(--color-surface-600)' }"
            >
              {{ tag.name }}
            </span>
          </div>
          <button @click="saveTags" class="btn-secondary btn-sm w-full">Save Tags</button>
        </div>

        <!-- Admin Notes -->
        <div class="card card-body">
          <h3 class="font-semibold text-white mb-3">Admin Notes <span class="text-surface-500 font-normal text-xs">(private)</span></h3>
          <textarea v-model="notes" class="textarea" rows="4" placeholder="Internal notes…" />
          <button @click="saveNotes" class="btn-secondary btn-sm mt-2 w-full">Save Notes</button>
        </div>

        <!-- Volunteer profile -->
        <div class="card card-body">
          <h3 class="font-semibold text-white mb-3">Volunteer Profile</h3>
          <dl class="space-y-2 text-sm">
            <div v-if="application.user.phone">
              <dt class="text-surface-400 text-xs">Phone</dt>
              <dd class="text-surface-200">{{ application.user.phone }}</dd>
            </div>
            <div v-if="application.user.tshirt_size">
              <dt class="text-surface-400 text-xs">T-Shirt</dt>
              <dd class="text-surface-200">{{ application.user.tshirt_size }}</dd>
            </div>
            <div v-if="application.user.dietary_requirements">
              <dt class="text-surface-400 text-xs">Dietary</dt>
              <dd class="text-surface-200">{{ application.user.dietary_requirements }}</dd>
            </div>
            <div v-if="application.user.emergency_contact_name">
              <dt class="text-surface-400 text-xs">Emergency Contact</dt>
              <dd class="text-surface-200">{{ application.user.emergency_contact_name }} · {{ application.user.emergency_contact_phone }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ChevronRight, Paperclip } from 'lucide-vue-next';
import { format } from 'date-fns';
import AppLayout from '@/components/layout/AppLayout.vue';
import ApplicationStatusBadge from '@/components/ui/ApplicationStatusBadge.vue';

const props = defineProps<{
  event: any;
  application: any;
  tags: any[];
}>();

const statusForm = useForm({ status: props.application.status, note: '' });
const notes = ref(props.application.admin_notes ?? '');
const selectedTagIds = ref<number[]>(props.application.tags.map((t: any) => t.id));

function getFormForStage(stage: number) {
  return props.application.event.forms?.find((f: any) => f.stage == stage);
}

function getResponseForField(fieldId: number) {
  return props.application.responses?.find((r: any) => r.form_field_id === fieldId)?.value ?? null;
}

function getFilesForField(fieldId: number) {
  return props.application.files?.filter((f: any) => f.form_field_id === fieldId) ?? [];
}

function submitStatus() {
  statusForm.patch(route('admin.events.applications.status', [props.event.id, props.application.id]));
}

function saveNotes() {
  router.patch(route('admin.events.applications.notes', [props.event.id, props.application.id]), { admin_notes: notes.value });
}

function toggleTag(id: number) {
  const idx = selectedTagIds.value.indexOf(id);
  if (idx >= 0) selectedTagIds.value.splice(idx, 1);
  else selectedTagIds.value.push(id);
}

function saveTags() {
  router.post(route('admin.events.applications.tags', [props.event.id, props.application.id]), { tag_ids: selectedTagIds.value });
}
</script>
