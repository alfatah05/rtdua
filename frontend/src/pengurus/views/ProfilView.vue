<template>
  <div>
    <AppBackHeader title="Profil" />

    <!-- Profile hero -->
    <div class="flex flex-col items-center text-center mb-6 pt-2">
      <button
        type="button"
        class="relative w-24 h-24 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center text-3xl font-bold overflow-hidden mb-3 active:scale-95 transition-transform"
        aria-label="Ubah foto profil"
        @click="onFoto"
      >
        <img v-if="foto" :src="foto" alt="Foto profil" class="w-full h-full object-cover" />
        <span v-else>{{ inisial }}</span>
        <span class="absolute bottom-0 right-0 w-8 h-8 rounded-full bg-[var(--g)] text-white grid place-items-center border-2 border-[var(--bg)]">
          <Camera :size="14" />
        </span>
      </button>
      <p class="text-xl font-extrabold m-0">{{ user?.nama || 'Pengurus' }}</p>
      <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5">{{ user?.jabatan || user?.role || '—' }}</p>
      <p class="text-[13px] text-[var(--mut)] m-0">@{{ user?.username || '—' }}</p>
    </div>

    <div class="px-1 space-y-0.5">
      <button
        v-for="m in menus"
        :key="m.label"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3.5 text-left active:scale-[0.99] transition-transform"
        @click="m.action ? m.action() : $router.push(m.to)"
      >
        <span class="w-10 h-10 rounded-full grid place-items-center shrink-0" :style="{ background: m.bg, color: m.color }">
          <component :is="m.icon" :size="18" />
        </span>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-[15px] m-0">{{ m.label }}</p>
          <p v-if="m.desc" class="text-[12px] text-[var(--mut)] m-0">{{ m.desc }}</p>
        </div>
        <span v-if="m.right" class="text-[13px] text-[var(--mut)] shrink-0">{{ m.right }}</span>
        <ChevronRight v-else :size="18" class="text-[var(--mut)] shrink-0" />
      </button>
    </div>

    <button
      type="button"
      class="w-full min-h-[48px] mt-8 rounded-full bg-[var(--card2)] font-bold active:scale-[0.98] transition-transform"
      @click="doLogout"
    >
      Keluar
    </button>

    <p class="text-center text-[12px] text-[var(--mut)] mt-6 mb-2">rtdua · versi 0.1.0</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  Camera, ChevronRight, KeyRound, Moon, Settings, Users, Info, Shield
} from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useAuth } from '@shared/composables/useAuth.js'
import { useTheme } from '@shared/composables/useTheme.js'
import { useToast } from '@shared/composables/useToast.js'

const router = useRouter()
const { user, logout, isKetua } = useAuth()
const { mode, toggle } = useTheme()
const { show } = useToast()
const foto = ref(null)

const inisial = computed(() => {
  const n = user.value?.nama || 'P'
  return n.split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase()
})

const menus = computed(() => {
  const list = [
    {
      label: 'Ganti password',
      desc: 'Ubah kata sandi masuk',
      icon: KeyRound,
      to: '/profil',
      bg: 'rgba(16,185,129,.18)',
      color: '#059669',
      action: () => show('Ganti password menyusul'),
    },
    {
      label: 'Mode gelap',
      desc: mode.value === 'dark' ? 'Sedang aktif' : 'Nonaktif',
      icon: Moon,
      action: () => toggle(),
      right: mode.value === 'dark' ? 'Aktif' : 'Off',
      bg: 'rgba(107,114,128,.18)',
      color: '#4B5563',
    },
  ]
  if (isKetua.value) {
    list.push(
      {
        label: 'Pengaturan aplikasi',
        desc: 'Nama RT, blok, akses warga',
        icon: Settings,
        to: '/pengaturan-aplikasi',
        bg: 'rgba(59,130,246,.18)',
        color: '#2563EB',
      },
      {
        label: 'Kelola pengurus',
        desc: 'Angkat / lepas pengurus',
        icon: Users,
        to: '/kelola-pengurus',
        bg: 'rgba(6,182,212,.18)',
        color: '#0891B2',
      },
    )
  }
  list.push({
    label: 'Tentang aplikasi',
    desc: 'rtdua untuk pengurus RT',
    icon: Info,
    action: () => show('rtdua v0.1.0'),
    bg: 'rgba(168,85,247,.18)',
    color: '#7C3AED',
  })
  return list
})

function onFoto() {
  show('Upload foto menyusul (backend)')
}
function doLogout() {
  logout('pengurus')
  router.replace('/login')
}
</script>
