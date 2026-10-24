<template>
  <div>
    <AppBackHeader title="Menu" />

    <div v-for="g in visibleGroups" :key="g.title" class="mb-6">
      <p class="text-[15px] font-bold text-[var(--text)] m-0 mb-3 px-0.5">{{ g.title }}</p>
      <div class="grid grid-cols-4 gap-x-2 gap-y-4">
        <button
          v-for="m in g.items"
          :key="m.to + m.label"
          type="button"
          class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform"
          @click="$router.push(m.to)"
        >
          <span
            class="w-14 h-14 rounded-[18px] grid place-items-center text-white shrink-0"
            :style="{ background: m.bg }"
          >
            <component :is="m.icon" :size="24" :stroke-width="2" />
          </span>
          <span class="text-[11px] font-semibold leading-tight text-center text-[var(--text)] px-0.5">
            {{ m.label }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  Banknote, FileText, Wallet, Settings2, CreditCard, Layers,
  UserPlus, ScanLine, Download, Shield,
  Megaphone, ClipboardList, Baby, BookOpen, Leaf, Trophy, Bike, Fish,
  Swords, Activity, CalendarDays, Building2, Ambulance, HandCoins, Gift,
  Images, Network, Settings, Users,
} from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useAuth } from '@shared/composables/useAuth.js'
import { MENU } from '@shared/constants/menuColors.js'

const { user } = useAuth()
const isKetua = computed(() => user.value?.role === 'ketua' || user.value?.is_developer)

const groups = [
  {
    title: 'Keuangan',
    items: [
      { label: 'Catat iuran', icon: Banknote, to: '/keuangan/catat', bg: MENU.catatIuran },
      { label: 'Laporan', icon: FileText, to: '/keuangan/laporan', bg: MENU.laporan },
      { label: 'Iuran khusus', icon: Banknote, to: '/keuangan/iuran-khusus', bg: MENU.iuranKhusus },
      { label: 'Pos kas', icon: Wallet, to: '/keuangan/pos-kas', bg: MENU.posKas },
      { label: 'Sub pos', icon: Layers, to: '/keuangan/sub-pos', bg: MENU.subPos },
      { label: 'Tarif iuran', icon: Settings2, to: '/pengaturan-warga', bg: MENU.tarifIuran },
      { label: 'Rekening', icon: CreditCard, to: '/rekening-pembayaran', bg: MENU.rekening },
    ],
  },
  {
    title: 'Warga',
    items: [
      { label: 'Tambah', icon: UserPlus, to: '/warga/tambah', bg: MENU.tambahKeluarga },
      { label: 'Scan KK', icon: ScanLine, to: '/warga/scan-kk', bg: MENU.scanKk },
      { label: 'Ekspor', icon: Download, to: '/warga/ekspor', bg: MENU.ekspor },
    ],
  },
  {
    title: 'Ronda',
    items: [
      { label: 'Jadwal ronda', icon: Shield, to: '/ronda', bg: MENU.ronda },
    ],
  },
  {
    title: 'Konten warga',
    items: [
      { label: 'Pengumuman', icon: Megaphone, to: '/konten/pengumuman', bg: MENU.pengumuman },
      { label: 'Program RT', icon: ClipboardList, to: '/konten/program', bg: MENU.program },
      { label: 'Galeri', icon: Images, to: '/konten/galeri', bg: MENU.galeri },
      { label: 'Struktur', icon: Network, to: '/konten/struktur', bg: MENU.struktur },
      { label: 'Reward', icon: Gift, to: '/reward', bg: MENU.reward },
      { label: 'RW', icon: Building2, to: '/konten/rw', bg: MENU.rw },
      { label: 'Ambulance', icon: Ambulance, to: '/konten/ambulance', bg: MENU.ambulance },
    ],
  },
  {
    title: 'Komunitas',
    items: [
      { label: 'Bulutangkis', icon: Trophy, to: '/konten/bulutangkis', bg: MENU.bulutangkis },
      { label: 'Gowes', icon: Bike, to: '/konten/gowes', bg: MENU.gowes },
      { label: 'Mancing', icon: Fish, to: '/konten/mancing', bg: MENU.mancing },
      { label: 'Pencak silat', icon: Swords, to: '/konten/silat', bg: MENU.silat },
      { label: 'Senam', icon: Activity, to: '/konten/senam', bg: MENU.senam },
    ],
  },
  {
    title: 'Kader RT 002',
    items: [
      { label: 'Posyandu', icon: Baby, to: '/konten/posyandu', bg: MENU.posyandu },
      { label: 'Pengajian', icon: BookOpen, to: '/konten/pengajian', bg: MENU.pengajian },
      { label: 'Lingkungan', icon: Leaf, to: '/konten/lingkungan', bg: MENU.lingkungan },
    ],
  },
  {
    title: 'DKM',
    items: [
      { label: "Ta'lim", icon: BookOpen, to: '/konten/talim', bg: MENU.talim },
      { label: 'PHBI', icon: CalendarDays, to: '/konten/phbi', bg: MENU.phbi },
      { label: 'Bazis', icon: HandCoins, to: '/konten/bazis', bg: MENU.bazis },
    ],
  },
  {
    title: 'Sistem',
    ketuaOnly: true,
    items: [
      { label: 'Pengaturan', icon: Settings, to: '/pengaturan-aplikasi', bg: MENU.pengaturan },
      { label: 'Pengurus', icon: Users, to: '/kelola-pengurus', bg: MENU.kelolaPengurus },
    ],
  },
]

const visibleGroups = computed(() => groups.filter((g) => !g.ketuaOnly || isKetua.value))
</script>
