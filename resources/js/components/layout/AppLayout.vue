<template>
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="hidden lg:flex lg:flex-col w-64 bg-surface-900 border-r border-surface-700/60 fixed inset-y-0 left-0 z-40">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 h-16 border-b border-surface-700/60">
        <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center">
          <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z"/>
          </svg>
        </div>
        <span class="font-bold text-white tracking-tight">Cerberus</span>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-1">
        <template v-if="isAdmin">
          <p class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-surface-500">Admin</p>
          <NavLink :href="route('admin.dashboard')" :active="isRoute('admin.dashboard')">
            <LayoutDashboard class="w-4 h-4" /> Dashboard
          </NavLink>
          <NavLink :href="route('admin.events.index')" :active="isRoute('admin.events.*')">
            <Calendar class="w-4 h-4" /> Events
          </NavLink>
          <NavLink :href="route('admin.volunteers.index')" :active="isRoute('admin.volunteers.*')">
            <Users class="w-4 h-4" /> Volunteers
          </NavLink>
          <NavLink :href="route('admin.audit-log')" :active="isRoute('admin.audit-log')">
            <FileText class="w-4 h-4" /> Audit Log
          </NavLink>
        </template>
        <template v-else>
          <p class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-surface-500">Portal</p>
          <NavLink :href="route('volunteer.dashboard')" :active="isRoute('volunteer.dashboard')">
            <LayoutDashboard class="w-4 h-4" /> My Dashboard
          </NavLink>
        </template>
      </nav>

      <!-- User -->
      <div class="px-3 py-4 border-t border-surface-700/60">
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg">
          <img :src="user.avatar_url" :alt="user.name" class="w-8 h-8 rounded-full bg-surface-700 shrink-0" />
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-surface-100 truncate">{{ user.name }}</p>
            <p class="text-xs text-surface-400 truncate">@{{ user.discord_username }}</p>
          </div>
          <Link :href="route('logout')" method="post" as="button" class="btn-ghost btn-icon shrink-0">
            <LogOut class="w-4 h-4" />
          </Link>
        </div>
        <NavLink :href="route('profile.edit')" :active="isRoute('profile.edit')" class="mt-1">
          <Settings class="w-4 h-4" /> Profile Settings
        </NavLink>
      </div>
    </aside>

    <!-- Mobile header -->
    <div class="lg:hidden fixed top-0 left-0 right-0 z-30 h-14 bg-surface-900 border-b border-surface-700/60 flex items-center px-4 gap-3">
      <button @click="mobileOpen = !mobileOpen" class="btn-ghost btn-icon">
        <Menu class="w-5 h-5" />
      </button>
      <span class="font-bold text-white">Cerberus</span>
      <div class="ml-auto">
        <img :src="user.avatar_url" class="w-8 h-8 rounded-full" />
      </div>
    </div>

    <!-- Mobile sidebar overlay -->
    <Transition
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-200"
    >
      <div v-if="mobileOpen" class="lg:hidden fixed inset-0 z-40 bg-black/60" @click="mobileOpen = false" />
    </Transition>
    <Transition
      enter-from-class="-translate-x-full"
      enter-to-class="translate-x-0"
      leave-from-class="translate-x-0"
      leave-to-class="-translate-x-full"
      enter-active-class="transition-transform duration-200"
      leave-active-class="transition-transform duration-200"
    >
      <aside v-if="mobileOpen" class="lg:hidden fixed inset-y-0 left-0 z-50 w-72 bg-surface-900 border-r border-surface-700/60 flex flex-col">
        <div class="flex items-center justify-between px-5 h-14 border-b border-surface-700/60">
          <span class="font-bold text-white">Cerberus</span>
          <button @click="mobileOpen = false" class="btn-ghost btn-icon">
            <X class="w-4 h-4" />
          </button>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          <template v-if="isAdmin">
            <NavLink :href="route('admin.dashboard')" :active="isRoute('admin.dashboard')" @click="mobileOpen = false">
              <LayoutDashboard class="w-4 h-4" /> Dashboard
            </NavLink>
            <NavLink :href="route('admin.events.index')" :active="isRoute('admin.events.*')" @click="mobileOpen = false">
              <Calendar class="w-4 h-4" /> Events
            </NavLink>
            <NavLink :href="route('admin.volunteers.index')" :active="isRoute('admin.volunteers.*')" @click="mobileOpen = false">
              <Users class="w-4 h-4" /> Volunteers
            </NavLink>
          </template>
          <template v-else>
            <NavLink :href="route('volunteer.dashboard')" :active="isRoute('volunteer.dashboard')" @click="mobileOpen = false">
              <LayoutDashboard class="w-4 h-4" /> My Dashboard
            </NavLink>
          </template>
          <NavLink :href="route('profile.edit')" :active="isRoute('profile.edit')" @click="mobileOpen = false">
            <Settings class="w-4 h-4" /> Profile
          </NavLink>
        </nav>
      </aside>
    </Transition>

    <!-- Main content -->
    <div class="flex-1 lg:pl-64">
      <main class="min-h-screen pt-14 lg:pt-0">
        <!-- Flash messages -->
        <FlashMessages />

        <!-- Page content -->
        <div class="px-4 sm:px-6 lg:px-8 py-8">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, Calendar, Users, Settings, LogOut, Menu, X, FileText } from 'lucide-vue-next';
import NavLink from '@/components/ui/NavLink.vue';
import FlashMessages from '@/components/ui/FlashMessages.vue';

const page = usePage();
const user = page.props.auth.user;
const isAdmin = user?.is_admin;
const mobileOpen = ref(false);

function isRoute(pattern: string): boolean {
  const current = page.url;
  if (pattern.endsWith('*')) {
    const prefix = pattern.slice(0, -1);
    return current.includes(prefix.replace(/\./g, '/'));
  }
  return route().current(pattern);
}
</script>
