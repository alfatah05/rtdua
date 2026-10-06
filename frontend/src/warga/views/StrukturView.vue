<template>
  <div>
    <header class="flex items-center gap-3 mb-5">
      <button type="button" class="min-h-[44px] px-3 -ml-2 font-semibold text-[var(--g)]" @click="$router.back()">← Kembali</button>
      <h1 class="text-xl font-extrabold m-0">Struktur Pengurus</h1>
    </header>

    <!-- Ketua -->
    <div class="flex flex-col items-center mb-6">
      <div class="w-16 h-16 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-lg mb-2">BS</div>
      <p class="font-bold text-[15px] m-0">Budi Santoso</p>
      <p class="text-[13px] text-[var(--mut)] m-0">Ketua RT</p>
    </div>

    <!-- Garis -->
    <div class="w-px h-6 bg-[var(--line)] mx-auto mb-4"></div>

    <!-- Pengurus sejajar -->
    <div class="grid grid-cols-2 gap-3">
      <div v-for="p in pengurus" :key="p.id" class="flex flex-col items-center bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4">
        <div class="w-12 h-12 rounded-full bg-[var(--card2)] grid place-items-center font-bold text-sm mb-2">{{ p.inisial }}</div>
        <p class="font-bold text-[14px] m-0 text-center">{{ p.nama }}</p>
        <p class="text-[12px] text-[var(--mut)] m-0 text-center">{{ p.jabatan }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
const pengurus = [
  { id: 2, nama: 'Ani Wijaya', jabatan: 'Bendahara', inisial: 'AW' },
  { id: 3, nama: 'Rina Marlina', jabatan: 'Sekretaris', inisial: 'RM' },
  { id: 4, nama: 'Joko Prasetyo', jabatan: 'Keamanan', inisial: 'JP' },
  { id: 5, nama: 'Siti Aminah', jabatan: 'Sosial', inisial: 'SA' },
]
</script>
