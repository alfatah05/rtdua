<template>
  <div>
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-[24px] font-extrabold text-[var(--text)] tracking-tight m-0">rtdua</h1>
      <div class="flex items-center gap-0.5">
        <router-link to="/notifikasi" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Notifikasi">
          <Bell :size="20" />
        </router-link>
        <router-link to="/profil" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Profil">
          <UserRound :size="20" />
        </router-link>
      </div>
    </header>

    <div class="rounded-[20px] p-5 text-white mb-4 relative overflow-hidden"
      style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)">
      <p class="text-[13px] font-semibold text-white/90">Saldo kas</p>
      <p class="text-[32px] font-extrabold mt-1">Rp 12.450.000</p>
    </div>

    <div class="grid grid-cols-5 gap-2 text-center mb-5">
      <button v-for="a in aksi" :key="a.label" type="button" class="flex flex-col items-center gap-2" @click="$router.push(a.to)">
        <span class="w-12 h-12 rounded-full bg-[var(--card)] border border-[var(--line)] grid place-items-center shadow-[var(--sh)]">
          <component :is="a.icon" :size="20" />
        </span>
        <span class="text-[11px] font-semibold leading-tight">{{ a.label }}</span>
      </button>
    </div>

    <h2 class="text-[17px] font-bold mb-3">Riwayat kas</h2>
    <div class="space-y-2">
      <div v-for="(t, i) in transaksi" :key="i" class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4">
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0">{{ t.keterangan }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ t.tanggal }}</p>
        </div>
        <span class="font-bold" :class="t.tipe === 'masuk' ? 'text-[var(--g)]' : 'text-red-500'">
          {{ t.tipe === 'masuk' ? '+' : '-' }}{{ t.nominal }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Bell, UserRound, Banknote, ArrowDownLeft, ArrowUpRight, FileText, Filter } from 'lucide-vue-next'

const aksi = [
  { label: 'Kas masuk', icon: ArrowDownLeft, to: '/keuangan' },
  { label: 'Kas keluar', icon: ArrowUpRight, to: '/keuangan' },
  { label: 'Iuran', icon: Banknote, to: '/keuangan' },
  { label: 'Laporan', icon: FileText, to: '/keuangan' },
  { label: 'Filter', icon: Filter, to: '/keuangan' },
]

const transaksi = [
  { keterangan: 'Iuran kas Oktober', tanggal: '2 Okt 2026', nominal: 'Rp 40.000', tipe: 'masuk' },
  { keterangan: 'Beli alat kebersihan', tanggal: '28 Sep 2026', nominal: 'Rp 150.000', tipe: 'keluar' },
  { keterangan: 'Saldo awal', tanggal: '1 Sep 2026', nominal: 'Rp 10.000.000', tipe: 'masuk' },
]
</script>
