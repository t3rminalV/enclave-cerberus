<template>
  <Head :title="`Edit — ${event.name}`" />
  <AppLayout>
    <div class="page-header">
      <div>
        <div class="flex items-center gap-2 text-sm text-surface-400 mb-1">
          <Link :href="route('admin.events.index')" class="hover:text-surface-200">Events</Link>
          <ChevronRight class="w-3 h-3" />
          <Link :href="route('admin.events.show', event.id)" class="hover:text-surface-200">{{ event.name }}</Link>
          <ChevronRight class="w-3 h-3" />
        </div>
        <h1 class="page-title">Edit Event</h1>
      </div>
    </div>

    <div class="max-w-2xl">
      <form @submit.prevent="form.patch(route('admin.events.update', event.id))">
        <EventForm :form="form" />
        <div class="mt-6 flex items-center gap-3">
          <button type="submit" :disabled="form.processing" class="btn-primary">
            {{ form.processing ? 'Saving…' : 'Save Changes' }}
          </button>
          <Link :href="route('admin.events.show', event.id)" class="btn-ghost">Cancel</Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import EventForm from '@/components/forms/EventForm.vue';

const props = defineProps<{ event: any }>();

const form = useForm({
  name: props.event.name,
  description: props.event.description ?? '',
  location: props.event.location ?? '',
  starts_at: props.event.starts_at?.slice(0, 16) ?? '',
  ends_at: props.event.ends_at?.slice(0, 16) ?? '',
  applications_open_at: props.event.applications_open_at?.slice(0, 16) ?? '',
  applications_close_at: props.event.applications_close_at?.slice(0, 16) ?? '',
  status: props.event.status,
  tickettailor_event_id: props.event.tickettailor_event_id ?? '',
  tickettailor_ticket_type_id: props.event.tickettailor_ticket_type_id ?? '',
});
</script>
