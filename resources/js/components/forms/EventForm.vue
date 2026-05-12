<template>
  <div class="card card-body space-y-5">
    <div class="grid sm:grid-cols-2 gap-4">
      <div class="sm:col-span-2">
        <label class="label">Event Name <span class="text-red-400">*</span></label>
        <input v-model="form.name" class="input" :class="{ 'input-error': form.errors.name }" placeholder="Lancraft Summer 2025" />
        <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
      </div>
      <div class="sm:col-span-2">
        <label class="label">Description</label>
        <textarea v-model="form.description" class="textarea" placeholder="Brief description of the event..." rows="3" />
      </div>
      <div class="sm:col-span-2">
        <label class="label">Location</label>
        <input v-model="form.location" class="input" placeholder="Venue name or address" />
      </div>
      <div>
        <label class="label">Event Start <span class="text-red-400">*</span></label>
        <input v-model="form.starts_at" type="datetime-local" class="input" :class="{ 'input-error': form.errors.starts_at }" />
        <p v-if="form.errors.starts_at" class="form-error">{{ form.errors.starts_at }}</p>
      </div>
      <div>
        <label class="label">Event End <span class="text-red-400">*</span></label>
        <input v-model="form.ends_at" type="datetime-local" class="input" :class="{ 'input-error': form.errors.ends_at }" />
        <p v-if="form.errors.ends_at" class="form-error">{{ form.errors.ends_at }}</p>
      </div>
      <div>
        <label class="label">Applications Open <span class="text-surface-500 font-normal">(optional)</span></label>
        <input v-model="form.applications_open_at" type="datetime-local" class="input" />
        <p class="form-hint">Leave empty to open immediately when status is "Open"</p>
      </div>
      <div>
        <label class="label">Applications Close <span class="text-surface-500 font-normal">(optional)</span></label>
        <input v-model="form.applications_close_at" type="datetime-local" class="input" />
      </div>
      <div>
        <label class="label">Status</label>
        <select v-model="form.status" class="select">
          <option value="draft">Draft</option>
          <option value="open">Open</option>
          <option value="closed">Closed</option>
          <option value="completed">Completed</option>
        </select>
      </div>
    </div>

    <details class="border-t border-surface-700/60 pt-4">
      <summary class="cursor-pointer text-sm font-semibold text-surface-200">TicketTailor integration (optional)</summary>
      <p class="text-xs text-surface-400 mt-2">When set, accepted volunteers are auto-issued a complimentary ticket of the chosen type.</p>
      <div class="grid sm:grid-cols-2 gap-4 mt-3">
        <div>
          <label class="label">TicketTailor Event ID</label>
          <input v-model="form.tickettailor_event_id" class="input" placeholder="ev_..." />
        </div>
        <div>
          <label class="label">Volunteer Ticket Type ID</label>
          <input v-model="form.tickettailor_ticket_type_id" class="input" placeholder="tt_..." />
        </div>
      </div>
    </details>
  </div>
</template>

<script setup lang="ts">
defineProps<{ form: any }>();
</script>
