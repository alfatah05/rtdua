<template>
  <Teleport to="body">
    <Transition name="toast">
      <div
        v-if="visible"
        class="fixed left-1/2 -translate-x-1/2 z-[100] max-w-[min(90vw,340px)] px-4 py-2.5 rounded-full text-[13px] font-semibold text-center pointer-events-none leading-snug"
        style="
          bottom: calc(88px + env(safe-area-inset-bottom, 0px));
          background: #E8F5EE;
          color: #0B6B34;
          border: 1px solid rgba(10, 143, 68, 0.18);
          box-shadow: 0 2px 8px rgba(16, 22, 20, 0.06);
        "
      >
        {{ message }}
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { useToast } from '@shared/composables/useToast.js'
const { message, visible } = useToast()
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.22s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translate(-50%, 10px); }
</style>
