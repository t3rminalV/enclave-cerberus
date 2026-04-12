<template>
  <Head title="My Dashboard" />
  <AppLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">Welcome back, {{ user.name.split(' ')[0] }}</h1>
        <p class="page-subtitle">Your volunteer portal</p>
      </div>
    </div>

    <!-- Open events to apply -->
    <div v-if="openEvents.length" class="mb-8">
      <h2 class="text-lg font-semibold text-white mb-4">Open for Applications</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="event in openEvents" :key="event.id" class="card card-body hover:border-brand-500/40 transition-colors">
          <div class="flex items-start justify-between gap-2 mb-3">
            <h3 class="font-semibold text-white">{{ event.name }}</h3>
            <span class="badge-green">Open</span>
          </div>
          <p v-if="event.location" class="text-sm text-surface-400 mb-1">📍 {{ event.location }}</p>
          <p class="text-xs text-surface-400 mb-4">{{ formatDate(event.starts_at) }} – {{ formatDate(event.ends_at) }}</p>
          <Link :href="route('volunteer.applications.create', event.id)" class="btn-primary btn-sm w-full">
            Apply Now
          </Link>
        </div>
      </div>
    </div>

    <!-- My applications -->
    <div>
      <h2 class="text-lg font-semibold text-white mb-4">My Applications</h2>

      <div v-if="!myApplications.length" class="card card-body text-center py-12 text-surface-400">
        <ClipboardList class="w-8 h-8 mx-auto mb-2 opacity-30" />
        <p>No applications yet.</p>
      </div>

      <div class="space-y-3">
        <div v-for="app in myApplications" :key="app.id" class="card card-body flex items-center gap-4 flex-wrap">
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-white">{{ app.event.name }}</h3>
            <p class="text-xs text-surface-400 mt-0.5">
              {{ formatDate(app.event.starts_at) }}
              <template v-if="app.submitted_at"> · Submitted {{ formatRelative(app.submitted_at) }}</template>
            </p>
          </div>
          <ApplicationStatusBadge :status="app.status" />
          <div class="flex gap-2">
            <Link :href="route('volunteer.applications.show', app.id)" class="btn-secondary btn-sm">
              View Application
            </Link>
            <Link v-if="app.status === 'accepted'" :href="route('volunteer.rota.show', app.event_id)" class="btn-secondary btn-sm">
              <Calendar class="w-3.5 h-3.5" /> Rota
            </Link>
            <Link v-if="app.status === 'accepted'" :href="route('volunteer.documents.index', app.event_id)" class="btn-secondary btn-sm">
              <FileText class="w-3.5 h-3.5" /> Docs
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, Calendar, FileText } from 'lucide-vue-next';
import { format, formatDistanceToNow } from 'date-fns';
import AppLayout from '@/components/layout/AppLayout.vue';
import ApplicationStatusBadge from '@/components/ui/ApplicationStatusBadge.vue';

defineProps<{ myApplications: any[]; openEvents: any[] }>();

const user = usePage().props.auth.user;

function formatDate(d: string) { return format(new Date(d), 'dd MMM yyyy'); }
function formatRelative(d: string) { return formatDistanceToNow(new Date(d), { addSuffix: true }); }
</script>
