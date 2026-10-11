<template>
  <header
    class="sticky top-0 z-30 -mx-4 flex items-center bg-[var(--bg)]"
    :style="{
      paddingTop: 'max(8px, env(safe-area-inset-top, 0px))',
      paddingBottom: '10px',
      paddingLeft: '8px',
      paddingRight: '16px',
    }"
  >
    <button
      type="button"
      class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)] shrink-0"
      style="margin-left: -2px"
      aria-label="Kembali"
      @click="goBack"
    >
      <ChevronLeft :size="24" :stroke-width="2.25" />
    </button>
    <h1 class="flex-1 text-xl font-extrabold m-0 text-[var(--text)] leading-tight truncate pl-1">{{ title }}</h1>
    <div v-if="showNotif || showProfilIcon" class="flex items-center shrink-0" style="margin-right: -6px">
      <router-link
        v-if="showNotif"
        to="/notifikasi"
        class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]"
        aria-label="Notifikasi"
      >
        <Bell :size="20" />
      </router-link>
      <router-link
        v-if="showProfilIcon"
        to="/profil"
        class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]"
        aria-label="Profil"
      >
        <UserRound :size="20" />
      </router-link>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { ChevronLeft, Bell, UserRound } from 'lucide-vue-next'

const props = defineProps({
  title: { type: String, required: true },
  showActions: { type: Boolean, default: true },
  showProfil: { type: Boolean, default: null },
})

const showNotif = computed(() => props.showActions)

const showProfilIcon = computed(() => {
  if (!props.showActions) return false
  if (props.showProfil === true) return true
  if (props.showProfil === false) return false
  try {
    const h = String(location.hostname || '')
    if (h.includes('warga')) return false
    if (h.includes('pengurus')) return true
  } catch (e) {}
  try {
    if (typeof import.meta !== 'undefined' && import.meta.env?.VITE_APP_SIDE === 'warga') return false
  } catch (e) {}
  return true
})

const router = useRouter()
function goBack() {
  router.back()
}
</script>
