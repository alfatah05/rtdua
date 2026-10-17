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
import { useRouter, useRoute } from 'vue-router'
import { ChevronLeft, Bell, UserRound, Megaphone } from 'lucide-vue-next'
import { useNotifBadge } from '@shared/composables/useNotifBadge.js'

/**
 * Parent logis per route name (warga + pengurus yang share komponen ini).
 * Back selalu ke parent, bukan history browser — supaya setelah submit/form
 * tidak perlu tekan back berkali-kali.
 */
const PARENT_BY_ROUTE = {
  // warga
  'warga': '/',
  'warga-detail': '/warga',
  'keuangan': '/',
  'data-keluarga': '/profil',
  'data-keluarga-tambah': '/data-keluarga',
  'data-keluarga-edit': '/data-keluarga',
  'pengumuman': '/',
  'pengumuman-detail': '/pengumuman',
  'rincian-iuran': '/keuangan',
  'transfer': '/rincian-iuran',
  'bayar': '/rincian-iuran',
  'bayar-detail': '/bayar',
  'ronda': '/',
  'bantuan': '/profil',
  'notifikasi': '/',
  'notifikasi-detail': '/notifikasi',
  'ganti-pin': '/',
  'program-detail': '/program',
  'galeri': '/',
  'galeri-detail': '/galeri',
  'posyandu': '/',
  'talim': '/',
  // pengurus (nama route yang umum)
  'warga-edit': '/warga',
  'warga-tambah': '/warga',
  'iuran-detail': '/iuran',
  'kas-detail': '/kas',
}

const props = defineProps({
  title: { type: String, required: true },
  /**
   * Parent eksplisit (path atau name object).
   * Prioritas tertinggi di atas PARENT_BY_ROUTE.
   */
  to: { type: [String, Object], default: null },
  /** Tampilkan aksi kanan (pengumuman/notif/profil). Default true. */
  showActions: { type: Boolean, default: true },
  /** Override icon notif. null = ikut showActions. */
  showNotif: { type: Boolean, default: null },
  /** Override icon profil. null = auto (warga=false, pengurus=true). */
  showProfil: { type: Boolean, default: null },
  /** Icon pengumuman. null = auto (warga=true, pengurus=false). */
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
  try {
    const h = String(location.hostname || '')
    if (h.includes('pengurus')) return false
    if (h.includes('warga')) return true
  } catch (e) {}
  try {
    if (typeof import.meta !== 'undefined' && import.meta.env?.VITE_APP_SIDE === 'pengurus') return false
    if (typeof import.meta !== 'undefined' && import.meta.env?.VITE_APP_SIDE === 'warga') return true
  } catch (e) {}
  return false
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
const route = useRoute()

function resolveParent() {
  if (props.to != null && props.to !== '') return props.to
  const name = String(route.name || '')
  if (name && PARENT_BY_ROUTE[name]) return PARENT_BY_ROUTE[name]
  // path-based fallback (jika name tidak terdaftar)
  const path = String(route.path || '')
  if (path.startsWith('/data-keluarga/')) return '/data-keluarga'
  if (path.startsWith('/pengumuman/')) return '/pengumuman'
  if (path.startsWith('/notifikasi/')) return '/notifikasi'
  if (path.startsWith('/warga/')) return '/warga'
  if (path.startsWith('/program/')) return '/program'
  if (path.startsWith('/galeri/')) return '/galeri'
  if (path.startsWith('/bayar')) return '/rincian-iuran'
  if (path === '/rincian-iuran') return '/keuangan'
  if (path === '/keuangan' || path === '/ronda' || path === '/pengumuman') return '/'
  return '/'
}

function goBack() {
  const parent = resolveParent()
  // replace: tidak menumpuk history, back berikutnya tidak kembali ke child
  router.replace(parent)
}
</script>
