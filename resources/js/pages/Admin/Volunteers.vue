<template>
  <Head title="Volunteers" />
  <AppLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">Volunteers</h1>
        <p class="page-subtitle">All registered volunteers</p>
      </div>
    </div>

    <div class="card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Volunteer</th>
              <th>Discord</th>
              <th>Applications</th>
              <th>T-Shirt</th>
              <th>Joined</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="vol in volunteers.data" :key="vol.id">
              <td>
                <div class="flex items-center gap-3">
                  <img :src="vol.avatar_url" class="w-8 h-8 rounded-full bg-surface-700" />
                  <div>
                    <p class="font-medium text-surface-100 text-sm">{{ vol.name }}</p>
                    <p v-if="vol.email" class="text-xs text-surface-400">{{ vol.email }}</p>
                  </div>
                </div>
              </td>
              <td class="text-surface-300 text-sm">@{{ vol.discord_username }}</td>
              <td class="text-surface-400 text-sm">{{ vol.applications_count }}</td>
              <td class="text-surface-400 text-sm">{{ vol.tshirt_size ?? '—' }}</td>
              <td class="text-surface-400 text-xs whitespace-nowrap">{{ formatDate(vol.created_at) }}</td>
            </tr>
            <tr v-if="!volunteers.data.length">
              <td colspan="5" class="py-8 text-center text-surface-400 text-sm">No volunteers yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="volunteers.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-surface-400">
      <span>{{ volunteers.from }}–{{ volunteers.to }} of {{ volunteers.total }}</span>
      <div class="flex gap-1">
        <Link v-if="volunteers.prev_page_url" :href="volunteers.prev_page_url" class="btn-secondary btn-sm">Prev</Link>
        <Link v-if="volunteers.next_page_url" :href="volunteers.next_page_url" class="btn-secondary btn-sm">Next</Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { format } from 'date-fns';
import AppLayout from '@/components/layout/AppLayout.vue';

defineProps<{ volunteers: any }>();

function formatDate(d: string) { return format(new Date(d), 'dd MMM yyyy'); }
</script>
