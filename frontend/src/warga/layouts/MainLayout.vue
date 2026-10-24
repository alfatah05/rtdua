<template>
  <div class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <!-- Header di luar zone animasi -->
    <div
      class="max-w-[560px] mx-auto px-4 pt-0"
      :class="showNav
        ? 'pb-[calc(96px+env(safe-area-inset-bottom,0px))]'
        : 'pb-[calc(24px+env(safe-area-inset-bottom,0px))]'"
    >
      <AppMainHeader v-if="showNav && !isHome" show-pengumuman />

      <div class="page-transition-wrap">
        <router-view v-slot="{ Component, route: r }">
          <Transition name="meimo-page">
            <div :key="r.fullPath" class="page-transition-page">
              <component :is="Component" />
            </div>
          </Transition>
        </router-view>
      </div>
    </div>

    <!-- Bottom nav di luar zone animasi -->
    <AppBottomNav v-if="showNav" side="warga" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppBottomNav from '@shared/components/AppBottomNav.vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'

const route = useRoute()
const showNav = computed(() => !!route.meta.mainTab)
const isHome = computed(() => route.name === 'home')
</script>

<style>
/*
 * Keyframes & timing disalin 1:1 dari meimo_native:
 *   src/css/view-transitions.css + src/css/variables.css
 *   --duration-page-in: 380ms
 *   --duration-page-out: 220ms
 *   --ease-bounce: cubic-bezier(0.34, 1.56, 0.64, 1)
 *   --ease-standard: cubic-bezier(0.2, 0.8, 0.2, 1)
 *   in:  translateY(50px) → 0 + opacity 0→1
 *   out: translateY(0) → -16px + opacity 1→0
 *
 * Halaman tujuan SELALU di atas (z-index lebih tinggi) — termasuk saat back.
 * Header & bottom nav di luar wrap → tidak ikut animasi.
 */
.page-transition-wrap {
  position: relative;
  min-height: 40vh;
}

.page-transition-page {
  width: 100%;
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
}

/* Enter = halaman tujuan — di ATAS, animasi meimo-page-in */
.meimo-page-enter-active {
  animation: meimo-page-in 380ms cubic-bezier(0.34, 1.56, 0.64, 1) both;
  position: relative;
  z-index: 2;
  will-change: transform, opacity;
}

/* Leave = halaman sekarang — di BELAKANG, animasi meimo-page-out */
.meimo-page-leave-active {
  animation: meimo-page-out 220ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  width: 100%;
  z-index: 1;
  pointer-events: none;
  will-change: transform, opacity;
}

@keyframes meimo-page-out {
  from {
    opacity: 1;
    transform: translateY(0);
  }
  to {
    opacity: 0;
    transform: translateY(-16px);
  }
}

@keyframes meimo-page-in {
  from {
    opacity: 0;
    transform: translateY(50px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .meimo-page-enter-active,
  .meimo-page-leave-active {
    animation: none !important;
  }
}
</style>
