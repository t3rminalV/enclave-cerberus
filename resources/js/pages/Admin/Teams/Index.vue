<template>
  <Head :title="`Teams — ${event.name}`" />
  <AppLayout>
    <div class="page-header">
      <div>
        <div class="flex items-center gap-2 text-sm text-surface-400 mb-1">
          <Link :href="route('admin.events.show', event.id)" class="hover:text-surface-200">{{ event.name }}</Link>
          <ChevronRight class="w-3 h-3" />
        </div>
        <h1 class="page-title">Teams</h1>
      </div>
      <button @click="showCreate = true" class="btn-primary">
        <Plus class="w-4 h-4" /> New Team
      </button>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
      <div v-for="team in teams" :key="team.id" class="card">
        <div class="card-header flex items-center gap-3">
          <span class="w-4 h-4 rounded" :style="{ background: team.color }"></span>
          <h3 class="font-semibold text-white flex-1">{{ team.name }}</h3>
          <span class="text-xs text-surface-400">{{ team.members_count }} members</span>
          <button @click="editTeam(team)" class="btn-ghost btn-icon"><Pencil class="w-3.5 h-3.5" /></button>
          <button @click="deleteTeam(team)" class="btn-ghost btn-icon text-red-400"><Trash2 class="w-3.5 h-3.5" /></button>
        </div>
        <div class="p-4">
          <p v-if="team.description" class="text-sm text-surface-400 mb-3">{{ team.description }}</p>

          <!-- Member list -->
          <div class="space-y-2 mb-3">
            <div v-for="member in team.members" :key="member.id" class="flex items-center gap-2">
              <img :src="member.avatar_url" class="w-6 h-6 rounded-full bg-surface-700" />
              <span class="text-sm text-surface-200 flex-1">{{ member.name }}</span>
              <span v-if="member.pivot?.is_lead" class="badge-yellow text-xs">Lead</span>
              <span v-if="member.pivot?.role" class="text-xs text-surface-400">{{ member.pivot.role }}</span>
            </div>
            <p v-if="!team.members?.length" class="text-xs text-surface-500">No members yet</p>
          </div>

          <!-- Add members -->
          <button @click="managingTeam = team" class="btn-secondary btn-sm w-full">
            <Users class="w-3.5 h-3.5" /> Manage Members
          </button>
        </div>
      </div>

      <div v-if="!teams.length" class="card card-body text-center py-12 text-surface-400 lg:col-span-2">
        <Users class="w-8 h-8 mx-auto mb-2 opacity-30" />
        <p>No teams yet. Create your first team.</p>
      </div>
    </div>

    <!-- Create/Edit Team Modal -->
    <Modal :show="showCreate || !!editingTeam" @close="closeModal">
      <div class="card-body">
        <h2 class="font-semibold text-white text-lg mb-4">{{ editingTeam ? 'Edit Team' : 'New Team' }}</h2>
        <form @submit.prevent="saveTeam" class="space-y-4">
          <div>
            <label class="label">Name</label>
            <input v-model="teamForm.name" class="input" placeholder="Gate Team" required />
          </div>
          <div>
            <label class="label">Description</label>
            <textarea v-model="teamForm.description" class="textarea" rows="2" />
          </div>
          <div>
            <label class="label">Colour</label>
            <div class="flex items-center gap-3">
              <input type="color" v-model="teamForm.color" class="w-10 h-10 rounded cursor-pointer bg-transparent border border-surface-600" />
              <input v-model="teamForm.color" class="input" placeholder="#6366f1" pattern="^#[0-9a-fA-F]{6}$" />
            </div>
          </div>
          <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">{{ editingTeam ? 'Save' : 'Create Team' }}</button>
            <button type="button" @click="closeModal" class="btn-ghost">Cancel</button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Manage Members Modal -->
    <Modal :show="!!managingTeam" @close="managingTeam = null" size="lg">
      <div class="card-body" v-if="managingTeam">
        <h2 class="font-semibold text-white text-lg mb-1">{{ managingTeam.name }} — Members</h2>
        <p class="text-sm text-surface-400 mb-4">Select accepted volunteers to add to this team.</p>
        <div class="space-y-2 max-h-80 overflow-y-auto">
          <label v-for="vol in acceptedVolunteers" :key="vol.id"
            class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-700/40 cursor-pointer">
            <input type="checkbox" :value="vol.id" v-model="memberIds" class="rounded" />
            <img :src="vol.avatar_url" class="w-7 h-7 rounded-full bg-surface-700" />
            <div>
              <p class="text-sm text-surface-100">{{ vol.name }}</p>
              <p class="text-xs text-surface-400">@{{ vol.discord_username }}</p>
            </div>
          </label>
        </div>
        <div class="flex gap-3 mt-4">
          <button @click="saveMembers" class="btn-primary">Save Members</button>
          <button @click="managingTeam = null" class="btn-ghost">Cancel</button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Plus, Pencil, Trash2, Users } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import Modal from '@/components/ui/Modal.vue';

const props = defineProps<{ event: any; teams: any[]; acceptedVolunteers: any[] }>();

const showCreate = ref(false);
const editingTeam = ref<any>(null);
const managingTeam = ref<any>(null);
const memberIds = ref<number[]>([]);

const teamForm = ref({ name: '', description: '', color: '#6366f1' });

function editTeam(team: any) {
  editingTeam.value = team;
  teamForm.value = { name: team.name, description: team.description ?? '', color: team.color };
}

function closeModal() {
  showCreate.value = false;
  editingTeam.value = null;
  teamForm.value = { name: '', description: '', color: '#6366f1' };
}

function saveTeam() {
  if (editingTeam.value) {
    router.patch(route('admin.events.teams.update', [props.event.id, editingTeam.value.id]), teamForm.value, { onSuccess: closeModal });
  } else {
    router.post(route('admin.events.teams.store', props.event.id), teamForm.value, { onSuccess: closeModal });
  }
}

function deleteTeam(team: any) {
  if (!confirm(`Delete team "${team.name}"?`)) return;
  router.delete(route('admin.events.teams.destroy', [props.event.id, team.id]));
}

function openManage(team: any) {
  managingTeam.value = team;
  memberIds.value = team.members.map((m: any) => m.id);
}

function saveMembers() {
  router.post(route('admin.events.teams.members', [props.event.id, managingTeam.value.id]), {
    members: memberIds.value.map(id => ({ user_id: id })),
  }, { onSuccess: () => { managingTeam.value = null; } });
}
</script>
