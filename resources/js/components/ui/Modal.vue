<template>
  <Teleport to="body">
    <Transition
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
      enter-active-class="transition duration-200"
      leave-active-class="transition duration-150"
    >
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$emit('close')" />

        <!-- Modal -->
        <Transition
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
          enter-active-class="transition duration-200"
          leave-active-class="transition duration-150"
        >
          <div v-if="show" :class="['relative card w-full', sizeClass]">
            <button @click="$emit('close')" class="absolute top-4 right-4 btn-ghost btn-icon z-10">
              <X class="w-4 h-4" />
            </button>
            <slot />
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps<{ show: boolean; size?: 'sm' | 'md' | 'lg' | 'xl' }>();
defineEmits(['close']);

const sizeClass = computed(() => ({
  sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-2xl', xl: 'max-w-4xl',
}[props.size ?? 'md']));
</script>
