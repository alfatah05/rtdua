<template>
  <div>
    <AppBackHeader :title="title" />
    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5">
      <p class="text-[var(--mut)] m-0">Halaman {{ title }}.</p>
      <p class="text-[13px] text-[var(--mut)] mt-2 m-0">Fitur lengkap menyusul.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'

const route = useRoute()
const title = computed(() => route.meta.title || 'Halaman')
</script>
