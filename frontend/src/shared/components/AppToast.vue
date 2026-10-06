<template>
  <Teleport to="body">
    <Transition name="toast">
      <div
        v-if="visible"
        class="fixed left-1/2 -translate-x-1/2 z-[100] max-w-[90vw] px-5 py-3 rounded-full shadow-lg text-[14px] font-semibold text-center pointer-events-none"
        :class="bgClass"
        :style="{ bottom: 'calc(88px + env(safe-area-inset-bottom, 0px))' }"
      >
        {{ message }}
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useToast } from '@shared/composables/useToast.js'
const { message, type, visible } = useToast()
const bgClass = computed(() => {
  if (type.value === 'success') return 'bg-[var(--g)] text-white'
  if (type.value === 'error') return 'bg-red-600 text-white'
  return 'bg-[var(--text)] text-[var(--bg)]'
})
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translate(-50%, 12px); }
</style>
