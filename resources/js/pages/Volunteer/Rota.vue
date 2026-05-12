<template>
  <Head :title="`Rota — ${event.name}`" />
  <AppLayout>
    <div class="page-header">
      <div>
        <Link :href="route('volunteer.dashboard')" class="text-sm text-surface-400 hover:text-surface-200 flex items-center gap-1 mb-2">
          <ChevronLeft class="w-3 h-3" /> Dashboard
        </Link>
        <h1 class="page-title">{{ event.name }} — Rota</h1>
        <p class="page-subtitle">{{ formatDate(event.starts_at) }} – {{ formatDate(event.ends_at) }}</p>
      </div>
    </div>

    <!-- Calendar subscription -->
    <details class="card card-body mb-6">
      <summary class="flex items-center justify-between cursor-pointer">
        <span class="flex items-center gap-2 text-sm font-semibold text-surface-200">
          <Calendar class="w-4 h-4" /> Subscribe in your calendar app
        </span>
        <span class="text-xs text-surface-400">Google · Apple · Outlook</span>
      </summary>
      <div class="mt-4 space-y-3">
        <p class="text-sm text-surface-300">
          Paste this URL into Google Calendar (<em>Other calendars → From URL</em>), Apple Calendar (<em>File → New Calendar Subscription</em>), or Outlook (<em>Add calendar → Subscribe from web</em>). Your assigned shifts will sync automatically.
        </p>
        <div class="flex gap-2">
          <input :value="calendarFeedUrl" readonly class="input flex-1 font-mono text-xs" @focus="($event.target as HTMLInputElement).select()" />
          <button @click="copyFeedUrl" class="btn-secondary shrink-0">
            <component :is="copied ? Check : Copy" class="w-3.5 h-3.5" />
            {{ copied ? 'Copied' : 'Copy' }}
          </button>
        </div>
        <p class="text-xs text-surface-400">Keep this link private — anyone with it can read your shift schedule.</p>
      </div>
    </details>

    <!-- My shifts highlight -->
    <div v-if="myShifts.length" class="mb-6">
      <h2 class="text-sm font-semibold text-surface-300 uppercase tracking-wide mb-3">Your Shifts</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="shift in myShifts" :key="shift.id" class="card card-body border-brand-500/30 bg-brand-900/10">
          <div class="flex items-center gap-2 mb-1">
            <span class="w-2.5 h-2.5 rounded-full" :style="{ background: shift.team.color }"></span>
            <span class="text-xs text-surface-400 font-medium">{{ shift.team.name }}</span>
          </div>
          <p class="font-semibold text-white">{{ shift.name }}</p>
          <p class="text-sm text-surface-300 mt-1">{{ formatDT(shift.starts_at) }}</p>
          <p class="text-xs text-surface-400">{{ durationHours(shift) }} hours</p>
        </div>
      </div>
    </div>

    <!-- Full rota by team -->
    <div class="space-y-6">
      <div v-for="team in event.teams" :key="team.id">
        <div v-if="shiftsForTeam(team.id).length">
          <div class="flex items-center gap-3 mb-3">
            <span class="w-3 h-3 rounded-full" :style="{ background: team.color }"></span>
            <h2 class="font-semibold text-white">{{ team.name }}</h2>
          </div>
          <div class="card">
            <div class="table-wrapper">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Shift</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Duration</th>
                    <th>Volunteers</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="shift in shiftsForTeam(team.id)" :key="shift.id"
                    :class="isMyShift(shift.id) ? 'bg-brand-900/10 border-l-2 border-brand-500' : ''">
                    <td>
                      <span class="font-medium" :class="isMyShift(shift.id) ? 'text-brand-300' : 'text-surface-100'">
                        {{ shift.name }}
                        <span v-if="isMyShift(shift.id)" class="ml-1 text-xs text-brand-400">(you)</span>
                      </span>
                    </td>
                    <td class="text-surface-300 text-xs whitespace-nowrap">{{ formatDT(shift.starts_at) }}</td>
                    <td class="text-surface-300 text-xs whitespace-nowrap">{{ formatDT(shift.ends_at) }}</td>
                    <td class="text-surface-400 text-sm">{{ durationHours(shift) }}h</td>
                    <td>
                      <div class="flex flex-wrap gap-1">
                        <div v-for="a in shift.assignments" :key="a.id" class="flex items-center gap-1">
                          <img :src="a.user.avatar_url" class="w-5 h-5 rounded-full" :title="a.user.name" />
                          <span class="text-xs text-surface-300">{{ a.user.name.split(' ')[0] }}</span>
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronLeft, Calendar, Check, Copy } from 'lucide-vue-next';
import { format } from 'date-fns';
import AppLayout from '@/components/layout/AppLayout.vue';

const props = defineProps<{ event: any; currentUser: any; calendarFeedUrl: string }>();

const copied = ref(false);
async function copyFeedUrl() {
  try {
    await navigator.clipboard.writeText(props.calendarFeedUrl);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 1500);
  } catch {
    // no-op
  }
}

function formatDate(d: string) { return format(new Date(d), 'dd MMM yyyy'); }
function formatDT(d: string) { return format(new Date(d), 'EEE dd MMM HH:mm'); }
function durationHours(shift: any) {
  return ((new Date(shift.ends_at).getTime() - new Date(shift.starts_at).getTime()) / 3600000).toFixed(1);
}

function shiftsForTeam(teamId: number) {
  return props.event.shifts.filter((s: any) => s.team_id === teamId).sort((a: any, b: any) => new Date(a.starts_at).getTime() - new Date(b.starts_at).getTime());
}

function isMyShift(shiftId: number) {
  const shift = props.event.shifts.find((s: any) => s.id === shiftId);
  return shift?.assignments.some((a: any) => a.user_id === props.currentUser.id);
}

const myShifts = computed(() =>
  props.event.shifts
    .filter((s: any) => s.assignments.some((a: any) => a.user_id === props.currentUser.id))
    .map((s: any) => ({ ...s, team: props.event.teams.find((t: any) => t.id === s.team_id) }))
    .sort((a: any, b: any) => new Date(a.starts_at).getTime() - new Date(b.starts_at).getTime())
);
</script>
