<template>
  <div>
    <AppBackHeader title="Notifikasi" />
    <div v-for="(group, hari) in grouped" :key="hari" class="mb-5">
      <h2 class="text-[13px] font-bold text-[var(--mut)] mb-2">{{ hari }}</h2>
      <div class="list-wrap">
        <div v-for="n in group" :key="n.id" class="list-item !items-start"
          :class="!n.dibaca ? 'bg-[var(--ok)]/40' : ''">
          <div v-if="!n.dibaca" class="w-2 h-2 rounded-full bg-[var(--g)] mt-2 flex-none"></div>
          <div class="min-w-0">
            <p class="font-bold text-[15px] m-0">{{ n.judul }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5">{{ n.isi }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0 mt-1">{{ n.waktu }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const daftar = [
  { id: 1, hari: 'Hari ini', judul: 'Permintaan konfirmasi baru', isi: 'Keluarga AB2-22 mengajukan transfer Rp 50.000', waktu: '10.15', dibaca: false },
  { id: 2, hari: 'Hari ini', judul: 'Budi mencatat pembayaran', isi: 'Pembayaran tunai Keluarga Pratama', waktu: '09.00', dibaca: false },
  { id: 3, hari: 'Kemarin', judul: 'Sistem membuat tagihan bulan baru', isi: 'Tagihan kas Oktober terbit', waktu: '00.05', dibaca: true },
]
const grouped = {}
daftar.forEach(n => { if (!grouped[n.hari]) grouped[n.hari] = []; grouped[n.hari].push(n) })
</script>
