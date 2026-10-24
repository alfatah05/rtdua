<template>
  <div class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <main
      class="max-w-[560px] mx-auto px-4 pt-0"
      :class="showNav
        ? 'pb-[calc(96px+env(safe-area-inset-bottom,0px))]'
        : 'pb-[calc(24px+env(safe-area-inset-bottom,0px))]'"
    >
      <!-- Header utama stabil — disembunyikan di beranda (diganti bar di HomeView) -->
      <AppMainHeader v-if="showNav && !isHome" show-pengumuman />
      <div class="page-transition-wrap">
        <router-view v-slot="{ Component, route: r }">
          <Transition :name="pageTransitionName">
            <div :key="r.fullPath" class="page-transition-page">
              <component :is="Component" />
            </div>
          </Transition>
        </router-view>
      </div>
    </main>
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
}

.page-transition-page {
  width: 100%;
}

/* ——— Maju: page baru naik dari bawah ——— */
.page-forward-enter-active {
  transition:
    transform 0.36s cubic-bezier(0.32, 0.72, 0, 1),
    opacity 0.3s ease;
  will-change: transform, opacity;
  z-index: 2;
}
.page-forward-leave-active {
  transition:
    transform 0.28s cubic-bezier(0.4, 0, 1, 1),
    opacity 0.24s ease;
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  width: 100%;
  will-change: transform, opacity;
  z-index: 1;
  pointer-events: none;
}
.page-forward-enter-from {
  transform: translate3d(0, 36px, 0);
  opacity: 0;
}
.page-forward-leave-to {
  transform: translate3d(0, -10px, 0);
  opacity: 0;
}

/* ——— Mundur: page keluar turun ——— */
.page-back-enter-active {
  transition: opacity 0.28s ease, transform 0.28s cubic-bezier(0.32, 0.72, 0, 1);
  will-change: transform, opacity;
  z-index: 1;
}
.page-back-leave-active {
  transition:
    transform 0.34s cubic-bezier(0.32, 0.72, 0, 1),
    opacity 0.28s ease;
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  width: 100%;
  will-change: transform, opacity;
  z-index: 2;
  pointer-events: none;
}
.page-back-enter-from {
  transform: translate3d(0, -8px, 0);
  opacity: 0.7;
}
.page-back-leave-to {
  transform: translate3d(0, 40px, 0);
  opacity: 0;
}

/* ——— Antar tab bottom nav: fade ——— */
.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 0.18s ease;
}
.page-fade-leave-active {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  width: 100%;
  pointer-events: none;
}
.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
}

/* Tanpa animasi (first load) */
.page-none-enter-active,
.page-none-leave-active {
  transition: none;
}

@media (prefers-reduced-motion: reduce) {
  .page-forward-enter-active,
  .page-forward-leave-active,
  .page-back-enter-active,
  .page-back-leave-active,
  .page-fade-enter-active,
  .page-fade-leave-active {
    transition: none !important;
  }
  .page-forward-enter-from,
  .page-forward-leave-to,
  .page-back-enter-from,
  .page-back-leave-to {
    transform: none !important;
    opacity: 1 !important;
  }
}
</style>
