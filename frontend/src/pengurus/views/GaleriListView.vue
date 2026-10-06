<template>
  <div>
    <AppBackHeader title="Galeri" />
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4" @click="$router.push('/konten/galeri/tambah')">
      + Buat album
    </button>
    <div class="space-y-2">
      <button
        v-for="a in album"
        :key="a.id"
        type="button"
        class="w-full flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 text-left active:scale-[0.98] transition"
        @click="$router.push('/konten/galeri/' + a.id)"
      >
        <div class="w-14 h-14 rounded-[12px] bg-[var(--search)] grid place-items-center text-[var(--mut)] text-xs shrink-0">{{ a.jumlah }} foto</div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0">{{ a.judul }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ a.tanggal }}</p>
        </div>
      </button>
    </div>
  </div>
</template>
<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const album = [
  { id: 1, judul: 'Kerja bakti Oktober', tanggal: '12 Okt 2026', jumlah: 24 },
  { id: 2, judul: 'Rapat RT September', tanggal: '20 Sep 2026', jumlah: 8 },
  { id: 3, judul: '17 Agustus 2026', tanggal: '17 Agu 2026', jumlah: 56 },
]
</script>
