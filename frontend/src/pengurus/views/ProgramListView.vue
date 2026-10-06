<template>
  <div>
    <AppBackHeader title="Program RT" />
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4" @click="$router.push('/konten/program/tambah')">
      + Tambah program
    </button>
    <div class="space-y-3">
      <button
        v-for="p in daftar"
        :key="p.id"
        type="button"
        class="w-full text-left bg-[var(--card)] border border-[var(--line)] rounded-[20px] overflow-hidden shadow-[var(--sh)] active:scale-[0.98] transition"
        @click="$router.push('/konten/program/' + p.id)"
      >
        <div class="h-28 bg-[var(--search)] grid place-items-center text-[var(--mut)] text-[13px]">Banner</div>
        <div class="p-4">
          <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="statusClass(p.status)">{{ p.status }}</span>
          <p class="font-bold text-[15px] m-0 mt-2">{{ p.judul }}</p>
        </div>
      </button>
    </div>
  </div>
</template>
<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const daftar = [
  { id: 1, judul: 'Digitalisasi RT (aplikasi ini)', status: 'Berjalan' },
  { id: 2, judul: 'Pengadaan CCTV blok AB', status: 'Direncanakan' },
  { id: 3, judul: 'Renovasi balai RT', status: 'Selesai' },
]
function statusClass(s) {
  if (s === 'Berjalan') return 'bg-[var(--ok)] text-[var(--g)]'
  if (s === 'Selesai') return 'bg-[var(--card2)] text-[var(--mut)]'
  return 'bg-amber-500/15 text-amber-700'
}
</script>
