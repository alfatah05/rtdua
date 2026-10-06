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

    <div class="mb-4">
      <div class="flex items-center gap-2.5 bg-[var(--search)] rounded-full px-[18px] h-12 text-[var(--mut)]">
        <Search :size="18" />
        <span class="text-[15px]">Cari nama atau blok/nomor rumah</span>
      </div>
    </div>

    <p class="text-[13px] text-[var(--mut)] mb-3">Daftar keluarga (dummy Stage 2)</p>
    <div class="space-y-2">
      <div
        v-for="k in keluarga"
        :key="k.id"
        class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 shadow-[var(--sh)]"
      >
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm flex-none">
          {{ k.inisial }}
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ k.alamat }}</p>
        </div>
        <ChevronRight :size="18" class="text-[var(--mut)]" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Bell, UserRound, Search, ChevronRight } from 'lucide-vue-next'

const keluarga = [
  { id: 1, nama: 'Budi Santoso', alamat: 'AB2-22', inisial: 'BS' },
  { id: 2, nama: 'Siti Aminah', alamat: 'AB2-22a', inisial: 'SA' },
  { id: 3, nama: 'Andi Wijaya', alamat: 'AB1-05', inisial: 'AW' },
  { id: 4, nama: 'Rina Marlina', alamat: 'AB11-12', inisial: 'RM' },
  { id: 5, nama: 'Joko Prasetyo', alamat: 'AB12-03', inisial: 'JP' },
]
</script>
