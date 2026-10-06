<template>
  <header class="flex items-center gap-2 mb-5">
    <button
      type="button"
      class="w-11 h-11 -ml-2 grid place-items-center rounded-full text-[var(--text)] shrink-0"
      aria-label="Kembali"
      @click="goBack"
    >
      <ChevronLeft :size="24" :stroke-width="2.25" />
    </button>
    <h1 class="text-xl font-extrabold m-0 text-[var(--text)] leading-tight">{{ title }}</h1>
  </header>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { ChevronLeft } from 'lucide-vue-next'

defineProps({
  title: { type: String, required: true },
})

const router = useRouter()
function goBack() {
  router.back()
}
</script>
