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
          <div>
            <label class="label">Subject / Title</label>
            <input v-model="form.subject" class="input" required placeholder="Important update about your volunteer role" />
          </div>
          <div>
            <label class="label">Message</label>
            <textarea v-model="form.message" class="textarea" rows="5" required placeholder="Write your message here…" maxlength="2000" />
            <p class="form-hint">{{ form.message.length }}/2000 characters</p>
          </div>
          <button type="submit" :disabled="sending" class="btn-primary w-full">
            <Bell class="w-4 h-4" /> {{ sending ? 'Sending…' : 'Send via Discord' }}
          </button>
        </form>
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
import { ChevronRight, Bell } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';

const props = defineProps<{ event: any; teams: any[]; recentLogs: any[] }>();

const sending = ref(false);
const form = ref({ recipient_type: 'accepted', team_id: '', subject: '', message: '' });

function send() {
  sending.value = true;
  router.post(route('admin.events.notifications.bulk', props.event.id), form.value, {
    onSuccess: () => { form.value.subject = ''; form.value.message = ''; },
    onFinish: () => { sending.value = false; },
  });
}
</script>
