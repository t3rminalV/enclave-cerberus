<template>
  <Head title="Audit Log" />
  <AppLayout>
    <div class="page-header">
      <div>
        <h1 class="page-title">Audit Log</h1>
        <p class="page-subtitle">Record of actions from all users</p>
      </div>
    </div>

    <div class="card">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Actor</th>
              <th>Action</th>
              <th>Subject</th>
              <th>When</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs.data" :key="log.id">
              <td>
                <div class="flex items-center gap-2">
                  <img v-if="log.user" :src="log.user.avatar_url" class="w-6 h-6 rounded-full bg-surface-700" />
                  <span class="text-sm text-surface-200">{{ log.user?.name ?? 'System' }}</span>
                </div>
              </td>
              <td><code class="text-xs text-brand-300 bg-brand-900/20 px-1.5 py-0.5 rounded">{{ log.action }}</code></td>
              <td class="text-surface-400 text-xs">{{ formatSubject(log) }}</td>
              <td class="text-surface-400 text-xs whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
            </tr>
            <tr v-if="!logs.data.length">
              <td colspan="4" class="py-8 text-center text-surface-400 text-sm">No audit records yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="logs.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-surface-400">
      <span>{{ logs.from }}–{{ logs.to }} of {{ logs.total }}</span>
      <div class="flex gap-1">
        <Link v-if="logs.prev_page_url" :href="logs.prev_page_url" class="btn-secondary btn-sm">Prev</Link>
        <Link v-if="logs.next_page_url" :href="logs.next_page_url" class="btn-secondary btn-sm">Next</Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { format } from 'date-fns';
import AppLayout from '@/components/layout/AppLayout.vue';

defineProps<{ logs: any }>();

function formatDate(d: string) { return format(new Date(d), 'dd MMM yyyy HH:mm'); }

function formatSubject(log: any) {
  if (!log.auditable_type) return 'System';
  const subject = log.auditable_type.split('\\').pop();
  return log.auditable_id ? `${subject} #${log.auditable_id}` : subject;
}
</script>
