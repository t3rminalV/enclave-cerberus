<template>
  <div>
    <div class="flex items-center gap-2">
      <div class="flex-1 min-w-0">
        <p v-if="modelValue.eventId && modelValue.ticketTypeId" class="text-sm text-surface-200">
          <span class="text-surface-400 text-xs">Event:</span>
          <span class="font-mono">{{ modelValue.eventId }}</span>
          <span class="text-surface-500 mx-1">·</span>
          <span class="text-surface-400 text-xs">Ticket type:</span>
          <span class="font-mono">{{ modelValue.ticketTypeId }}</span>
        </p>
        <p v-else class="text-sm text-surface-400">No TicketTailor event mapped.</p>
      </div>
      <button type="button" @click="openPicker" class="btn-secondary">
        <Ticket class="w-4 h-4" /> {{ modelValue.eventId ? 'Change' : 'Choose' }}
      </button>
      <button v-if="modelValue.eventId || modelValue.ticketTypeId" type="button" @click="clear" class="btn-ghost btn-sm">Clear</button>
    </div>

    <Teleport to="body">
      <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="open = false">
        <div class="bg-surface-800 border border-surface-700 rounded-xl w-full max-w-3xl max-h-[85vh] flex flex-col shadow-2xl">
          <div class="flex items-center justify-between px-5 py-4 border-b border-surface-700">
            <h3 class="font-semibold text-white flex items-center gap-2">
              <Ticket class="w-4 h-4" /> Choose from TicketTailor
            </h3>
            <button @click="open = false" class="btn-ghost btn-icon"><X class="w-4 h-4" /></button>
          </div>

          <div class="px-5 py-3 border-b border-surface-700 flex items-center gap-2">
            <Search class="w-4 h-4 text-surface-400 shrink-0" />
            <input
              v-model="query"
              @input="debouncedSearch"
              class="input flex-1"
              placeholder="Search events by name…"
            />
            <button @click="refresh" class="btn-secondary btn-sm">
              <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
            </button>
          </div>

          <div v-if="error" class="px-5 py-3 border-b border-surface-700 bg-red-900/20 text-red-300 text-sm flex items-center gap-2">
            <AlertCircle class="w-4 h-4 shrink-0" /> {{ error }}
          </div>

          <div class="flex-1 overflow-y-auto">
            <div v-if="loading && !events.length" class="p-8 text-center text-surface-400 text-sm">Loading…</div>
            <div v-else-if="!events.length" class="p-8 text-center text-surface-400 text-sm">No events found.</div>

            <div v-else class="divide-y divide-surface-700/40">
              <div v-for="event in events" :key="event.id" class="px-5 py-3">
                <button
                  @click="toggleEvent(event.id)"
                  class="w-full flex items-center justify-between gap-3 text-left"
                >
                  <div class="min-w-0 flex-1">
                    <p class="text-sm text-surface-100 truncate font-medium">{{ event.name }}</p>
                    <p class="text-xs text-surface-400 truncate">
                      <span class="font-mono">{{ event.id }}</span>
                      <template v-if="event.start"> · {{ formatDate(event.start) }}</template>
                      <template v-if="event.status"> · {{ event.status }}</template>
                    </p>
                  </div>
                  <ChevronDown class="w-4 h-4 text-surface-400 transition-transform shrink-0" :class="{ 'rotate-180': expanded === event.id }" />
                </button>

                <div v-if="expanded === event.id" class="mt-3 pl-3 border-l-2 border-surface-700 space-y-1.5">
                  <p v-if="!event.ticket_types.length" class="text-xs text-surface-400 italic">No ticket types on this event.</p>
                  <button
                    v-for="tt in event.ticket_types"
                    :key="tt.id"
                    type="button"
                    @click="pick(event, tt)"
                    class="w-full text-left rounded-md px-3 py-2 text-sm bg-surface-700/40 hover:bg-surface-700 hover:border-brand-500 border border-transparent transition-colors flex items-center justify-between gap-3"
                  >
                    <div>
                      <p class="text-surface-100">{{ tt.name }}</p>
                      <p class="text-xs text-surface-400 font-mono">{{ tt.id }}</p>
                    </div>
                    <span v-if="tt.status" class="text-xs text-surface-400">{{ tt.status }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="nextCursor" class="px-5 py-3 border-t border-surface-700">
            <button @click="loadMore" class="btn-secondary btn-sm w-full" :disabled="loading">
              {{ loading ? 'Loading…' : 'Load more' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Ticket, X, Search, RefreshCw, ChevronDown, AlertCircle } from 'lucide-vue-next';
import { format } from 'date-fns';

type TTEvent = {
  id: string;
  name: string;
  status: string | null;
  start: string | null;
  end: string | null;
  ticket_types: Array<{ id: string; name: string; status: string | null; price: number | null }>;
};

const props = defineProps<{
  modelValue: { eventId: string | null; ticketTypeId: string | null };
}>();
const emit = defineEmits<{ (e: 'update:modelValue', v: { eventId: string | null; ticketTypeId: string | null }): void }>();

const open = ref(false);
const loading = ref(false);
const error = ref<string | null>(null);
const events = ref<TTEvent[]>([]);
const nextCursor = ref<string | null>(null);
const expanded = ref<string | null>(null);
const query = ref('');

let debounceTimer: number | undefined;
function debouncedSearch() {
  window.clearTimeout(debounceTimer);
  debounceTimer = window.setTimeout(() => fetchEvents({ reset: true }), 300);
}

function openPicker() {
  open.value = true;
  if (!events.value.length) fetchEvents({ reset: true });
}

function clear() {
  emit('update:modelValue', { eventId: null, ticketTypeId: null });
}

function refresh() {
  fetchEvents({ reset: true });
}

function loadMore() {
  if (nextCursor.value) fetchEvents({ reset: false });
}

async function fetchEvents({ reset }: { reset: boolean }) {
  loading.value = true;
  error.value = null;
  if (reset) {
    events.value = [];
    nextCursor.value = null;
  }
  try {
    const params: Record<string, string> = {};
    if (query.value) params.q = query.value;
    if (!reset && nextCursor.value) {
      const m = nextCursor.value.match(/starting_after=([^&]+)/);
      if (m) params.starting_after = decodeURIComponent(m[1]);
    }
    const { data } = await axios.get(route('admin.tickettailor.events'), { params });
    events.value = reset ? data.events : [...events.value, ...data.events];
    nextCursor.value = data.next_cursor ?? null;
  } catch (e: any) {
    error.value = e.response?.data?.error ?? e.message ?? 'Failed to load TicketTailor events';
  } finally {
    loading.value = false;
  }
}

function toggleEvent(id: string) {
  expanded.value = expanded.value === id ? null : id;
}

function pick(event: TTEvent, tt: TTEvent['ticket_types'][number]) {
  emit('update:modelValue', { eventId: event.id, ticketTypeId: tt.id });
  open.value = false;
}

function formatDate(iso: string) {
  try { return format(new Date(iso), 'dd MMM yyyy'); }
  catch { return iso; }
}
</script>
