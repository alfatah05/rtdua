<template>
  <div class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <div
      class="max-w-[560px] mx-auto px-4 pt-0"
      :class="showNav
        ? 'pb-[calc(96px+env(safe-area-inset-bottom,0px))]'
        : 'pb-[calc(24px+env(safe-area-inset-bottom,0px))]'"
    >
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
    </div>

    <AppBottomNav v-if="showNav" side="warga" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppBottomNav from '@shared/components/AppBottomNav.vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { useMotion } from '@shared/composables/useMotion.js'

const route = useRoute()
const showNav = computed(() => !!route.meta.mainTab)
const isHome = computed(() => route.name === 'home')
const { animEnabled } = useMotion()
const pageTransitionName = computed(() => (animEnabled.value ? 'meimo-page' : ''))
</script>

<style>
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

.meimo-page-enter-active {
  animation: meimo-page-in 380ms cubic-bezier(0.34, 1.56, 0.64, 1) both;
  position: relative;
  z-index: 2;
}

.meimo-page-leave-active {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  width: 100%;
  z-index: 1;
  pointer-events: none;
  transition: none;
  animation: none;
}

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
