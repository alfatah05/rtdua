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

    <div class="flex gap-2 mb-4">
      <select class="flex-1 min-h-[44px] px-4 rounded-full bg-[var(--search)] text-[var(--text)] outline-none">
        <option>Oktober</option>
        <option>September</option>
      </select>
      <select class="flex-1 min-h-[44px] px-4 rounded-full bg-[var(--search)] text-[var(--text)] outline-none">
        <option>2026</option>
        <option>2025</option>
      </select>
    </div>

    <p class="text-[13px] text-[var(--mut)] mb-3">Oktober 2026 · 3 aktivitas</p>

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
import { Bell, UserRound, Check, UserPlus, Banknote } from 'lucide-vue-next'

const aktivitas = [
  { text: 'Budi (Bendahara) mengonfirmasi pembayaran', time: '2 jam lalu', icon: Check },
  { text: 'Ani (Sekretaris) menambah data keluarga', time: 'Kemarin', icon: UserPlus },
  { text: 'Sistem membuat tagihan bulan baru', time: '1 Okt', icon: Banknote },
]
</script>
