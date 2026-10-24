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
 * Page transition:
 * - Hanya geser (margin-top), TANPA fade/opacity.
 * - Page lama langsung tertutup page baru (z-index), tanpa animasi leave.
 * - Background solid --bg di wrap + page agar celah geser tidak menampilkan
 *   html hitam (#000) yang dipakai untuk navigation bar sistem.
 */
.page-transition-wrap {
  position: relative;
  min-height: 40vh;
  background: var(--bg);
  background-color: var(--bg);
}

.page-transition-page {
  width: 100%;
  min-height: 40vh;
  background: var(--bg);
  background-color: var(--bg);
}

/* Halaman tujuan — geser naik saja */
.meimo-page-enter-active {
  animation: meimo-page-in 380ms cubic-bezier(0.34, 1.56, 0.64, 1) both;
  position: relative;
  z-index: 2;
}

/* Halaman lama — tanpa fade, langsung di belakang page baru */
.meimo-page-leave-active {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  width: 100%;
  z-index: 1;
  pointer-events: none;
  /* tidak ada animasi opacity */
  transition: none;
  animation: none;
}

/* Hanya geser, opacity tetap 1 */
@keyframes meimo-page-in {
  0% {
    margin-top: 50px;
  }
  100% {
    margin-top: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .meimo-page-enter-active {
    animation: none !important;
  }
}
</style>
