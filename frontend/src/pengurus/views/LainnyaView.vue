<template>
  <div>
    <AppBackHeader title="Menu" />

    <div v-for="g in visibleGroups" :key="g.title" class="mb-5">
      <p class="text-[13px] font-bold text-[var(--mut)] m-0 mb-2 px-0.5">{{ g.title }}</p>
      <div class="flex flex-col gap-2.5">
        <button
          v-for="m in g.items"
          :key="m.to + m.label"
          type="button"
          class="menu-row w-full text-left relative overflow-hidden touch-manipulation will-change-transform"
          :class="pressedKey === m.to ? 'is-pressed' : ''"
          :style="{ background: m.bg }"
          @click="goTo(m.to)"
        >
          <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-[64px]">
            <div class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0 bg-white/20 text-white">
              <component :is="m.icon" :size="22" />
            </div>
            <div class="flex-1 min-w-0 py-2.5">
              <p class="text-[15px] font-bold m-0 leading-tight text-white truncate">{{ m.label }}</p>
              <p v-if="m.desc" class="text-[13px] font-semibold m-0 mt-0.5 leading-tight text-white/85 truncate">
                {{ m.desc }}
              </p>
            </div>
            <ChevronRight :size="18" class="shrink-0 text-white/70" />
          </div>
          <div class="absolute -right-8 -bottom-10 w-28 h-28 rounded-full bg-white/10 pointer-events-none" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  Banknote, FileText, Wallet, Settings2, CreditCard, Layers,
  UserPlus, ScanLine, Download, Shield,
  Megaphone, ClipboardList, Baby, BookOpen, Leaf, Trophy, Bike, Fish,
  Swords, Activity, CalendarDays, Building2, Ambulance, HandCoins, Gift,
  Images, Network, Settings, Users, ChevronRight,
} from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useAuth } from '@shared/composables/useAuth.js'

const router = useRouter()
const { user } = useAuth()
const isKetua = computed(() => user.value?.role === 'ketua' || user.value?.is_developer)
const pressedKey = ref('')
let navLock = false

async function goTo(to) {
  if (navLock) return
  navLock = true
  pressedKey.value = to
  await new Promise((r) => setTimeout(r, 90))
  pressedKey.value = ''
  await new Promise((r) => setTimeout(r, 160))
  try {
    await router.push(to)
  } finally {
    navLock = false
  }
}

/**
 * Warna solid dari pola dashboard warga (HomeView quickTiles / menus).
 * Jangan invent warna baru.
 */
const C = {
  amber: '#F59E0B',
  pink: '#EC4899',
  teal: '#14B8A6',
  orange: '#F97316',
  sky: '#0EA5E9',
  slate: '#64748B',
  red: '#DC2626',
  purple: '#A855F7',
  blue: '#3B82F6',
  cyan: '#06B6D4',
  green: '#22B863',
}

const groups = [
  {
    title: 'Keuangan',
    items: [
      { label: 'Catat iuran', desc: 'Bayar iuran warga', icon: Banknote, to: '/keuangan/catat', bg: C.green },
      { label: 'Laporan', desc: 'Rekap kas & iuran', icon: FileText, to: '/keuangan/laporan', bg: C.purple },
      { label: 'Iuran khusus', desc: 'Tagihan khusus keluarga', icon: Banknote, to: '/keuangan/iuran-khusus', bg: C.amber },
      { label: 'Pos kas', desc: 'Kelola pos kas', icon: Wallet, to: '/keuangan/pos-kas', bg: C.teal },
      { label: 'Sub pos', desc: 'Saldo iuran belum disetor', icon: Layers, to: '/keuangan/sub-pos', bg: C.cyan },
      { label: 'Tarif iuran', desc: 'Atur komponen iuran', icon: Settings2, to: '/pengaturan-warga', bg: C.slate },
      { label: 'Rekening bayar', desc: 'Rekening transfer warga', icon: CreditCard, to: '/rekening-pembayaran', bg: C.blue },
    ],
  },
  {
    title: 'Warga',
    items: [
      { label: 'Tambah keluarga', desc: 'Daftarkan KK baru', icon: UserPlus, to: '/warga/tambah', bg: C.cyan },
      { label: 'Scan KK', desc: 'Input dari foto KK', icon: ScanLine, to: '/warga/scan-kk', bg: C.sky },
      { label: 'Ekspor', desc: 'Unduh data warga', icon: Download, to: '/warga/ekspor', bg: C.slate },
    ],
  },
  {
    title: 'Ronda',
    items: [
      { label: 'Jadwal ronda', desc: 'Kelola jadwal & absen', icon: Shield, to: '/ronda', bg: C.blue },
    ],
  },
  {
    title: 'Konten warga',
    items: [
      { label: 'Pengumuman', icon: Megaphone, to: '/konten/pengumuman', bg: C.purple },
      { label: 'Program RT', icon: ClipboardList, to: '/konten/program', bg: C.purple },
      { label: 'Posyandu', icon: Baby, to: '/konten/posyandu', bg: C.orange },
      { label: 'Pengajian', icon: BookOpen, to: '/konten/pengajian', bg: C.purple },
      { label: 'Lingkungan', icon: Leaf, to: '/konten/lingkungan', bg: C.green },
      { label: 'Bulutangkis', icon: Trophy, to: '/konten/bulutangkis', bg: C.sky },
      { label: 'Gowes', icon: Bike, to: '/konten/gowes', bg: C.green },
      { label: 'Mancing', icon: Fish, to: '/konten/mancing', bg: C.cyan },
      { label: 'Pencak silat', icon: Swords, to: '/konten/silat', bg: C.red },
      { label: 'Senam', icon: Activity, to: '/konten/senam', bg: C.amber },
      { label: "Ta'lim", icon: BookOpen, to: '/konten/talim', bg: C.sky },
      { label: 'PHBI', icon: CalendarDays, to: '/konten/phbi', bg: C.purple },
      { label: 'RW', icon: Building2, to: '/konten/rw', bg: C.slate },
      { label: 'Ambulance', icon: Ambulance, to: '/konten/ambulance', bg: C.red },
      { label: 'Bazis', icon: HandCoins, to: '/konten/bazis', bg: C.green },
      { label: 'Reward', icon: Gift, to: '/reward', bg: C.amber },
      { label: 'Galeri', icon: Images, to: '/konten/galeri', bg: C.pink },
      { label: 'Struktur', icon: Network, to: '/konten/struktur', bg: C.purple },
    ],
  },
  {
    title: 'Sistem',
    ketuaOnly: true,
    items: [
      { label: 'Pengaturan', desc: 'Pengaturan aplikasi', icon: Settings, to: '/pengaturan-aplikasi', bg: C.slate },
      { label: 'Kelola pengurus', desc: 'Akun & peran pengurus', icon: Users, to: '/kelola-pengurus', bg: C.slate },
    ],
  },
]

const visibleGroups = computed(() => groups.filter((g) => !g.ketuaOnly || isKetua.value))
</script>

<style scoped>
.menu-row {
  border-radius: 18px;
  min-height: 64px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  transform: scale(1);
  transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.menu-row.is-pressed {
  transform: scale(0.96);
  transition: transform 0.08s ease-out;
}
:global(html[data-mode="dark"]) .menu-row {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
}
:global(html:not([data-mode="dark"])) .menu-row {
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.12);
}
</style>
