<template>
  <Head title="Settings" />
  <AppLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Manage users and application configuration</p>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
      <!-- User Management -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-base font-semibold text-surface-100">User Roles</h2>
          <p class="text-sm text-surface-400 mt-0.5">Assign roles to control access levels</p>
        </div>
        <div>
          <div class="overflow-x-auto">
            <table class="data-table w-full">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Role</th>
                  <th class="w-8"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="u in users" :key="u.id">
                  <td>
                    <div class="flex items-center gap-3">
                      <img :src="u.avatar_url" :alt="u.name" class="w-8 h-8 rounded-full bg-surface-700 shrink-0" />
                      <div class="min-w-0">
                        <p class="text-sm font-medium text-surface-100 truncate">{{ u.name }}</p>
                        <p class="text-xs text-surface-400 truncate">@{{ u.discord_username }}</p>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span :class="roleBadge(u.role)">{{ u.role }}</span>
                  </td>
                  <td>
                    <div class="relative" v-if="u.id !== currentUserId">
                      <select
                        :value="u.role"
                        @change="changeRole(u, ($event.target as HTMLSelectElement).value)"
                        class="select text-xs py-1 px-2 pr-7"
                        :disabled="savingRole === u.id"
                      >
                        <option value="volunteer">volunteer</option>
                        <option value="organiser">organiser</option>
                        <option value="admin">admin</option>
                      </select>
                    </div>
                    <span v-else class="text-xs text-surface-500">you</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- App Settings -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-base font-semibold text-surface-100">Application Settings</h2>
          <p class="text-sm text-surface-400 mt-0.5">Configure portal behaviour and notifications</p>
        </div>
        <div class="card-body">
          <form @submit.prevent="saveSettings" class="space-y-5">
            <div v-for="setting in editableSettings" :key="setting.key">
              <!-- Boolean toggle -->
              <template v-if="setting.type === 'boolean'">
                <label class="flex items-start gap-3 cursor-pointer group">
                  <div class="relative mt-0.5 shrink-0">
                    <input
                      type="checkbox"
                      v-model="form[setting.key]"
                      class="sr-only"
                    />
                    <div
                      class="w-10 h-6 rounded-full transition-colors duration-200"
                      :class="form[setting.key] ? 'bg-brand-600' : 'bg-surface-600'"
                    >
                      <div
                        class="absolute top-1 w-4 h-4 rounded-full bg-white shadow transition-transform duration-200"
                        :class="form[setting.key] ? 'translate-x-5' : 'translate-x-1'"
                      />
                    </div>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-surface-100">{{ setting.label }}</p>
                    <p class="text-xs text-surface-400 mt-0.5">{{ setting.description }}</p>
                  </div>
                </label>
              </template>

              <!-- Integer input -->
              <template v-else-if="setting.type === 'integer'">
                <div>
                  <label class="block text-sm font-medium text-surface-200 mb-1">{{ setting.label }}</label>
                  <p class="text-xs text-surface-400 mb-2">{{ setting.description }}</p>
                  <input type="number" v-model.number="form[setting.key]" class="input w-48" min="0" />
                </div>
              </template>

              <!-- String input -->
              <template v-else>
                <div>
                  <label class="block text-sm font-medium text-surface-200 mb-1">{{ setting.label }}</label>
                  <p class="text-xs text-surface-400 mb-2">{{ setting.description }}</p>
                  <input type="text" v-model="form[setting.key]" class="input" />
                </div>
              </template>
            </div>

            <div class="flex items-center gap-3 pt-2 border-t border-surface-700/60">
              <button type="submit" class="btn-primary" :disabled="settingsSaving">
                <Check v-if="!settingsSaving" class="w-4 h-4" />
                <Loader2 v-else class="w-4 h-4 animate-spin" />
                Save Settings
              </button>
              <span v-if="settingsSaved" class="text-sm text-green-400 flex items-center gap-1">
                <Check class="w-3.5 h-3.5" /> Saved
              </span>
            </div>
          </form>
        </div>
      </div>

      <!-- Integrations -->
      <div class="card xl:col-span-2">
        <div class="card-header">
          <h2 class="text-base font-semibold text-surface-100">Integrations</h2>
          <p class="text-sm text-surface-400 mt-0.5">Connections to external services</p>
        </div>
        <div class="p-6 space-y-4">
          <div class="flex items-center gap-3 flex-wrap">
            <Ticket class="w-5 h-5 text-surface-300 shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-surface-100">TicketTailor</p>
              <p class="text-xs text-surface-400">
                <template v-if="!integrations.tickettailor.configured">
                  Set <code>TICKETTAILOR_API_KEY</code> in your environment to enable auto-issue of volunteer tickets.
                </template>
                <template v-else>API key detected. Test the connection and map each event to a TicketTailor event + volunteer ticket type from the event edit form.</template>
              </p>
            </div>
            <button v-if="integrations.tickettailor.configured" @click="testTicketTailor" :disabled="ttTesting" class="btn-secondary btn-sm">
              <Loader2 v-if="ttTesting" class="w-3.5 h-3.5 animate-spin" />
              <Check v-else-if="ttStatus === 'ok'" class="w-3.5 h-3.5 text-green-400" />
              <AlertCircle v-else-if="ttStatus === 'fail'" class="w-3.5 h-3.5 text-red-400" />
              {{ ttTesting ? 'Testing…' : 'Test Connection' }}
            </button>
          </div>
          <p v-if="ttMessage" :class="ttStatus === 'ok' ? 'text-green-300' : 'text-red-300'" class="text-xs">
            {{ ttMessage }}
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import axios from 'axios';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Check, Loader2, Ticket, AlertCircle } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';

