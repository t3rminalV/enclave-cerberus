<template>
  <Head title="Dashboard" />
  <AppLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Overview of all events and volunteer activity</p>
      </div>
      <Link :href="route('admin.events.create')" class="btn-primary">
        <Plus class="w-4 h-4" /> New Event
      </Link>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <StatCard
        label="Total Volunteers"
        :value="stats.total_volunteers"
        icon="Users"
        color="brand"
      />
      <StatCard
        label="Total Events"
        :value="stats.total_events"
        icon="Calendar"
        color="blue"
      />
      <StatCard
        label="Active Events"
        :value="stats.active_events"
        icon="Activity"
        color="green"
      />
      <StatCard
        label="Pending Applications"
        :value="stats.pending_applications"
        icon="ClipboardList"
        color="yellow"
      />
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
      <!-- Recent Events -->
      <div class="lg:col-span-2 card">
        <div class="card-header flex items-center justify-between">
          <h2 class="font-semibold text-white">Recent Events</h2>
          <Link :href="route('admin.events.index')" class="text-xs text-brand-400 hover:text-brand-300">View all</Link>
        </div>
        <div class="divide-y divide-surface-700/40">
          <div v-for="event in recentEvents" :key="event.id" class="px-6 py-4 flex items-center gap-4">
            <div class="flex-1 min-w-0">
              <Link :href="route('admin.events.show', event.id)" class="font-medium text-surface-100 hover:text-white truncate block">
                {{ event.name }}
              </Link>
              <p class="text-xs text-surface-400 mt-0.5">{{ event.applications_count }} applications</p>
            </div>
            <EventStatusBadge :status="event.status" />
          </div>
          <div v-if="!recentEvents.length" class="px-6 py-8 text-center text-surface-400 text-sm">
            No events yet. <Link :href="route('admin.events.create')" class="text-brand-400 hover:underline">Create one</Link>.
          </div>
        </div>
      </div>

      <!-- Pending Applications -->
      <div class="card">
        <div class="card-header">
          <h2 class="font-semibold text-white">Pending Review</h2>
        </div>
        <div class="divide-y divide-surface-700/40">
          <div v-for="app in recentApplications" :key="app.id" class="px-6 py-3 flex items-start gap-3">
            <img :src="app.user.avatar_url" class="w-7 h-7 rounded-full mt-0.5 shrink-0 bg-surface-700" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-surface-100 truncate">{{ app.user.name }}</p>
              <p class="text-xs text-surface-400 truncate">{{ app.event.name }}</p>
            </div>
            <Link :href="route('admin.events.applications.show', [app.event_id, app.id])" class="btn-ghost btn-icon shrink-0">
              <ArrowRight class="w-3 h-3" />
            </Link>
          </div>
          <div v-if="!recentApplications.length" class="px-6 py-8 text-center text-surface-400 text-sm">
            No pending applications.
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Audit Log -->
    <div class="card mt-6">
      <div class="card-header flex items-center justify-between">
        <h2 class="font-semibold text-white">Recent Activity</h2>
        <Link :href="route('admin.audit-log')" class="text-xs text-brand-400 hover:text-brand-300">View all</Link>
      </div>
      <div class="divide-y divide-surface-700/40">
        <div v-for="log in recentAuditLogs" :key="log.id" class="px-6 py-3 flex items-center gap-3">
          <img v-if="log.user" :src="log.user.avatar_url" class="w-6 h-6 rounded-full shrink-0 bg-surface-700" />
          <div v-else class="w-6 h-6 rounded-full bg-surface-600 shrink-0" />
          <div class="flex-1 min-w-0">
            <p class="text-sm text-surface-200">
              <span class="font-medium">{{ log.user?.name ?? 'System' }}</span>
              <span class="text-surface-400"> — </span>
              <span class="font-mono text-xs text-brand-300">{{ log.action }}</span>
            </p>
          </div>
          <span class="text-xs text-surface-500 whitespace-nowrap">{{ formatDate(log.created_at) }}</span>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, ArrowRight } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import StatCard from '@/components/ui/StatCard.vue';
import EventStatusBadge from '@/components/ui/EventStatusBadge.vue';
import { formatDistanceToNow } from 'date-fns';

defineProps<{
  stats: Record<string, number>;
  recentEvents: any[];
  recentApplications: any[];
  recentAuditLogs: any[];
}>();

function formatDate(date: string) {
  return formatDistanceToNow(new Date(date), { addSuffix: true });
}
</script>
