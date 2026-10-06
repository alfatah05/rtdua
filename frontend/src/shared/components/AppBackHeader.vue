<template>
  <header class="sticky top-0 z-30 -mx-4 px-4 pt-1 pb-3 mb-3 flex items-center gap-1 bg-[var(--bg)]">
    <button
      type="button"
      class="w-11 h-11 -ml-2 grid place-items-center rounded-full text-[var(--text)] shrink-0"
      aria-label="Kembali"
      @click="goBack"
    >
      <ChevronLeft :size="24" :stroke-width="2.25" />
    </button>
    <h1 class="flex-1 text-xl font-extrabold m-0 text-[var(--text)] leading-tight truncate">{{ title }}</h1>
    <router-link
      v-if="showActions"
      to="/notifikasi"
      class="w-11 h-11 grid place-items-center rounded-full text-[var(--text)] shrink-0"
      aria-label="Notifikasi"
    >
      <Bell :size="20" />
    </router-link>
    <router-link
      v-if="showActions"
      to="/profil"
      class="w-11 h-11 grid place-items-center rounded-full text-[var(--text)] shrink-0"
      aria-label="Profil"
    >
      <UserRound :size="20" />
    </router-link>
  </header>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { ChevronLeft, Bell, UserRound } from 'lucide-vue-next'

defineProps({
  title: { type: String, required: true },
  showActions: { type: Boolean, default: true },
})

const router = useRouter()
function goBack() {
  router.back()
}
</script>
