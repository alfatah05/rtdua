<template>
  <div>
    <AppMainHeader show-profil />
    <h1 class="text-[20px] font-extrabold m-0 mb-4">Lainnya</h1>

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
  Banknote, ArrowDownLeft, ArrowUpRight, FileText, ClipboardList, Settings2, CreditCard,
  UserPlus, ScanLine, Download, Shield, Megaphone, Images, Network, Users, Settings, Wallet, Baby, BookOpen, Leaf, HandCoins, Trophy, Layers, CalendarDays,
  Bike, Fish, Swords, Activity, Building2, Ambulance, Gift,
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
      { label: 'Pos kas', icon: Wallet, to: '/keuangan/pos-kas', bg: 'rgba(16,185,129,.18)', color: '#059669' },
      { label: 'Sub pos', icon: Layers, to: '/keuangan/sub-pos', bg: 'rgba(16,185,129,.18)', color: '#059669' },
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
      { label: 'Posyandu', icon: Baby, to: '/konten/posyandu', bg: 'rgba(249,115,22,.18)', color: '#EA580C' },
      { label: 'Pengajian', icon: BookOpen, to: '/konten/pengajian', bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
      { label: 'Lingkungan', icon: Leaf, to: '/konten/lingkungan', bg: 'rgba(34,184,99,.18)', color: '#0B8442' },
      { label: 'Bulutangkis', icon: Trophy, to: '/konten/bulutangkis', bg: 'rgba(14,165,233,.18)', color: '#0369A1' },
      { label: 'Gowes', icon: Bike, to: '/konten/gowes', bg: 'rgba(34,197,94,.18)', color: '#15803D' },
      { label: 'Mancing', icon: Fish, to: '/konten/mancing', bg: 'rgba(6,182,212,.18)', color: '#0E7490' },
      { label: 'Pencak silat', icon: Swords, to: '/konten/silat', bg: 'rgba(239,68,68,.18)', color: '#B91C1C' },
      { label: 'Senam', icon: Activity, to: '/konten/senam', bg: 'rgba(245,158,11,.18)', color: '#B45309' },
      { label: "Ta'lim", icon: BookOpen, to: '/konten/talim', bg: 'rgba(14,165,233,.18)', color: '#0369A1' },
      { label: 'PHBI', icon: CalendarDays, to: '/konten/phbi', bg: 'rgba(139,92,246,.18)', color: '#6D28D9' },
      { label: 'RW', icon: Building2, to: '/konten/rw', bg: 'rgba(100,116,139,.18)', color: '#334155' },
      { label: 'Ambulance', icon: Ambulance, to: '/konten/ambulance', bg: 'rgba(220,38,38,.18)', color: '#991B1B' },
      { label: 'Bazis', icon: HandCoins, to: '/konten/bazis', bg: 'rgba(5,150,105,.18)', color: '#047857' },
      { label: 'Reward', icon: Gift, to: '/reward', bg: 'rgba(245,158,11,.18)', color: '#D97706' },
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
