<template>
  <div>
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-extrabold text-[var(--gh)]">Beranda</h1>
      <div class="flex items-center gap-1">
        <router-link to="/notifikasi" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Notifikasi">
          <Bell :size="20" />
        </router-link>
        <router-link to="/profil" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Profil">
          <UserRound :size="20" />
        </router-link>
      </div>
    </header>

    <div class="mb-4">
      <div class="flex items-center gap-2 bg-[var(--search)] rounded-full px-4 h-12 text-[var(--mut)]">
        <Search :size="18" />
        <span class="text-[15px]">Cari nama atau blok/nomor rumah</span>
      </div>
    </div>

    <div class="rounded-[20px] p-5 text-white mb-4 relative overflow-hidden"
      style="background: linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)">
      <p class="text-[13px] font-semibold opacity-90">Saldo kas</p>
      <p class="text-[32px] font-extrabold mt-1 tracking-tight">Rp 12.450.000</p>
      <p class="text-[13px] opacity-80 mt-1">Data dummy Stage 1</p>
    </div>

    <div class="grid grid-cols-5 gap-2 text-center mb-6">
      <button v-for="a in aksi" :key="a.label" type="button" class="flex flex-col items-center gap-1.5" @click="$router.push(a.to)">
        <span class="w-12 h-12 rounded-full bg-[var(--card)] border border-[var(--line)] grid place-items-center shadow-[var(--sh)]">
          <component :is="a.icon" :size="20" />
        </span>
        <span class="text-[11px] font-semibold leading-tight">{{ a.label }}</span>
      </button>
    </div>

    <div class="grid grid-cols-2 gap-2 mb-6">
      <UiCard>
        <p class="text-[22px] font-extrabold">52</p>
        <p class="text-[12px] font-semibold text-[var(--mut)]">Keluarga (KK)</p>
      </UiCard>
      <UiCard>
        <p class="text-[22px] font-extrabold">187</p>
        <p class="text-[12px] font-semibold text-[var(--mut)]">Warga</p>
      </UiCard>
    </div>

    <h2 class="text-[17px] font-bold mb-3">Aktivitas terakhir</h2>
    <div class="space-y-2">
      <UiCard v-for="(act, i) in aktivitas" :key="i" class="!py-3 !px-3.5">
        <p class="font-bold text-[15px]">{{ act.text }}</p>
        <p class="text-[13px] text-[var(--mut)]">{{ act.time }}</p>
      </UiCard>
    </div>
  </div>
</template>

<script setup>
import { Bell, UserRound, Search, Banknote, ArrowDownLeft, ArrowUpRight, FileText, LayoutGrid } from 'lucide-vue-next'
import UiCard from '@shared/components/UiCard.vue'

const aksi = [
  { label: 'Catat iuran', icon: Banknote, to: '/keuangan' },
  { label: 'Kas masuk', icon: ArrowDownLeft, to: '/keuangan' },
  { label: 'Kas keluar', icon: ArrowUpRight, to: '/keuangan' },
  { label: 'Laporan', icon: FileText, to: '/keuangan' },
  { label: 'Lainnya', icon: LayoutGrid, to: '/lainnya' },
]

const aktivitas = [
  { text: 'Budi (Bendahara) mengonfirmasi pembayaran', time: '2 jam lalu' },
  { text: 'Ani (Sekretaris) menambah data keluarga', time: 'Kemarin' },
  { text: 'Sistem membuat tagihan bulan baru', time: '1 Okt' },
]
</script>
