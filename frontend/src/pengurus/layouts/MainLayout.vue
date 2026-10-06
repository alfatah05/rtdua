<template>
  <div class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <!-- Desktop sidebar placeholder (>=1024px) nanti dilengkapi -->
    <main class="max-w-[1000px] mx-auto px-4 pt-4 pb-[calc(96px+env(safe-area-inset-bottom,0px))] lg:pb-8">
      <router-view />
    </main>
    <div class="lg:hidden">
      <AppBottomNav side="pengurus" />
    </div>
  </div>
</template>

<script setup>
import AppBottomNav from '@shared/components/AppBottomNav.vue'
</script>
