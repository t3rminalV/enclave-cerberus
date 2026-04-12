<template>
  <Head :title="event.name" />
  <AppLayout>
    <div class="page-header">
      <div>
        <div class="flex items-center gap-2 text-sm text-surface-400 mb-1">
          <Link :href="route('admin.events.index')" class="hover:text-surface-200">Events</Link>
          <ChevronRight class="w-3 h-3" />
        </div>
        <h1 class="page-title flex items-center gap-3">
          {{ event.name }}
          <EventStatusBadge :status="event.status" />
        </h1>
        <p class="page-subtitle">{{ event.location }} · {{ formatDateRange(event.starts_at, event.ends_at) }}</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <Link :href="route('admin.events.edit', event.id)" class="btn-secondary">
          <Pencil class="w-4 h-4" /> Edit
        </Link>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
      <div class="card card-body text-center" v-for="(count, label) in statItems" :key="label">
        <p class="text-2xl font-bold text-white">{{ count }}</p>
        <p class="text-xs text-surface-400 mt-0.5 capitalize">{{ label }}</p>
      </div>
    </div>

    <!-- Action cards grid -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <ActionCard
        title="Applications"
        :description="`${stats.applications_submitted} submitted, ${stats.applications_accepted} accepted`"
        icon="ClipboardList"
        :href="route('admin.events.applications.index', event.id)"
        color="blue"
      />
      <ActionCard
        title="Stage 1 Form"
        :description="event.stage_one_form ? 'Form configured' : 'Not set up yet'"
        icon="FileText"
        :href="route('admin.events.forms.show', [event.id, 1])"
        color="brand"
      />
      <ActionCard
        title="Stage 2 Form"
        :description="event.stage_two_form ? 'Form configured' : 'Not set up yet'"
        icon="FileText"
        :href="route('admin.events.forms.show', [event.id, 2])"
        color="purple"
      />
      <ActionCard
        title="Teams"
        :description="`${stats.teams_count} teams configured`"
        icon="Users"
        :href="route('admin.events.teams.index', event.id)"
        color="green"
      />
      <ActionCard
        title="Rota"
        :description="`${stats.shifts_count} shifts`"
        icon="Calendar"
        :href="route('admin.events.rota.index', event.id)"
        color="yellow"
      />
      <ActionCard
        title="Documents"
        icon="Folder"
        description="Manage shared documents & policies"
        :href="route('admin.events.documents.index', event.id)"
        color="orange"
      />
      <ActionCard
        title="Notifications"
        icon="Bell"
        description="Send Discord messages to volunteers"
        :href="route('admin.events.notifications.index', event.id)"
        color="pink"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, Pencil } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import EventStatusBadge from '@/components/ui/EventStatusBadge.vue';
import ActionCard from '@/components/ui/ActionCard.vue';
import { format } from 'date-fns';

const props = defineProps<{ event: any; stats: Record<string, number> }>();

const statItems = computed(() => ({
  total: props.stats.applications_total,
  submitted: props.stats.applications_submitted,
  'under review': 0,
  accepted: props.stats.applications_accepted,
  rejected: props.stats.applications_rejected,
  waitlisted: props.stats.applications_waitlisted,
}));

function formatDateRange(start: string, end: string) {
  return `${format(new Date(start), 'dd MMM')} – ${format(new Date(end), 'dd MMM yyyy')}`;
}
</script>
