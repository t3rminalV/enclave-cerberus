<template>
  <Head :title="`Rota — ${event.name}`" />
  <AppLayout>
    <div class="page-header">
      <div>
        <div class="flex items-center gap-2 text-sm text-surface-400 mb-1">
          <Link :href="route('admin.events.show', event.id)" class="hover:text-surface-200">{{ event.name }}</Link>
          <ChevronRight class="w-3 h-3" />
        </div>
        <h1 class="page-title">Rota</h1>
        <p class="page-subtitle">
          <span v-if="event.rota_publication?.is_live" class="text-green-400">● Published</span>
          <span v-else class="text-yellow-400">● Draft — not visible to volunteers</span>
        </p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <a :href="route('admin.events.rota.export', event.id)" class="btn-secondary btn-sm">
          <Download class="w-3.5 h-3.5" /> Export CSV
        </a>
        <button @click="generateRota" :disabled="generating" class="btn-secondary">
          <Wand2 class="w-4 h-4" /> {{ generating ? 'Generating…' : 'Auto-Generate' }}
        </button>
        <button @click="togglePublish" :disabled="publishing" :class="event.rota_publication?.is_live ? 'btn-secondary' : 'btn-primary'">
          <template v-if="event.rota_publication?.is_live">
            <EyeOff class="w-4 h-4" /> Unpublish
          </template>
          <template v-else>
            <Eye class="w-4 h-4" /> Publish & Notify
          </template>
        </button>
      </div>
    </div>

    <div class="grid lg:grid-cols-4 gap-6">
      <!-- Left sidebar: controls -->
      <div class="space-y-4">
        <!-- Rules -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-white text-sm">Generation Rules</h3>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="label text-xs">Min Shift (mins)</label>
              <input v-model.number="rules.min_shift_minutes" type="number" class="input" min="30" />
            </div>
            <div>
              <label class="label text-xs">Max Shift (mins)</label>
              <input v-model.number="rules.max_shift_minutes" type="number" class="input" min="30" />
            </div>
            <div>
              <label class="label text-xs">Min Rest Between (mins)</label>
              <input v-model.number="rules.min_rest_minutes" type="number" class="input" min="0" />
            </div>
            <div>
              <label class="label text-xs">Max Shifts / Volunteer</label>
              <input v-model.number="rules.max_shifts_per_volunteer" type="number" class="input" min="1" />
            </div>
            <button @click="saveRules" class="btn-secondary btn-sm w-full">Save Rules</button>
          </div>
        </div>

        <!-- Add Shift -->
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold text-white text-sm">Add Shift</h3>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="label text-xs">Team</label>
              <select v-model="newShift.team_id" class="select">
                <option value="">Select team…</option>
                <option v-for="team in event.teams" :key="team.id" :value="team.id">{{ team.name }}</option>
              </select>
            </div>
            <div>
              <label class="label text-xs">Shift Name</label>
              <input v-model="newShift.name" class="input" placeholder="Morning shift" />
            </div>
            <div>
              <label class="label text-xs">Start</label>
              <input v-model="newShift.starts_at" type="datetime-local" class="input" />
            </div>
            <div>
              <label class="label text-xs">End</label>
              <input v-model="newShift.ends_at" type="datetime-local" class="input" />
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="label text-xs">Min Volunteers</label>
                <input v-model.number="newShift.min_volunteers" type="number" class="input" min="0" />
              </div>
              <div>
                <label class="label text-xs">Max Volunteers</label>
                <input v-model.number="newShift.max_volunteers" type="number" class="input" min="1" placeholder="∞" />
              </div>
            </div>
            <button @click="addShift" class="btn-primary btn-sm w-full">
              <Plus class="w-3.5 h-3.5" /> Add Shift
            </button>
          </div>
        </div>
      </div>

      <!-- Right: rota grid -->
      <div class="lg:col-span-3">
        <!-- Team legend -->
        <div class="flex flex-wrap gap-2 mb-4">
          <span v-for="team in event.teams" :key="team.id" class="flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full" :style="{ background: team.color + '22', color: team.color, border: `1px solid ${team.color}44` }">
            <span class="w-2 h-2 rounded-full" :style="{ background: team.color }"></span>
            {{ team.name }}
          </span>
        </div>

        <!-- Group by team -->
        <div v-if="groupedShifts.length === 0" class="card card-body text-center text-surface-400 py-12">
          <Calendar class="w-8 h-8 mx-auto mb-2 opacity-30" />
          <p>No shifts yet. Add shifts using the panel on the left.</p>
        </div>

        <div v-for="group in groupedShifts" :key="group.team.id" class="card mb-4">
          <div class="card-header flex items-center gap-3">
            <span class="w-3 h-3 rounded-full" :style="{ background: group.team.color }"></span>
            <h3 class="font-semibold text-white">{{ group.team.name }}</h3>
            <span class="text-xs text-surface-400">{{ group.shifts.length }} shifts</span>
          </div>
          <div class="divide-y divide-surface-700/40">
            <div v-for="shift in group.shifts" :key="shift.id" class="px-6 py-4">
              <div class="flex items-start justify-between gap-3 flex-wrap mb-3">
                <div>
                  <p class="font-medium text-surface-100">{{ shift.name }}</p>
                  <p class="text-xs text-surface-400 mt-0.5">
                    {{ formatDT(shift.starts_at) }} → {{ formatDT(shift.ends_at) }}
                    ({{ durationHours(shift) }}h)
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-surface-400">
                    {{ shift.assignments.length }}/{{ shift.max_volunteers ?? '∞' }} volunteers
                    <span v-if="shift.assignments.length < shift.min_volunteers" class="text-yellow-400 ml-1">
                      (needs {{ shift.min_volunteers - shift.assignments.length }} more)
                    </span>
                  </span>
                  <button @click="editingShift = shift" class="btn-ghost btn-icon">
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                  <button @click="deleteShift(shift)" class="btn-ghost btn-icon text-red-400">
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>

              <!-- Assigned volunteers -->
              <div class="flex flex-wrap gap-2 mb-2">
                <div v-for="assignment in shift.assignments" :key="assignment.id" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-700 text-xs text-surface-200">
                  <img :src="assignment.user.avatar_url" class="w-4 h-4 rounded-full" />
                  {{ assignment.user.name }}
                  <span v-if="assignment.is_manual" class="text-yellow-400" title="Manual override">✦</span>
                  <button @click="removeAssignment(shift, assignment.user.id)" class="ml-0.5 text-surface-500 hover:text-red-400">
                    <X class="w-3 h-3" />
                  </button>
                </div>

                <!-- Add volunteer dropdown -->
                <select @change="assignVolunteer(shift.id, $event)" class="text-xs px-2.5 py-1 rounded-full bg-surface-700 text-surface-400 border border-surface-600 cursor-pointer">
                  <option value="">+ Assign volunteer</option>
                  <option
                    v-for="vol in getUnassignedVolunteers(shift)"
                    :key="vol.id"
                    :value="vol.id"
                  >
                    {{ vol.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Download, Wand2, Eye, EyeOff, Plus, Calendar, Pencil, Trash2, X } from 'lucide-vue-next';
import { format } from 'date-fns';
import AppLayout from '@/components/layout/AppLayout.vue';

const props = defineProps<{
  event: any;
  acceptedVolunteers: any[];
}>();

const generating = ref(false);
const publishing = ref(false);

const rules = ref({
  min_shift_minutes: props.event.rota_rules?.min_shift_minutes ?? 120,
  max_shift_minutes: props.event.rota_rules?.max_shift_minutes ?? 480,
  min_rest_minutes: props.event.rota_rules?.min_rest_minutes ?? 480,
  max_shifts_per_volunteer: props.event.rota_rules?.max_shifts_per_volunteer ?? 3,
});

const newShift = ref({
  team_id: '',
  name: '',
  starts_at: '',
  ends_at: '',
  min_volunteers: 1,
  max_volunteers: null as number | null,
});

const editingShift = ref<any>(null);

const groupedShifts = computed(() => {
  return props.event.teams.map((team: any) => ({
    team,
    shifts: props.event.shifts.filter((s: any) => s.team_id === team.id).sort((a: any, b: any) => new Date(a.starts_at).getTime() - new Date(b.starts_at).getTime()),
  })).filter((g: any) => g.shifts.length > 0);
});

function formatDT(dt: string) {
  return format(new Date(dt), 'EEE dd MMM HH:mm');
}

function durationHours(shift: any) {
  return ((new Date(shift.ends_at).getTime() - new Date(shift.starts_at).getTime()) / 3600000).toFixed(1);
}

function getUnassignedVolunteers(shift: any) {
  const assignedIds = shift.assignments.map((a: any) => a.user_id);
  return props.acceptedVolunteers.filter(v => !assignedIds.includes(v.id));
}

function saveRules() {
  router.post(route('admin.events.rota.rules', props.event.id), rules.value);
}

function addShift() {
  router.post(route('admin.events.rota.shifts.store', props.event.id), newShift.value, {
    onSuccess: () => {
      newShift.value = { team_id: '', name: '', starts_at: '', ends_at: '', min_volunteers: 1, max_volunteers: null };
    },
  });
}

function deleteShift(shift: any) {
  if (!confirm(`Delete shift "${shift.name}"?`)) return;
  router.delete(route('admin.events.rota.shifts.destroy', [props.event.id, shift.id]));
}

function assignVolunteer(shiftId: number, event: Event) {
  const userId = (event.target as HTMLSelectElement).value;
  if (!userId) return;
  (event.target as HTMLSelectElement).value = '';
  router.post(route('admin.events.rota.assign', props.event.id), { shift_id: shiftId, user_id: Number(userId) });
}

function removeAssignment(shift: any, userId: number) {
  router.delete(route('admin.events.rota.unassign', [props.event.id, shift.id, userId]));
}

function generateRota() {
  if (!confirm('This will replace all auto-generated assignments. Manually assigned volunteers will be kept. Continue?')) return;
  generating.value = true;
  router.post(route('admin.events.rota.generate', props.event.id), {}, {
    onFinish: () => { generating.value = false; },
  });
}

function togglePublish() {
  const isLive = props.event.rota_publication?.is_live ?? false;
  const msg = isLive
    ? 'Unpublish the rota? Volunteers will no longer be able to see it.'
    : 'Publish the rota and notify all accepted volunteers via Discord?';
  if (!confirm(msg)) return;
  publishing.value = true;
  router.post(route('admin.events.rota.publish', props.event.id), { is_live: !isLive }, {
    onFinish: () => { publishing.value = false; },
  });
}
</script>
