<template>
  <div>
    <AppBackHeader title="Pengumuman" />
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4 clickable" @click="$router.push('/konten/pengumuman/tambah')">
      + Tambah pengumuman
    </button>
    <div class="list-wrap">
      <button
        v-for="p in daftar"
        :key="p.id"
        type="button"
        class="list-item list-item-press !items-start"
        @click="$router.push('/konten/pengumuman/' + p.id)"
      >
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2 mb-0.5">
            <span v-if="p.pin" class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[var(--ok)] text-[var(--g)]">Pin</span>
            <span class="text-[12px] text-[var(--mut)] ml-auto">{{ p.tanggal }}</span>
          </div>
          <p class="font-bold text-[15px] m-0">{{ p.judul }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5 line-clamp-2">{{ p.ringkas }}</p>
        </div>
      </button>
    </div>
  </div>
</template>
<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const daftar = [
  { id: 1, pin: true, tanggal: '3 Okt', judul: 'Kerja bakti membersihkan selokan', ringkas: 'Hari Minggu, 12 Oktober pukul 07.00.' },
  { id: 2, pin: true, tanggal: '1 Okt', judul: 'Tagihan kas Oktober sudah terbit', ringkas: 'Silakan cek rincian iuran.' },
  { id: 3, pin: false, tanggal: '28 Sep', judul: 'Jadwal ronda bulan Oktober', ringkas: 'Jadwal sudah tersedia.' },
]
</script>
