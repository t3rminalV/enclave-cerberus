<template>
  <Head :title="`Notifications — ${event.name}`" />
  <AppLayout>
    <div class="page-header">
      <div>
        <div class="flex items-center gap-2 text-sm text-surface-400 mb-1">
          <Link :href="route('admin.events.show', event.id)" class="hover:text-surface-200">{{ event.name }}</Link>
          <ChevronRight class="w-3 h-3" />
        </div>
        <h1 class="page-title">Send Notification</h1>
        <p class="page-subtitle">Send Discord DMs to volunteers</p>
      </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
      <!-- Compose -->
      <div class="card card-body">
        <h2 class="font-semibold text-white mb-4">Compose Message</h2>
        <form @submit.prevent="send" class="space-y-4">
          <div>
            <label class="label">Recipients</label>
            <select v-model="form.recipient_type" class="select">
              <option value="all">All Applicants</option>
              <option value="submitted">Submitted / Under Review</option>
              <option value="accepted">Accepted Volunteers</option>
              <option value="team">Specific Team</option>
            </select>
          </div>
          <div v-if="form.recipient_type === 'team'">
            <label class="label">Team</label>
            <select v-model="form.team_id" class="select" required>
              <option value="">Select team…</option>
              <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div v-if="templates.length">
            <label class="label">Load Template</label>
            <select :value="selectedTemplateId" @change="applyTemplate(($event.target as HTMLSelectElement).value)" class="select">
              <option value="">— Choose a template —</option>
              <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Subject / Title</label>
            <input v-model="form.subject" class="input" required placeholder="Important update about your volunteer role" />
          </div>
          <div>
            <label class="label">Message</label>
            <textarea v-model="form.message" class="textarea" rows="5" required placeholder="Write your message here…" maxlength="2000" />
            <p class="form-hint">{{ form.message.length }}/2000 characters · Variables: <code>{{ '{{name}}' }}</code>, <code>{{ '{{first_name}}' }}</code>, <code>{{ '{{event}}' }}</code>, <code>{{ '{{team}}' }}</code></p>
          </div>
          <div class="flex gap-2">
            <button type="submit" :disabled="sending" class="btn-primary flex-1">
              <Bell class="w-4 h-4" /> {{ sending ? 'Sending…' : 'Send via Discord' }}
            </button>
            <button type="button" @click="showSaveTemplate = !showSaveTemplate" class="btn-secondary" :disabled="!form.subject || !form.message">
              <Save class="w-4 h-4" /> Save as Template
            </button>
          </div>
          <div v-if="showSaveTemplate" class="border-t border-surface-700/60 pt-3">
            <label class="label">Template Name</label>
            <div class="flex gap-2">
              <input v-model="newTemplateName" class="input flex-1" placeholder="e.g. Pre-event briefing" />
              <button type="button" @click="saveTemplate" :disabled="!newTemplateName" class="btn-secondary">Save</button>
            </div>
          </div>
        </form>
      </div>

      <!-- Templates -->
      <div v-if="templates.length" class="card lg:col-span-2">
        <div class="card-header flex items-center justify-between">
          <h2 class="font-semibold text-white">Saved Templates</h2>
          <span class="text-xs text-surface-400">{{ templates.length }} saved</span>
        </div>
        <div class="divide-y divide-surface-700/40">
          <div v-for="t in templates" :key="t.id" class="px-6 py-3 flex items-center gap-3">
            <div class="flex-1 min-w-0">
              <p class="text-sm text-surface-200 truncate font-medium">{{ t.name }}</p>
              <p class="text-xs text-surface-400 truncate">{{ t.subject }}</p>
            </div>
            <button @click="applyTemplate(t.id)" class="btn-ghost btn-sm">Use</button>
            <button @click="deleteTemplate(t)" class="btn-ghost btn-sm text-red-400 hover:text-red-300">Delete</button>
          </div>
        </div>
      </div>

      <!-- Recent log -->
      <div class="card">
        <div class="card-header">
          <h2 class="font-semibold text-white">Recent Notifications</h2>
        </div>
        <div class="divide-y divide-surface-700/40 max-h-96 overflow-y-auto">
          <div v-for="log in recentLogs" :key="log.id" class="px-6 py-3 flex items-center gap-3">
            <img :src="log.user?.avatar_url" class="w-7 h-7 rounded-full bg-surface-700 shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="text-sm text-surface-200 truncate">{{ log.user?.name }}</p>
              <p class="text-xs text-surface-400 truncate">{{ log.type }}</p>
            </div>
            <span :class="log.delivered ? 'badge-green' : 'badge-red'" class="text-xs shrink-0">
              {{ log.delivered ? 'Sent' : 'Failed' }}
            </span>
          </div>
          <div v-if="!recentLogs.length" class="px-6 py-8 text-center text-surface-400 text-sm">
            No notifications sent yet.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Bell, Save } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';

const props = defineProps<{
  event: any;
  teams: any[];
  recentLogs: any[];
  templates: Array<{ id: number; name: string; subject: string; body: string }>;
}>();

const sending = ref(false);
const form = ref({ recipient_type: 'accepted', team_id: '', subject: '', message: '' });
const selectedTemplateId = ref('');
const showSaveTemplate = ref(false);
const newTemplateName = ref('');

function send() {
  sending.value = true;
  router.post(route('admin.events.notifications.bulk', props.event.id), form.value, {
    onSuccess: () => { form.value.subject = ''; form.value.message = ''; selectedTemplateId.value = ''; },
    onFinish: () => { sending.value = false; },
  });
}

function applyTemplate(id: string | number) {
  const t = props.templates.find(x => x.id === Number(id));
  if (!t) return;
  form.value.subject = t.subject;
  form.value.message = t.body;
  selectedTemplateId.value = String(t.id);
}

function saveTemplate() {
  router.post(route('admin.message-templates.store'), {
    name: newTemplateName.value,
    subject: form.value.subject,
    body: form.value.message,
  }, {
    onSuccess: () => { showSaveTemplate.value = false; newTemplateName.value = ''; },
  });
}

function deleteTemplate(t: { id: number; name: string }) {
  if (!confirm(`Delete template "${t.name}"?`)) return;
  router.delete(route('admin.message-templates.destroy', t.id));
}
</script>
