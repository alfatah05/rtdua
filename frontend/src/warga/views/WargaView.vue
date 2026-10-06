<template>
  <div>
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-[24px] font-extrabold text-[var(--text)] tracking-tight m-0">rtdua</h1>
      <router-link to="/notifikasi" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Notifikasi">
        <Bell :size="20" />
      </router-link>
    </header>

    <p class="text-[13px] text-[var(--mut)] mb-2 px-2">Daftar nama dan alamat keluarga</p>
    <div class="px-1 space-y-0.5">
      <div v-for="k in keluarga" :key="k.id" class="w-full flex flex-row items-center gap-3 px-2 py-3">
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0">{{ k.inisial }}</div>
        <div class="min-w-0">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ k.alamat }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Bell } from 'lucide-vue-next'
const keluarga = [
  { id: 1, nama: 'Budi Santoso', alamat: 'AB2-22', inisial: 'BS' },
  { id: 2, nama: 'Siti Aminah', alamat: 'AB2-22a', inisial: 'SA' },
  { id: 3, nama: 'Andi Wijaya', alamat: 'AB1-05', inisial: 'AW' },
  { id: 4, nama: 'Rina Marlina', alamat: 'AB11-12', inisial: 'RM' },
  { id: 5, nama: 'Joko Prasetyo', alamat: 'AB12-03', inisial: 'JP' },
]
</script>
