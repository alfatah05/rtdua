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
 * Page transition (adaptasi meimo_native):
 * - Page tujuan: geser naik 50px + bounce, opacity cepat penuh biar
 *   bayangan page lama tidak keliatan lama.
 * - Page lama: HANYA fade cepat (tanpa translateY).
 * - TANPA transform di wrapper → position:fixed (floating button)
 *   tetap nempel viewport, tidak "blink" ke flow dokumen.
 *   Geser pakai margin-top (bukan transform).
 */
.page-transition-wrap {
  position: relative;
  min-height: 40vh;
}

.page-transition-page {
  width: 100%;
}

/* Halaman tujuan — di ATAS */
.meimo-page-enter-active {
  animation: meimo-page-in 380ms cubic-bezier(0.34, 1.56, 0.64, 1) both;
  position: relative;
  z-index: 2;
}

/* Halaman lama — di BELAKANG, fade saja */
.meimo-page-leave-active {
  animation: meimo-page-out 120ms ease-out both;
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  width: 100%;
  z-index: 1;
  pointer-events: none;
}

/* Page lama: fade cepat, tanpa geser */
@keyframes meimo-page-out {
  from {
    opacity: 1;
  }
  to {
    opacity: 0;
  }
}

/*
 * Page baru:
 * - opacity full di ~30% (≈114ms) supaya bayangan page lama cepat hilang
 * - geser 50px→0 sampai 100% dengan bounce (margin-top, bukan transform)
 */
@keyframes meimo-page-in {
  0% {
    opacity: 0;
    margin-top: 50px;
  }
  30% {
    opacity: 1;
  }
  100% {
    opacity: 1;
    margin-top: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .meimo-page-enter-active,
  .meimo-page-leave-active {
    animation: none !important;
  }
}
</style>
