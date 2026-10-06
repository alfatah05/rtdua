<template>
  <div>
    <!-- Header tetap: rtdua hitam + lonceng + profil -->
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

    <div class="mb-4">
      <div class="flex items-center gap-2.5 bg-[var(--search)] rounded-full px-[18px] h-12 text-[var(--mut)]">
        <Search :size="18" />
        <span class="text-[15px]">Cari nama atau blok/nomor rumah</span>
      </div>
    </div>

    <div class="rounded-[20px] p-5 text-white mb-4 relative overflow-hidden"
      style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)">
      <div class="flex items-center justify-between">
        <p class="text-[13px] font-semibold text-white/90">Saldo kas</p>
        <span class="text-[12px] font-semibold px-3 py-1 rounded-full bg-white/20">Oktober 2026</span>
      </div>
      <p class="text-[34px] font-extrabold mt-2.5 tracking-tight leading-none">Rp 12.450.000</p>
      <p class="text-[13px] text-white/80 mt-2">Data dummy Stage 2</p>
      <div class="absolute -right-14 -bottom-20 w-52 h-52 rounded-full bg-white/10 pointer-events-none"></div>
    </div>

    <!-- Aksi cepat 5 kolom 48px -->
    <div class="grid grid-cols-5 gap-2 text-center mb-5">
      <button
        v-for="a in aksi"
        :key="a.label"
        type="button"
        class="flex flex-col items-center gap-2"
        @click="$router.push(a.to)"
      >
        <span class="w-12 h-12 rounded-full bg-[var(--card)] border border-[var(--line)] grid place-items-center shadow-[var(--sh)]">
          <component :is="a.icon" :size="20" />
        </span>
        <span class="text-[11px] font-semibold leading-tight text-center">{{ a.label }}</span>
      </button>
    </div>

    <div class="grid grid-cols-2 gap-2 mb-5">
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4">
        <div class="flex items-center gap-2">
          <House :size="20" />
          <b class="text-[22px] font-extrabold">52</b>
        </div>
        <span class="block text-[12px] font-semibold text-[var(--mut)] mt-1">Keluarga (KK)</span>
      </div>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4">
        <div class="flex items-center gap-2">
          <Users :size="20" />
          <b class="text-[22px] font-extrabold">187</b>
        </div>
        <span class="block text-[12px] font-semibold text-[var(--mut)] mt-1">Warga</span>
      </div>
    </div>

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-[18px] mb-5 active:scale-[0.98] transition cursor-pointer" @click="$router.push('/keuangan')">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-[17px] font-bold m-0">Iuran Oktober</h2>
          <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5">38 dari 52 keluarga sudah bayar</p>
        </div>
        <div class="flex items-center gap-1">
          <span class="text-2xl font-extrabold">73%</span>
          <ChevronRight :size="20" class="text-[var(--mut)]" />
        </div>
      </div>
      <div class="h-2.5 rounded-full bg-[var(--card2)] mt-3.5 overflow-hidden">
        <div class="h-full w-[73%] rounded-full bg-[var(--g)]"></div>
      </div>
      <p class="text-[13px] text-[var(--mut)] mt-2.5 mb-0">14 keluarga belum bayar</p>
    </div>

    <h2 class="text-[17px] font-bold mb-3">Aktivitas terakhir</h2>
    <div class="space-y-2">
      <div
        v-for="(act, i) in aktivitas"
        :key="i"
        class="flex gap-3 items-center bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-3"
      >
        <div class="w-10 h-10 rounded-full bg-[var(--card2)] grid place-items-center flex-none">
          <component :is="act.icon" :size="18" />
        </div>
        <div class="min-w-0">
          <p class="font-bold text-[15px] m-0">{{ act.text }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ act.time }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  Bell, UserRound, Search, Banknote, ArrowDownLeft, ArrowUpRight,
  FileText, LayoutGrid, House, Users, ChevronRight, Check, UserPlus
} from 'lucide-vue-next'

const aksi = [
  { label: 'Catat iuran', icon: Banknote, to: '/keuangan' },
  { label: 'Kas masuk', icon: ArrowDownLeft, to: '/keuangan' },
  { label: 'Kas keluar', icon: ArrowUpRight, to: '/keuangan' },
  { label: 'Laporan', icon: FileText, to: '/keuangan' },
  { label: 'Lainnya', icon: LayoutGrid, to: '/lainnya' },
]

const aktivitas = [
  { text: 'Budi (Bendahara) mengonfirmasi pembayaran', time: '2 jam lalu', icon: Check },
  { text: 'Ani (Sekretaris) menambah data keluarga', time: 'Kemarin', icon: UserPlus },
  { text: 'Sistem membuat tagihan bulan baru', time: '1 Okt', icon: Banknote },
]
</script>
