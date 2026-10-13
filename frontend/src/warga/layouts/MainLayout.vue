<template>
  <div class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <main
      class="max-w-[560px] mx-auto px-4 pt-0"
      :class="showNav
        ? 'pb-[calc(96px+env(safe-area-inset-bottom,0px))]'
        : 'pb-[calc(24px+env(safe-area-inset-bottom,0px))]'"
    >
      <!-- Header utama stabil (tidak remount saat ganti tab) -->
      <AppMainHeader v-if="showNav" show-pengumuman />
      <router-view />
    </main>
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
</script>
