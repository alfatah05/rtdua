<template>
  <div class="min-h-screen bg-[var(--bg)] text-[var(--text)]">
    <main class="max-w-[560px] mx-auto px-4 pt-4 pb-[calc(96px+env(safe-area-inset-bottom,0px))]">
      <router-view />
    </main>
    <AppBottomNav side="warga" />
  </div>
</template>

<script setup>
import AppBottomNav from '@shared/components/AppBottomNav.vue'
</script>
