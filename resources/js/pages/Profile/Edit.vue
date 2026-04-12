<template>
  <Head title="Profile Settings" />
  <AppLayout>
    <div class="max-w-2xl">
      <div class="page-header">
        <div>
          <h1 class="page-title">Profile Settings</h1>
          <p class="page-subtitle">This info carries across all events you apply to</p>
        </div>
      </div>

      <!-- Discord info (read-only) -->
      <div class="card card-body mb-6 flex items-center gap-4">
        <img :src="user.avatar_url" class="w-14 h-14 rounded-full bg-surface-700" />
        <div>
          <p class="font-semibold text-white text-lg">{{ user.name }}</p>
          <p class="text-surface-400 text-sm">@{{ user.discord_username }}</p>
          <p v-if="user.email" class="text-surface-400 text-sm">{{ user.email }}</p>
        </div>
      </div>

      <!-- Profile incomplete warning -->
      <div v-if="!isComplete" class="alert-warning mb-6">
        <AlertTriangle class="w-4 h-4 shrink-0" />
        <span>Your profile is incomplete. First name, last name, and contact number are required before you can apply to events.</span>
      </div>

      <form @submit.prevent="form.patch(route('profile.update'))">
        <div class="card card-body space-y-5">
          <h2 class="font-semibold text-white">Personal Details</h2>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="label">First Name <span class="text-red-400">*</span></label>
              <input v-model="form.first_name" class="input" :class="{ 'input-error': form.errors.first_name }" placeholder="Jane" />
              <p v-if="form.errors.first_name" class="form-error">{{ form.errors.first_name }}</p>
            </div>
            <div>
              <label class="label">Last Name <span class="text-red-400">*</span></label>
              <input v-model="form.last_name" class="input" :class="{ 'input-error': form.errors.last_name }" placeholder="Smith" />
              <p v-if="form.errors.last_name" class="form-error">{{ form.errors.last_name }}</p>
            </div>
          </div>

          <h2 class="font-semibold text-white pt-2">Contact Information</h2>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="label">Contact Number <span class="text-red-400">*</span></label>
              <input v-model="form.phone" class="input" :class="{ 'input-error': form.errors.phone }" placeholder="+44 7700 900000" />
              <p v-if="form.errors.phone" class="form-error">{{ form.errors.phone }}</p>
            </div>
            <div>
              <label class="label">T-Shirt Size</label>
              <select v-model="form.tshirt_size" class="select">
                <option value="">Prefer not to say</option>
                <option v-for="s in ['XS','S','M','L','XL','XXL','XXXL']" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>
          </div>

          <h2 class="font-semibold text-white pt-2">Emergency Contact</h2>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="label">Name</label>
              <input v-model="form.emergency_contact_name" class="input" placeholder="Full name" />
            </div>
            <div>
              <label class="label">Phone</label>
              <input v-model="form.emergency_contact_phone" class="input" placeholder="+44 7700 900000" />
            </div>
          </div>

          <h2 class="font-semibold text-white pt-2">Health & Dietary</h2>
          <div>
            <label class="label">Dietary Requirements <span class="text-surface-500 font-normal">(optional)</span></label>
            <textarea v-model="form.dietary_requirements" class="textarea" rows="2" placeholder="E.g. vegetarian, nut allergy…" />
          </div>
          <div>
            <label class="label">Medical Information <span class="text-surface-500 font-normal">(optional, visible to event admins)</span></label>
            <textarea v-model="form.medical_info" class="textarea" rows="2" placeholder="Anything we should know in case of emergency…" />
          </div>
        </div>

        <div class="mt-6">
          <button type="submit" :disabled="form.processing" class="btn-primary">
            {{ form.processing ? 'Saving…' : 'Save Profile' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';

const props = defineProps<{ user: any }>();

const isComplete = computed(() =>
  props.user.first_name && props.user.last_name && props.user.phone
);

const form = useForm({
  first_name: props.user.first_name ?? '',
  last_name: props.user.last_name ?? '',
  phone: props.user.phone ?? '',
  emergency_contact_name: props.user.emergency_contact_name ?? '',
  emergency_contact_phone: props.user.emergency_contact_phone ?? '',
  tshirt_size: props.user.tshirt_size ?? '',
  dietary_requirements: props.user.dietary_requirements ?? '',
  medical_info: props.user.medical_info ?? '',
});
</script>
