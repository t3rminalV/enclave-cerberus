<template>
  <Head title="Events" />
  <AppLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">Events</h1>
        <p class="page-subtitle">Manage all volunteer events</p>
      </div>
      <Link :href="route('admin.events.create')" class="btn-primary">
        <Plus class="w-4 h-4" /> New Event
      </Link>
    </div>

    <div class="card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Event</th>
              <th>Dates</th>
              <th>Status</th>
              <th>Applications</th>
              <th>Teams</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="event in events.data" :key="event.id">
              <td>
                <div>
                  <p class="font-medium text-surface-100">{{ event.name }}</p>
                  <p v-if="event.location" class="text-xs text-surface-400">{{ event.location }}</p>
                </div>
              </td>
              <td class="whitespace-nowrap text-surface-400 text-xs">
                {{ formatDate(event.starts_at) }} → {{ formatDate(event.ends_at) }}
              </td>
              <td><EventStatusBadge :status="event.status" /></td>
              <td class="text-surface-300">{{ event.applications_count }}</td>
              <td class="text-surface-300">{{ event.teams_count }}</td>
              <td>
                <div class="flex items-center gap-1 justify-end">
                  <Link :href="route('admin.events.show', event.id)" class="btn-ghost btn-sm">View</Link>
                  <Link :href="route('admin.events.edit', event.id)" class="btn-ghost btn-sm btn-icon">
                    <Pencil class="w-3.5 h-3.5" />
                  </Link>
                </div>
              </td>
            </tr>
            <tr v-if="!events.data.length">
              <td colspan="6" class="py-12 text-center text-surface-400">
                No events yet. <Link :href="route('admin.events.create')" class="text-brand-400 hover:underline">Create your first event</Link>.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="events.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-surface-400">
      <span>Showing {{ events.from }}–{{ events.to }} of {{ events.total }}</span>
      <div class="flex gap-1">
        <Link v-if="events.prev_page_url" :href="events.prev_page_url" class="btn-secondary btn-sm">Previous</Link>
        <Link v-if="events.next_page_url" :href="events.next_page_url" class="btn-secondary btn-sm">Next</Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Pencil } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import EventStatusBadge from '@/components/ui/EventStatusBadge.vue';
import { format } from 'date-fns';

defineProps<{ events: any }>();

function formatDate(d: string) {
  return format(new Date(d), 'dd MMM yy');
}
</script>
