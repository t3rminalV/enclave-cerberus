<template>
  <Head :title="`Feedback — ${event.name}`" />
  <AppLayout>
    <div class="max-w-2xl mx-auto">
      <div class="mb-6">
        <Link :href="route('volunteer.dashboard')" class="text-sm text-surface-400 hover:text-surface-200 flex items-center gap-1 mb-3">
          <ChevronLeft class="w-3 h-3" /> Dashboard
        </Link>
        <h1 class="page-title">Feedback — {{ event.name }}</h1>
        <p class="page-subtitle">Help us improve future events</p>
      </div>

      <div v-if="submitted" class="alert-success">
        <CheckCircle class="w-4 h-4 shrink-0" />
        <span>Thanks — your feedback has already been submitted. Edits are not currently supported.</span>
      </div>

      <div v-else class="card card-body">
        <h2 class="text-lg font-bold text-white mb-1">{{ form.title }}</h2>
        <p v-if="form.description" class="text-sm text-surface-300 mb-4">{{ form.description }}</p>

        <form @submit.prevent="submit" class="space-y-5">
          <template v-for="field in form.fields" :key="field.id">
            <FormFieldInput
              v-if="isFieldVisible(field.visible_when, fieldValues)"
              :field="field"
              :error="errors[`fields.${field.id}`]"
              v-model="fieldValues[field.id]"
            />
          </template>

          <div class="flex justify-end pt-2">
            <button type="submit" :disabled="processing" class="btn-primary">
              {{ processing ? 'Submitting…' : 'Submit Feedback' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, CheckCircle } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import FormFieldInput from '@/components/forms/FormFieldInput.vue';
import { isFieldVisible } from '@/composables/useFieldVisibility';

const props = defineProps<{
  event: any;
  form: any;
  submitted: boolean;
  existingResponses: Record<number, string>;
}>();

const fieldValues = ref<Record<number, any>>({ ...props.existingResponses });
const errors = ref<Record<string, string>>({});
const processing = ref(false);

function submit() {
  processing.value = true;
  const payload: Record<string, any> = { fields: {} };
  for (const [id, v] of Object.entries(fieldValues.value)) {
    payload.fields[id] = v;
  }
  router.post(route('volunteer.feedback.store', props.event.id), payload, {
    onError: (e) => { errors.value = e; },
    onFinish: () => { processing.value = false; },
  });
}
</script>
