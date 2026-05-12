<template>
  <Head title="Create Event" />
  <AppLayout>
    <div class="page-header">
      <div>
        <div class="flex items-center gap-2 text-sm text-surface-400 mb-1">
          <Link :href="route('admin.events.index')" class="hover:text-surface-200">Events</Link>
          <ChevronRight class="w-3 h-3" />
        </div>
        <h1 class="page-title">Create Event</h1>
      </div>
    </div>

    <div class="max-w-2xl">
      <form @submit.prevent="form.post(route('admin.events.store'))">
        <EventForm :form="form" />
        <div class="mt-6 flex items-center gap-3">
          <button type="submit" :disabled="form.processing" class="btn-primary">
            {{ form.processing ? 'Creating…' : 'Create Event' }}
          </button>
          <Link :href="route('admin.events.index')" class="btn-ghost">Cancel</Link>
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

const form = useForm({
  name: '', description: '', location: '',
  starts_at: '', ends_at: '',
  applications_open_at: '', applications_close_at: '',
  status: 'draft',
  tickettailor_event_id: '', tickettailor_ticket_type_id: '',
});
</script>
