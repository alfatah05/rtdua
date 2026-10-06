<template>
  <div>
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-[24px] font-extrabold text-[var(--text)] tracking-tight m-0">rtdua</h1>
      <router-link to="/notifikasi" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Notifikasi">
        <Bell :size="20" />
      </router-link>
    </header>
    <h2 class="text-[17px] font-bold mb-3">Keuangan</h2>

    <div class="rounded-[20px] p-5 text-white mb-4 relative overflow-hidden"
      style="background: linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)">
      <p class="text-[13px] font-semibold text-white/90">Total kas RT</p>
      <p class="text-[28px] font-extrabold mt-1">Rp 12.450.000</p>
      <p class="text-[13px] text-white/80 mt-1">Transparansi kas RT</p>
    </div>

    <button
      type="button"
      class="w-full flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-[18px] active:scale-[0.98] transition-transform text-left mb-4"
      @click="$router.push('/rincian-iuran')"
    >
      <div class="flex-1">
        <span class="block text-[13px] font-semibold text-[var(--mut)]">Iuran keluarga Anda</span>
        <b class="block text-[17px] font-extrabold mt-0.5">Rp 10.000</b>
        <span class="text-[13px] text-[var(--mut)]">Ketuk untuk rincian & transfer</span>
      </div>
      <ChevronRight :size="20" class="text-[var(--mut)]" />
    </button>

    <h2 class="text-[17px] font-bold mb-3">Riwayat kas</h2>
    <div class="px-1 space-y-0.5">
      <div v-for="(t, i) in transaksi" :key="i" class="w-full flex flex-row items-center gap-3 px-2 py-3">
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px]">{{ t.keterangan }}</p>
          <p class="text-[13px] text-[var(--mut)]">{{ t.tanggal }}</p>
        </div>
        <span class="font-bold" :class="t.tipe === 'masuk' ? 'text-[var(--g)]' : 'text-red-500'">
          {{ t.tipe === 'masuk' ? '+' : '-' }}{{ t.nominal }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ChevronRight } from 'lucide-vue-next'

const transaksi = [
  { keterangan: 'Iuran kas Oktober', tanggal: '2 Okt 2026', nominal: 'Rp 40.000', tipe: 'masuk' },
  { keterangan: 'Beli alat kebersihan', tanggal: '28 Sep 2026', nominal: 'Rp 150.000', tipe: 'keluar' },
  { keterangan: 'Iuran khusus 17 Agustus', tanggal: '15 Agu 2026', nominal: 'Rp 20.000', tipe: 'masuk' },
]
</script>
