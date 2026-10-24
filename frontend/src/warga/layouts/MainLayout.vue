<template>
  <div class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <!-- Header di luar zone animasi — tidak ikut geser -->
    <div
      class="max-w-[560px] mx-auto px-4 pt-0"
      :class="showNav
        ? 'pb-[calc(96px+env(safe-area-inset-bottom,0px))]'
        : 'pb-[calc(24px+env(safe-area-inset-bottom,0px))]'"
    >
      <AppMainHeader v-if="showNav && !isHome" show-pengumuman />

      <!-- Hanya konten page yang dianimasikan -->
      <div class="page-transition-wrap">
        <router-view v-slot="{ Component, route: r }">
          <Transition :name="pageTransitionName">
            <div :key="r.fullPath" class="page-transition-page">
              <component :is="Component" />
            </div>
          </Transition>
        </router-view>
      </div>
    </div>

    <!-- Bottom nav di luar zone animasi — tidak ikut geser -->
    <AppBottomNav v-if="showNav" side="warga" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppBottomNav from '@shared/components/AppBottomNav.vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { pageTransitionName } from '@shared/composables/usePageTransition.js'

const route = useRoute()
const showNav = computed(() => !!route.meta.mainTab)
const isHome = computed(() => route.name === 'home')
</script>

<style>
.page-transition-wrap {
  position: relative;
  /* Cegah flash layout saat leave absolute */
  min-height: 40vh;
}

.page-transition-page {
  width: 100%;
  /* GPU layer biar halus di mobile */
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
}

/*
 * Meimo-like page-up
 * - Enter: dari bawah (~64px) + fade, 420ms, ease-out native
 * - Leave: fade saja (tidak turun / tidak naik), sedikit lebih cepat
 * - Dipakai untuk maju, back, DAN antar-tab navbar
 */
.page-up-enter-active {
  transition:
    transform 0.42s cubic-bezier(0.22, 1, 0.36, 1),
    opacity 0.36s cubic-bezier(0.22, 1, 0.36, 1);
  will-change: transform, opacity;
  z-index: 2;
}
.page-up-leave-active {
  transition: opacity 0.28s cubic-bezier(0.4, 0, 1, 1);
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  width: 100%;
  will-change: opacity;
  z-index: 1;
  pointer-events: none;
}

/* Page baru: mulai di bawah, naik ke posisi normal */
.page-up-enter-from {
  transform: translate3d(0, 64px, 0);
  opacity: 0;
}
.page-up-enter-to {
  transform: translate3d(0, 0, 0);
  opacity: 1;
}

/* Page lama: hanya pudar, TIDAK geser turun/naik */
.page-up-leave-from {
  opacity: 1;
}
.page-up-leave-to {
  opacity: 0;
}

/* First load */
.page-none-enter-active,
.page-none-leave-active {
  transition: none;
}

@media (prefers-reduced-motion: reduce) {
  .page-up-enter-active,
  .page-up-leave-active {
    transition: none !important;
  }
  .page-up-enter-from,
  .page-up-leave-to {
    transform: none !important;
    opacity: 1 !important;
  }
}
</style>
