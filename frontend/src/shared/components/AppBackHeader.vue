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
    <div v-if="$slots.actions || showPengumumanIcon || showNotifIcon || showProfilIcon" class="flex items-center shrink-0" style="margin-right: -6px">
      <slot name="actions" />
      <router-link
        v-if="showPengumumanIcon"
        to="/pengumuman"
        class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]"
        aria-label="Pengumuman"
      >
        <Megaphone :size="20" />
      </router-link>
      <router-link
        v-if="showNotifIcon"
        to="/notifikasi"
        class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)] relative"
        aria-label="Notifikasi"
      >
        <Bell :size="20" />
        <span
          v-if="badge > 0"
          class="absolute top-1 right-1 min-w-[16px] h-[16px] px-0.5 rounded-full bg-[var(--g)] text-white text-[10px] font-bold grid place-items-center"
        >{{ badge > 9 ? '9+' : badge }}</span>
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
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ChevronLeft, Bell, UserRound, Megaphone } from 'lucide-vue-next'
import { useNotifBadge } from '@shared/composables/useNotifBadge.js'

const props = defineProps({
  title: { type: String, required: true },
  /** Tampilkan aksi kanan (pengumuman/notif/profil). Default true. */
  showActions: { type: Boolean, default: true },
  /** Override icon notif. null = ikut showActions. */
  showNotif: { type: Boolean, default: null },
  /** Override icon profil. null = auto (warga=false, pengurus=true). */
  showProfil: { type: Boolean, default: null },
  /** Icon pengumuman. null = tampil bila showActions (child warga). */
  showPengumuman: { type: Boolean, default: null },
})

const showNotifIcon = computed(() => {
  if (!props.showActions) return false
  if (props.showNotif === false) return false
  if (props.showNotif === true) return true
  return true
})

const showPengumumanIcon = computed(() => {
  if (!props.showActions) return false
  if (props.showPengumuman === false) return false
  if (props.showPengumuman === true) return true
  // default: tampil di halaman child (sama seperti notif)
  return true
})

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

const { badge, ensure } = useNotifBadge()
onMounted(() => {
  if (showNotifIcon.value) ensure()
})

const router = useRouter()
function goBack() {
  router.back()
}
</script>
