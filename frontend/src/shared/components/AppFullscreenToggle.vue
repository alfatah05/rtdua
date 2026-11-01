<template>
  <!-- Desktop only: floating di luar kolom max-width -->
  <button
    v-if="isDesktop"
    type="button"
    class="fs-toggle"
    :aria-label="isFullscreen ? 'Keluar layar penuh' : 'Layar penuh'"
    :title="isFullscreen ? 'Keluar layar penuh' : 'Layar penuh'"
    @click="toggle"
  >
    <Minimize2 v-if="isFullscreen" :size="18" stroke-width="2" />
    <Maximize2 v-else :size="18" stroke-width="2" />
  </button>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Maximize2, Minimize2 } from 'lucide-vue-next'

const isDesktop = ref(false)
const isFullscreen = ref(false)

let mq = null

function checkDesktop() {
  // pointer fine + lebar cukup → desktop/laptop, bukan HP
  const wide = window.matchMedia('(min-width: 768px)').matches
  const fine = window.matchMedia('(pointer: fine)').matches
  isDesktop.value = wide && fine
}

function syncFullscreen() {
  isFullscreen.value = !!(
    document.fullscreenElement ||
    document.webkitFullscreenElement ||
    document.mozFullScreenElement ||
    document.msFullscreenElement
  )
}

async function toggle() {
  try {
    if (isFullscreen.value) {
      const exit =
        document.exitFullscreen ||
        document.webkitExitFullscreen ||
        document.mozCancelFullScreen ||
        document.msExitFullscreen
      if (exit) await exit.call(document)
    } else {
      const el = document.documentElement
      const req =
        el.requestFullscreen ||
        el.webkitRequestFullscreen ||
        el.mozRequestFullScreen ||
        el.msRequestFullscreen
      if (req) await req.call(el)
    }
  } catch (_) {
    // user cancel / browser block
  }
  syncFullscreen()
}

function onMqChange() {
  checkDesktop()
}

onMounted(() => {
  checkDesktop()
  syncFullscreen()
  mq = window.matchMedia('(min-width: 768px)')
  mq.addEventListener?.('change', onMqChange)
  window.addEventListener('fullscreenchange', syncFullscreen)
  window.addEventListener('webkitfullscreenchange', syncFullscreen)
})

onUnmounted(() => {
  mq?.removeEventListener?.('change', onMqChange)
  window.removeEventListener('fullscreenchange', syncFullscreen)
  window.removeEventListener('webkitfullscreenchange', syncFullscreen)
})
</script>

<style scoped>
.fs-toggle {
  position: fixed;
  left: 16px;
  bottom: max(16px, env(safe-area-inset-bottom, 0px));
  z-index: 90;
  width: 44px;
  height: 44px;
  border-radius: 9999px;
  border: 1px solid var(--line);
  background: var(--card);
  color: var(--text);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.06);
  display: grid;
  place-items: center;
  cursor: pointer;
  pointer-events: auto;
  transition: background 0.15s ease, transform 0.12s ease, opacity 0.15s ease;
}
.fs-toggle:hover {
  background: var(--search);
}
.fs-toggle:active {
  transform: scale(0.94);
}

/* Sembunyikan di layar sempit (cadangan CSS) */
@media (max-width: 767px), (pointer: coarse) {
  .fs-toggle {
    display: none !important;
  }
}
</style>
