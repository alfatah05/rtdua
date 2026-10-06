<template>
  <div>
    <AppBackHeader title="Lainnya" />

    <div v-for="group in visibleGroups" :key="group.title" class="mb-6">
      <h2 class="text-[13px] font-bold text-[var(--mut)] mb-2 px-1">{{ group.title }}</h2>
      <div class="grid grid-cols-4 gap-3">
        <button
          v-for="item in group.items"
          :key="item.label"
          type="button"
          class="flex flex-col items-center gap-1.5 text-center"
          @click="$router.push(item.to)"
        >
          <span class="w-12 h-12 rounded-full grid place-items-center" :style="{ background: item.bg, color: item.color }">
            <component :is="item.icon" :size="20" />
          </span>
          <span class="text-[11px] font-semibold leading-tight">{{ item.label }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  Banknote, ArrowDownLeft, ArrowUpRight, FileText, ClipboardList, Settings2,
  UserPlus, ScanLine, Download, Shield, Clock, Megaphone, Images, Network, Users, Settings
} from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useAuth } from '@shared/composables/useAuth.js'

const { isKetua } = useAuth()

const groups = [
  {
    title: 'Keuangan',
    items: [
      { label: 'Catat iuran', icon: Banknote, to: '/keuangan/catat', bg: 'rgba(16,185,129,.18)', color: '#059669' },
      { label: 'Kas masuk', icon: ArrowDownLeft, to: '/keuangan/kas-masuk', bg: 'rgba(16,185,129,.18)', color: '#059669' },
      { label: 'Kas keluar', icon: ArrowUpRight, to: '/keuangan/kas-keluar', bg: 'rgba(16,185,129,.18)', color: '#059669' },
      { label: 'Iuran', icon: ClipboardList, to: '/keuangan/iuran', bg: 'rgba(16,185,129,.18)', color: '#059669' },
      { label: 'Laporan', icon: FileText, to: '/keuangan/laporan', bg: 'rgba(16,185,129,.18)', color: '#059669' },
      { label: 'Iuran khusus', icon: Banknote, to: '/keuangan/iuran-khusus', bg: 'rgba(16,185,129,.18)', color: '#059669' },
      { label: 'Tarif & denda', icon: Settings2, to: '/pengaturan-warga', bg: 'rgba(16,185,129,.18)', color: '#059669' },
    ],
  },
  {
    title: 'Warga',
    items: [
      { label: 'Tambah keluarga', icon: UserPlus, to: '/warga/tambah', bg: 'rgba(6,182,212,.18)', color: '#0891B2' },
      { label: 'Scan KK', icon: ScanLine, to: '/warga/scan-kk', bg: 'rgba(6,182,212,.18)', color: '#0891B2' },
      { label: 'Ekspor', icon: Download, to: '/warga/ekspor', bg: 'rgba(6,182,212,.18)', color: '#0891B2' },
    ],
  },
  {
    title: 'Ronda',
    items: [
      { label: 'Jadwal ronda', icon: Shield, to: '/ronda', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
      { label: 'Jam ronda', icon: Clock, to: '/pengaturan-warga', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
    ],
  },
  {
    title: 'Konten warga',
    items: [
      { label: 'Pengumuman', icon: Megaphone, to: '/konten/pengumuman', bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
      { label: 'Program RT', icon: ClipboardList, to: '/konten/program', bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
      { label: 'Galeri', icon: Images, to: '/konten/galeri', bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
      { label: 'Struktur', icon: Network, to: '/konten/struktur', bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
    ],
  },
  {
    title: 'Sistem',
    ketuaOnly: true,
    items: [
      { label: 'Pengaturan', icon: Settings, to: '/pengaturan-aplikasi', bg: 'rgba(107,114,128,.18)', color: '#4B5563' },
      { label: 'Kelola pengurus', icon: Users, to: '/kelola-pengurus', bg: 'rgba(107,114,128,.18)', color: '#4B5563' },
    ],
  },
]

const visibleGroups = computed(() => groups.filter(g => !g.ketuaOnly || isKetua.value))
</script>
