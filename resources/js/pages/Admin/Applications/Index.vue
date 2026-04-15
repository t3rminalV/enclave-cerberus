<template>
  <Head :title="`Applications — ${event.name}`" />
  <AppLayout>
    <div class="page-header">
      <div>
        <div class="flex items-center gap-2 text-sm text-surface-400 mb-1">
          <Link :href="route('admin.events.show', event.id)" class="hover:text-surface-200">{{ event.name }}</Link>
          <ChevronRight class="w-3 h-3" />
        </div>
        <h1 class="page-title">Applications</h1>
      </div>
      <div class="flex items-center gap-2">
        <a :href="route('admin.events.applications.export', event.id)" class="btn-secondary btn-sm">
          <Download class="w-3.5 h-3.5" /> Export CSV
        </a>
        <a v-if="canExportFullData" :href="route('admin.events.applications.export-full', event.id)" class="btn-secondary btn-sm">
          <Download class="w-3.5 h-3.5" /> Export Full Data
        </a>
      </div>
    </div>

    <div v-if="anonymiseApplications" class="alert-info mb-6">
      <span>Application anonymisation is enabled. Personal details are hidden in this view and in the standard CSV export.</span>
    </div>

    <!-- Status tabs -->
    <div class="flex gap-1 mb-6 flex-wrap">
      <button
        v-for="s in statusFilters"
        :key="s.value"
        @click="setStatus(s.value)"
        :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', filters.status === s.value ? 'bg-brand-600 text-white' : 'bg-surface-700 text-surface-300 hover:bg-surface-600']"
      >
        {{ s.label }}
        <span class="ml-1.5 opacity-60">{{ statusCounts[s.value] ?? 0 }}</span>
      </button>
    </div>

    <!-- Search + bulk -->
    <div class="flex gap-3 mb-4 flex-wrap">
      <div class="relative flex-1 max-w-xs">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
        <input v-model="searchQuery" @input="search" class="input pl-9" :placeholder="anonymiseApplications ? 'Search by application ID…' : 'Search by name…'" />
      </div>
      <div v-if="selected.length > 0" class="flex items-center gap-2">
        <span class="text-sm text-surface-400">{{ selected.length }} selected</span>
        <select v-model="bulkStatus" class="select text-sm py-1.5 px-3">
          <option value="">Change status…</option>
          <option value="under_review">Under Review</option>
          <option value="accepted">Accepted</option>
          <option value="rejected">Rejected</option>
          <option value="waitlisted">Waitlisted</option>
        </select>
        <button @click="applyBulk" :disabled="!bulkStatus" class="btn-primary btn-sm">Apply</button>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="w-10">
                <input type="checkbox" @change="toggleAll" :checked="allSelected" class="rounded" />
              </th>
              <th>Volunteer</th>
              <th>Status</th>
              <th>Stage</th>
              <th>Tags</th>
              <th>Submitted</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="app in applications.data" :key="app.id">
              <td>
                <input type="checkbox" :value="app.id" v-model="selected" class="rounded" />
              </td>
              <td>
                <div class="flex items-center gap-3">
                  <img :src="app.user.avatar_url" class="w-7 h-7 rounded-full bg-surface-700 shrink-0" />
                  <div>
                    <p class="font-medium text-surface-100 text-sm">{{ app.user.name }}</p>
                    <p class="text-xs text-surface-400">@{{ app.user.discord_username }}</p>
                  </div>
                </div>
              </td>
              <td><ApplicationStatusBadge :status="app.status" /></td>
              <td class="text-surface-400 text-sm">{{ app.current_stage === 2 ? 'Stage 2' : 'Stage 1' }}</td>
              <td>
                <div class="flex gap-1 flex-wrap">
                  <span v-for="tag in app.tags" :key="tag.id" class="badge text-xs" :style="{ background: tag.color + '22', color: tag.color, borderColor: tag.color + '44' }">
                    {{ tag.name }}
                  </span>
                </div>
              </td>
              <td class="text-surface-400 text-xs whitespace-nowrap">
                {{ app.submitted_at ? format(new Date(app.submitted_at), 'dd MMM HH:mm') : '—' }}
              </td>
              <td>
                <Link :href="route('admin.events.applications.show', [event.id, app.id])" class="btn-ghost btn-sm">
                  View <ArrowRight class="w-3 h-3" />
                </Link>
              </td>
            </tr>
            <tr v-if="!applications.data.length">
              <td colspan="7" class="py-12 text-center text-surface-400 text-sm">No applications found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="applications.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-surface-400">
      <span>{{ applications.from }}–{{ applications.to }} of {{ applications.total }}</span>
      <div class="flex gap-1">
        <Link v-if="applications.prev_page_url" :href="applications.prev_page_url" preserve-state class="btn-secondary btn-sm">Prev</Link>
        <Link v-if="applications.next_page_url" :href="applications.next_page_url" preserve-state class="btn-secondary btn-sm">Next</Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Download, Search, ArrowRight } from 'lucide-vue-next';
import { format } from 'date-fns';
import AppLayout from '@/components/layout/AppLayout.vue';
import ApplicationStatusBadge from '@/components/ui/ApplicationStatusBadge.vue';

const props = defineProps<{
  event: any;
  applications: any;
  tags: any[];
  filters: Record<string, string>;
  statusCounts: Record<string, number>;
  anonymiseApplications: boolean;
  canExportFullData: boolean;
}>();

const selected = ref<number[]>([]);
const bulkStatus = ref('');
const searchQuery = ref(props.filters.search ?? '');

const statusFilters = [
  { value: '', label: 'All' },
  { value: 'submitted', label: 'Submitted' },
  { value: 'under_review', label: 'Under Review' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'waitlisted', label: 'Waitlisted' },
];

const filters = ref({ status: props.filters.status ?? '', search: props.filters.search ?? '' });

const allSelected = computed(() =>
  props.applications.data.length > 0 &&
  props.applications.data.every((a: any) => selected.value.includes(a.id))
);

function toggleAll(e: Event) {
  if ((e.target as HTMLInputElement).checked) {
    selected.value = props.applications.data.map((a: any) => a.id);
  } else {
    selected.value = [];
  }
}

function setStatus(status: string) {
  filters.value.status = status;
  router.get(route('admin.events.applications.index', props.event.id), { status, search: searchQuery.value }, { preserveState: true });
}

let searchTimer: ReturnType<typeof setTimeout>;
function search() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    router.get(route('admin.events.applications.index', props.event.id), { status: filters.value.status, search: searchQuery.value }, { preserveState: true });
  }, 300);
}

function applyBulk() {
  if (!bulkStatus.value || !selected.value.length) return;
  router.post(route('admin.events.applications.bulk-status', props.event.id), {
    application_ids: selected.value,
    status: bulkStatus.value,
  }, {
    onSuccess: () => { selected.value = []; bulkStatus.value = ''; },
  });
}
</script>
