<template>
  <div class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <div
      class="max-w-[560px] mx-auto px-4 pt-0"
      :class="showNav
        ? 'pb-[calc(96px+env(safe-area-inset-bottom,0px))]'
        : 'pb-[calc(24px+env(safe-area-inset-bottom,0px))]'"
    >
      <!-- AppMainHeader (tab) — fade cepat, tidak ikut slide konten -->
      <Transition name="header-fade" mode="out-in">
        <AppMainHeader
          v-if="showNav && !isHome"
          :key="'main-hdr'"
          show-pengumuman
        />
      </Transition>

      <div class="page-transition-wrap">
        <router-view v-slot="{ Component, route: r }">
          <Transition
            :name="transitionName"
            @before-enter="onBeforeEnter"
            @after-enter="onAfterEnter"
            @after-leave="onAfterLeave"
          >
            <div :key="r.fullPath" class="page-transition-page">
              <!-- Child: skeleton selama animasi, baru mount page asli -->
              <template v-if="isChildRoute(r)">
                <PageChildSkeleton v-if="contentPending" />
                <div v-else class="page-real-appear">
                  <component :is="Component" />
                </div>
              </template>
              <!-- Tab utama: langsung konten -->
              <component :is="Component" v-else />
            </div>
          </Transition>
        </router-view>
      </div>
    </div>

    <AppBottomNav v-if="showNav" side="warga" />
  </div>
</template>

<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import AppBottomNav from '@shared/components/AppBottomNav.vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import PageChildSkeleton from '../components/PageChildSkeleton.vue'

/** Samakan dengan durasi meimo-page-in */
const PAGE_IN_MS = 380

const route = useRoute()
const showNav = computed(() => !!route.meta.mainTab)
const isHome = computed(() => route.name === 'home')

function isChildRoute(r) {
  if (!r || !r.name) return false
  if (r.meta?.mainTab) return false
  if (r.meta?.public) return false
  return true
}

const contentPending = ref(false)
const transitionName = computed(() =>
  isChildRoute(route) ? 'meimo-page' : 'tab-fade'
)

let pendingTimer = null

function clearPendingTimer() {
  if (pendingTimer) {
    clearTimeout(pendingTimer)
    pendingTimer = null
  }
}

function armContentPending() {
  if (!isChildRoute(route)) {
    contentPending.value = false
    return
  }
  contentPending.value = true
  clearPendingTimer()
  // Fallback: skeleton minimal sepanjang animasi meski hook terlewat
  pendingTimer = setTimeout(() => {
    contentPending.value = false
    pendingTimer = null
  }, PAGE_IN_MS + 40)
}

function onBeforeEnter() {
  if (isChildRoute(route)) {
    contentPending.value = true
  }
}

function onAfterEnter() {
  // Animasi enter selesai → lepas skeleton, mount page asli (header fade via page-real-appear)
  clearPendingTimer()
  contentPending.value = false
}

function onAfterLeave() {
  /* no-op */
}

// Saat navigasi child dimulai, kunci skeleton dari path change
watch(
  () => route.fullPath,
  () => {
    armContentPending()
  }
)

onBeforeUnmount(() => clearPendingTimer())
</script>

<style>
.page-transition-wrap {
  position: relative;
  min-height: 40vh;
}

.page-transition-page {
  width: 100%;
}

/* ——— Child: page tujuan geser (hanya skeleton yang ikut) ——— */
.meimo-page-enter-active {
  animation: meimo-page-in 380ms cubic-bezier(0.34, 1.56, 0.64, 1) both;
  position: relative;
  z-index: 2;
}
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

@keyframes meimo-page-out {
  from { opacity: 1; }
  to { opacity: 0; }
}

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

/* Page asli muncul setelah skeleton — fade cepat (header ikut fade, tidak slide) */
.page-real-appear {
  animation: page-real-fade 140ms ease-out both;
}
@keyframes page-real-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* ——— Tab bottom-nav: fade singkat ——— */
.tab-fade-enter-active,
.tab-fade-leave-active {
  transition: opacity 0.16s ease;
}
.tab-fade-leave-active {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  width: 100%;
  pointer-events: none;
}
.tab-fade-enter-from,
.tab-fade-leave-to {
  opacity: 0;
}

/* Header tab (AppMainHeader) fade cepat */
.header-fade-enter-active,
.header-fade-leave-active {
  transition: opacity 0.12s ease;
}
.header-fade-enter-from,
.header-fade-leave-to {
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .meimo-page-enter-active,
  .meimo-page-leave-active,
  .page-real-appear {
    animation: none !important;
  }
  .tab-fade-enter-active,
  .tab-fade-leave-active,
  .header-fade-enter-active,
  .header-fade-leave-active {
    transition: none !important;
  }
}
</style>
