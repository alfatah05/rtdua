<template>
  <div>
    <header class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-xl font-extrabold">Beranda</h1>
        <p class="text-[13px] text-[var(--mut)]">{{ user?.nama || 'Warga' }}</p>
      </div>
      <router-link to="/notifikasi" class="w-11 h-11 grid place-items-center rounded-full bg-[var(--search)]" aria-label="Notifikasi">
        <Bell :size="20" />
      </router-link>
    </header>

    <UiCard class="mb-4">
      <p class="text-[13px] font-semibold text-[var(--mut)]">Iuran yang belum dibayar</p>
      <p class="text-2xl font-extrabold mt-1">Rp 10.000</p>
      <p class="text-[13px] text-[var(--mut)] mt-1">Ketuk untuk melihat rincian</p>
    </UiCard>

    <div class="grid grid-cols-2 gap-2">
      <UiCard v-for="m in menus" :key="m.to" clickable class="!p-4" @click="$router.push(m.to)">
        <div class="w-11 h-11 rounded-full grid place-items-center mb-2" :style="{ background: m.bg, color: m.color }">
          <component :is="m.icon" :size="20" />
        </div>
        <p class="font-bold text-[15px]">{{ m.label }}</p>
        <p class="text-[13px] text-[var(--mut)]">{{ m.desc }}</p>
      </UiCard>
    </div>
  </div>
</template>

<script setup>
import { Bell, Megaphone, ShieldCheck, ClipboardList, Images, Network, MessageCircle } from 'lucide-vue-next'
import UiCard from '@shared/components/UiCard.vue'
import { useAuth } from '@shared/composables/useAuth.js'

const { user } = useAuth()

const menus = [
  { to: '/pengumuman', label: 'Pengumuman', desc: 'Kabar dari pengurus', icon: Megaphone, bg: 'rgba(245,158,11,.18)', color: '#D97706' },
  { to: '/ronda', label: 'Jadwal Ronda', desc: 'Giliran jaga malam', icon: ShieldCheck, bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { to: '/program', label: 'Program RT', desc: 'Kegiatan dan rencana', icon: ClipboardList, bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
  { to: '/galeri', label: 'Galeri RT', desc: 'Foto kegiatan', icon: Images, bg: 'rgba(236,72,153,.18)', color: '#DB2777' },
  { to: '/struktur', label: 'Struktur', desc: 'Pengurus RT', icon: Network, bg: 'rgba(99,102,241,.18)', color: '#4F46E5' },
  { to: '/bantuan', label: 'Bantuan', desc: 'Hubungi pengurus', icon: MessageCircle, bg: 'rgba(34,197,94,.18)', color: '#16A34A' },
]
</script>
