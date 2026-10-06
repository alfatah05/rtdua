<template>
  <div>
    <AppBackHeader title="Kelola pengurus" />
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4" @click="$router.push('/kelola-pengurus/angkat')">
      + Angkat pengurus
    </button>
    <div class="space-y-2">
      <div v-for="p in daftar" :key="p.id" class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4">
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm">{{ p.inisial }}</div>
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0">{{ p.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ p.jabatan }}{{ p.ketua ? ' · Ketua' : '' }}</p>
        </div>
        <button v-if="!p.ketua" type="button" class="text-[13px] font-semibold text-[var(--g)]" @click="$router.push('/kelola-pengurus/' + p.id)">Kelola</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const daftar = [
  { id: 1, nama: 'Budi Santoso', jabatan: 'Ketua RT', inisial: 'BS', ketua: true },
  { id: 2, nama: 'Ani Wijaya', jabatan: 'Bendahara', inisial: 'AW', ketua: false },
  { id: 3, nama: 'Rina Marlina', jabatan: 'Sekretaris', inisial: 'RM', ketua: false },
]
</script>
