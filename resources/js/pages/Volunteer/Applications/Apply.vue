<template>
  <Head :title="`Apply — ${event.name}`" />
  <AppLayout>
    <div class="max-w-2xl mx-auto">
      <div class="mb-8">
        <Link :href="route('volunteer.dashboard')" class="text-sm text-surface-400 hover:text-surface-200 flex items-center gap-1 mb-4">
          <ChevronLeft class="w-3 h-3" /> Back to dashboard
        </Link>
        <h1 class="page-title">Apply for {{ event.name }}</h1>
        <p v-if="event.location" class="page-subtitle">{{ event.location }} · {{ formatDate(event.starts_at) }}</p>
      </div>

      <!-- Profile notice -->
      <div v-if="!user.phone" class="alert-info mb-6">
        <Info class="w-4 h-4 shrink-0" />
        <span>
          Your profile is incomplete.
          <Link :href="route('profile.edit')" class="underline hover:no-underline">Update your profile</Link>
          before submitting for a smoother experience.
        </span>
      </div>

      <div class="card card-body mb-6">
        <h2 class="text-lg font-bold text-white mb-1">{{ form.title }}</h2>
        <p v-if="form.description" class="text-sm text-surface-300">{{ form.description }}</p>
      </div>

      <form @submit.prevent="submit">
        <div class="space-y-5">
          <div v-for="field in form.fields" :key="field.id">
            <FormFieldInput
              :field="field"
              :error="errors[`fields.${field.id}`]"
              v-model="fieldValues[field.id]"
              @file-change="(files) => fileValues[field.id] = files"
            />
          </div>
        </div>

        <div class="mt-8 flex gap-3">
          <button type="button" @click="save" :disabled="processing" class="btn-secondary">
            Save Draft
          </button>
          <button type="submit" :disabled="processing" class="btn-primary">
            {{ processing ? 'Submitting…' : 'Submit Application' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, Info } from 'lucide-vue-next';
import { format } from 'date-fns';
import AppLayout from '@/components/layout/AppLayout.vue';
import FormFieldInput from '@/components/forms/FormFieldInput.vue';

const props = defineProps<{ event: any; form: any; user: any }>();

const fieldValues = ref<Record<number, any>>({});
const fileValues = ref<Record<number, File[]>>({});
const errors = ref<Record<string, string>>({});
const processing = ref(false);

function formatDate(d: string) { return format(new Date(d), 'dd MMM yyyy'); }

function buildFormData(submit: boolean) {
  const fd = new FormData();
  for (const [id, val] of Object.entries(fieldValues.value)) {
    if (Array.isArray(val)) {
      val.forEach(v => fd.append(`fields[${id}][]`, v));
    } else if (val !== undefined && val !== null) {
      fd.append(`fields[${id}]`, val);
    }
  }
  for (const [id, files] of Object.entries(fileValues.value)) {
    if (files?.length) {
      files.forEach(f => fd.append(`fields[${id}][]`, f));
    }
  }
  return fd;
}

function save() {
  processing.value = true;
  router.post(route('volunteer.applications.store', props.event.id), buildFormData(false), {
    forceFormData: true,
    onError: (e) => { errors.value = e; },
    onFinish: () => { processing.value = false; },
  });
}

function submit() {
  processing.value = true;
  // First save, then submit
  router.post(route('volunteer.applications.store', props.event.id), buildFormData(true), {
    forceFormData: true,
    onSuccess: (page) => {
      // Find the new application id from redirect
    },
    onError: (e) => { errors.value = e; },
    onFinish: () => { processing.value = false; },
  });
}
</script>
