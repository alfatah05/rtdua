<template>
  <header class="flex items-center gap-1 mb-5">
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
