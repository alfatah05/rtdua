<template>
  <div>
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-[24px] font-extrabold text-[var(--text)] tracking-tight m-0">rtdua</h1>
      <div class="flex items-center gap-0.5">
        <router-link to="/notifikasi" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Notifikasi"><Bell :size="20" /></router-link>
        <router-link to="/profil" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Profil"><UserRound :size="20" /></router-link>
      </div>
    </header>

    <div class="rounded-[20px] p-5 text-white mb-4 relative overflow-hidden clickable"
      style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)">
      <p class="text-[13px] font-semibold text-white/90">Saldo kas</p>
      <p class="text-[32px] font-extrabold mt-1 tracking-tight">Rp 12.450.000</p>
      <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-5 gap-2 text-center mb-5">
      <button v-for="a in aksi" :key="a.label" type="button" class="qa-btn" @click="$router.push(a.to)">
        <span class="qa-icon relative" :style="{ background: a.bg, color: a.color }">
          <component :is="a.icon" :size="20" />
          <span v-if="a.badge" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] rounded-full bg-[var(--g)] text-white text-[10px] font-bold grid place-items-center px-1">{{ a.badge }}</span>
        </span>
        <span class="text-[11px] font-semibold leading-tight">{{ a.label }}</span>
      </button>
    </div>

    <div class="flex items-center justify-between mb-1 px-1">
      <h2 class="text-[17px] font-bold m-0">Riwayat kas</h2>
      <button type="button" class="text-[13px] font-semibold text-[var(--g)]" @click="$router.push('/keuangan/filter-kas')">Filter</button>
    </div>

    <div class="list-wrap">
      <button
        v-for="t in transaksi"
        :key="t.id"
        type="button"
        class="list-item list-item-press"
        @click="$router.push('/keuangan/kas/' + t.id)"
      >
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0">{{ t.keterangan }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ t.tanggal }} · {{ t.kategori }}</p>
        </div>
        <span class="font-bold shrink-0" :class="t.tipe === 'masuk' ? 'text-[var(--g)]' : 'text-red-500'">
          {{ t.tipe === 'masuk' ? '+' : '−' }}{{ t.nominal }}
        </span>
      </button>
    </div>
  </div>
</template>
<script setup>
import { Bell, UserRound, ArrowDownLeft, ArrowUpRight, ClipboardList, FileText, SlidersHorizontal } from 'lucide-vue-next'
const aksi = [
  { label: 'Kas masuk', icon: ArrowDownLeft, to: '/keuangan/kas-masuk', bg: 'rgba(16,185,129,.18)', color: '#059669' },
  { label: 'Kas keluar', icon: ArrowUpRight, to: '/keuangan/kas-keluar', bg: 'rgba(239,68,68,.15)', color: '#DC2626' },
  { label: 'Iuran', icon: ClipboardList, to: '/keuangan/iuran', bg: 'rgba(16,185,129,.18)', color: '#059669', badge: 2 },
  { label: 'Laporan', icon: FileText, to: '/keuangan/laporan', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Filter', icon: SlidersHorizontal, to: '/keuangan/filter-kas', bg: 'rgba(107,114,128,.18)', color: '#4B5563' },
]
// fix Filter -> SlidersHorizontal

const transaksi = [
  { id: 1, keterangan: 'Iuran kas Oktober', tanggal: '2 Okt 2026', kategori: 'Iuran kas', nominal: 'Rp 40.000', tipe: 'masuk' },
  { id: 2, keterangan: 'Beli alat kebersihan', tanggal: '28 Sep 2026', kategori: 'Operasional', nominal: 'Rp 150.000', tipe: 'keluar' },
  { id: 3, keterangan: 'Saldo awal', tanggal: '1 Sep 2026', kategori: 'Saldo awal', nominal: 'Rp 10.000.000', tipe: 'masuk' },
  { id: 4, keterangan: 'Iuran khusus 17 Agustus', tanggal: '15 Agu 2026', kategori: 'Iuran khusus', nominal: 'Rp 20.000', tipe: 'masuk' },
]
</script>
