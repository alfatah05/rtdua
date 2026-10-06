<template>
  <div>
    <header class="flex items-center gap-3 mb-6">
      <button
        type="button"
        class="min-h-[44px] px-3 -ml-2 font-semibold text-[var(--g)]"
        @click="$router.back()"
      >
        ← Kembali
      </button>
      <h1 class="text-xl font-extrabold">{{ title }}</h1>
    </header>
    <UiCard>
      <p class="text-[var(--mut)]">Halaman {{ title }} — Stage 1 (placeholder).</p>
      <p class="text-[13px] text-[var(--mut)] mt-2">Fitur lengkap menyusul di Stage berikutnya.</p>
    </UiCard>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import UiCard from '@shared/components/UiCard.vue'

const route = useRoute()
const title = computed(() => route.meta.title || 'Halaman')
</script>