interface UserRow {
  id: number;
  name: string;
  discord_username: string;
  avatar_url: string;
  role: string;
  email: string;
}

interface Setting {
  key: string;
  value: string;
  type: 'boolean' | 'integer' | 'string';
  label: string;
  description: string;
}

const props = defineProps<{
  users: UserRow[];
  settings: Setting[];
  integrations: { tickettailor: { configured: boolean } };
}>();

const ttTesting = ref(false);
const ttStatus = ref<'ok' | 'fail' | null>(null);
const ttMessage = ref<string | null>(null);

async function testTicketTailor() {
  ttTesting.value = true;
  ttStatus.value = null;
  ttMessage.value = null;
  try {
    const { data } = await axios.get(route('admin.tickettailor.ping'));
    ttStatus.value = data.ok ? 'ok' : 'fail';
    ttMessage.value = data.message ?? null;
  } catch (e: any) {
    ttStatus.value = 'fail';
    ttMessage.value = e.response?.data?.message ?? e.message ?? 'Connection failed.';
  } finally {
    ttTesting.value = false;
  }
}

const page = usePage();
const currentUserId = page.props.auth.user?.id;

// Role management
const savingRole = ref<number | null>(null);

function roleBadge(role: string) {
  return {
    'badge-green': role === 'admin',
    'badge-blue': role === 'organiser',
    'badge-gray': role === 'volunteer',
  };
}

function changeRole(user: UserRow, newRole: string) {
  savingRole.value = user.id;
  router.patch(
    route('admin.settings.user-role', { user: user.id }),
    { role: newRole },
    {
      preserveScroll: true,
      onFinish: () => { savingRole.value = null; },
    }
  );
}

// App settings
const editableSettings = props.settings;

// Build reactive form from current values
const form = reactive<Record<string, boolean | number | string>>({});
for (const s of props.settings) {
  if (s.type === 'boolean') {
    form[s.key] = s.value === 'true';
  } else if (s.type === 'integer') {
    form[s.key] = parseInt(s.value, 10);
  } else {
    form[s.key] = s.value;
  }
}

const settingsSaving = ref(false);
const settingsSaved = ref(false);

function saveSettings() {
  settingsSaving.value = true;
  settingsSaved.value = false;

  const payload = Object.entries(form).map(([key, value]) => ({
    key,
    value: typeof value === 'boolean' ? (value ? 'true' : 'false') : String(value),
  }));

  router.post(
    route('admin.settings.app'),
    { settings: payload },
    {
      preserveScroll: true,
      onSuccess: () => {
        settingsSaved.value = true;
        setTimeout(() => { settingsSaved.value = false; }, 3000);
      },
      onFinish: () => { settingsSaving.value = false; },
    }
  );
}
</script>
