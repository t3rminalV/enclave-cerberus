<template>
  <Head :title="`My Application — ${application.event.name}`" />
  <AppLayout>
    <div class="max-w-2xl mx-auto">
      <Link :href="route('volunteer.dashboard')" class="text-sm text-surface-400 hover:text-surface-200 flex items-center gap-1 mb-6">
        <ChevronLeft class="w-3 h-3" /> Back to dashboard
      </Link>

      <div class="page-header">
        <div>
          <h1 class="page-title">{{ application.event.name }}</h1>
          <p class="page-subtitle">Your application</p>
        </div>
        <ApplicationStatusBadge :status="application.status" />
      </div>

      <!-- Profile reminder -->
      <div class="alert-info mb-6">
        <Info class="w-4 h-4 shrink-0" />
        <span>
          Keep your
          <Link :href="route('profile.edit')" class="underline hover:no-underline">profile</Link>
          up to date — your name and contact number are shared with event organisers.
        </span>
      </div>

      <!-- Status messages -->
      <div v-if="application.status === 'accepted'" class="alert-success mb-6 flex items-start gap-3">
        <CheckCircle class="w-5 h-5 mt-0.5 shrink-0" />
        <div>
          <p class="font-medium">Congratulations! You've been accepted.</p>
          <p class="text-sm mt-0.5 opacity-80">Check your Discord DMs for next steps. Stage 2 information is available below if required.</p>
        </div>
      </div>
      <div v-else-if="application.status === 'rejected'" class="alert-error mb-6">
        <AlertCircle class="w-5 h-5 shrink-0" />
        <p>Your application was not successful this time. Thank you for applying.</p>
      </div>
      <div v-else-if="application.status === 'waitlisted'" class="alert-warning mb-6">
        <AlertTriangle class="w-5 h-5 shrink-0" />
        <p>You're on our waitlist. We'll contact you if a spot opens up.</p>
      </div>

      <!-- Active form (if editable) -->
      <div v-if="form && application.isEditable" class="card mb-6">
        <div class="card-header">
          <h2 class="font-semibold text-white">
            {{ form.title }}
            <span v-if="application.current_stage === 2" class="badge-purple ml-2">Stage 2</span>
          </h2>
          <p v-if="form.description" class="text-sm text-surface-400 mt-1">{{ form.description }}</p>
        </div>
        <div class="p-6">
          <form @submit.prevent="submit">
            <div class="space-y-5">
              <FormFieldInput
                v-for="field in form.fields"
                :key="field.id"
                :field="field"
                :error="errors[`fields.${field.id}`]"
                v-model="fieldValues[field.id]"
                @file-change="(files) => fileValues[field.id] = files"
              />
            </div>
            <div class="mt-6 flex gap-3">
              <button type="button" @click="save" :disabled="processing" class="btn-secondary">Save</button>
              <button v-if="application.status === 'draft'" type="submit" :disabled="processing" class="btn-primary">
                {{ processing ? 'Submitting…' : 'Submit Application' }}
              </button>
              <button v-else type="submit" :disabled="processing" class="btn-primary">
                {{ processing ? 'Saving…' : 'Update & Resubmit' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Read-only responses -->
      <div v-if="!form || !application.isEditable" class="card">
        <div class="card-header">
          <h2 class="font-semibold text-white">Your Responses</h2>
        </div>
        <div class="divide-y divide-surface-700/40">
          <div v-for="response in application.responses" :key="response.id" class="px-6 py-4">
            <p class="text-xs text-surface-400 font-semibold uppercase tracking-wide mb-1">{{ response.field?.label }}</p>
            <p class="text-sm text-surface-200">{{ response.value || '—' }}</p>
          </div>
          <div v-if="!application.responses?.length" class="px-6 py-8 text-center text-surface-400 text-sm">
            No responses yet.
          </div>
        </div>
      </div>

      <!-- Status timeline -->
      <div class="card mt-4">
        <div class="card-header">
          <h2 class="font-semibold text-white text-sm">Application Timeline</h2>
        </div>
        <div class="px-6 py-4">
          <ol class="relative border-l border-surface-700 space-y-3 ml-2">
            <li v-for="entry in application.status_history" :key="entry.id" class="ml-4">
              <div class="absolute -left-1.5 w-3 h-3 rounded-full bg-surface-600 border-2 border-surface-800" />
              <div class="flex items-center gap-2 flex-wrap">
                <ApplicationStatusBadge :status="entry.to_status" />
                <span class="text-xs text-surface-400">{{ formatRelative(entry.created_at) }}</span>
              </div>
              <p v-if="entry.note" class="text-xs text-surface-400 mt-1 italic">{{ entry.note }}</p>
            </li>
          </ol>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, CheckCircle, AlertCircle, AlertTriangle, Info } from 'lucide-vue-next';
import { formatDistanceToNow } from 'date-fns';
import AppLayout from '@/components/layout/AppLayout.vue';
import ApplicationStatusBadge from '@/components/ui/ApplicationStatusBadge.vue';
import FormFieldInput from '@/components/forms/FormFieldInput.vue';

const props = defineProps<{ application: any; form: any; user: any }>();

const fieldValues = ref<Record<number, any>>(
  Object.fromEntries((props.application.responses ?? []).map((r: any) => [r.form_field_id, r.value]))
);
const fileValues = ref<Record<number, File[]>>({});
const errors = ref<Record<string, string>>({});
const processing = ref(false);

function formatRelative(d: string) { return formatDistanceToNow(new Date(d), { addSuffix: true }); }

function buildFormData() {
  const fd = new FormData();
  for (const [id, val] of Object.entries(fieldValues.value)) {
    if (Array.isArray(val)) val.forEach(v => fd.append(`fields[${id}][]`, v));
    else if (val !== undefined && val !== null) fd.append(`fields[${id}]`, val);
  }
  for (const [id, files] of Object.entries(fileValues.value)) {
    if (files?.length) files.forEach(f => fd.append(`fields[${id}][]`, f));
  }
  return fd;
}

function save() {
  processing.value = true;
  const fd = buildFormData();
  fd.append('_method', 'PATCH');
  router.post(route('volunteer.applications.update', props.application.id), fd, {
    forceFormData: true,
    onFinish: () => { processing.value = false; },
  });
}

function submit() {
  processing.value = true;
  router.post(route('volunteer.applications.submit', props.application.id), buildFormData(), {
    forceFormData: true,
    onError: (e) => { errors.value = e; },
    onFinish: () => { processing.value = false; },
  });
}
</script>
