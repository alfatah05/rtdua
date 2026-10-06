<template>
  <div>
    <header class="flex items-center gap-3 mb-5">
      <button type="button" class="min-h-[44px] px-3 -ml-2 font-semibold text-[var(--g)]" @click="$router.back()">← Kembali</button>
      <h1 class="text-xl font-extrabold m-0">{{ title }}</h1>
    </header>
    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)]">
      <p class="text-[var(--mut)] m-0">Halaman {{ title }} — kerangka Stage 4.</p>
      <p class="text-[13px] text-[var(--mut)] mt-2 m-0">Fitur lengkap akan diisi di Stage berikutnya. Tombol Kembali sudah berfungsi.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
const route = useRoute()
const title = computed(() => route.meta.title || 'Halaman')
</script>
