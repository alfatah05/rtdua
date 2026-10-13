<template>
  <div>
    <AppMainHeader title="Lainnya" />
    <div v-for="g in visibleGroups" :key="g.title" class="mb-5">
      <h2 class="text-[13px] font-bold text-[var(--mut)] mb-2 px-1">{{ g.title }}</h2>
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="it in g.items"
          :key="it.label"
          type="button"
          class="flex flex-col items-center gap-2 p-3 rounded-[16px] bg-[var(--card)] border border-[var(--line)] active:scale-[0.98] transition"
          @click="$router.push(it.to)"
        >
          <div
            class="w-11 h-11 rounded-full grid place-items-center"
            :style="{ background: it.bg, color: it.color }"
          >
            <component :is="it.icon" :size="20" />
          </div>
          <span class="text-[11px] font-semibold text-center leading-tight">{{ it.label }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  Banknote, ArrowDownLeft, ArrowUpRight, ClipboardList, FileText, Settings2,
  CreditCard, UserPlus, ScanLine, Download, Shield, Megaphone, Images, Network,
  Settings, Users,
} from 'lucide-vue-next'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
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
      { label: 'Tarif iuran', icon: Settings2, to: '/pengaturan-warga', bg: 'rgba(16,185,129,.18)', color: '#059669' },
      { label: 'Rekening bayar', icon: CreditCard, to: '/rekening-pembayaran', bg: 'rgba(16,185,129,.18)', color: '#059669' },
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
