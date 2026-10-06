<template>
  <div>
    <header class="flex items-center gap-3 mb-5">
      <button type="button" class="min-h-[44px] px-3 -ml-2 font-semibold text-[var(--g)]" @click="$router.back()">← Kembali</button>
      <h1 class="text-xl font-extrabold m-0">Notifikasi</h1>
    </header>

    <div v-for="(group, hari) in grouped" :key="hari" class="mb-5">
      <h2 class="text-[13px] font-bold text-[var(--mut)] mb-2">{{ hari }}</h2>
      <div class="space-y-2">
        <div
          v-for="n in group"
          :key="n.id"
          class="flex gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4"
          :class="!n.dibaca ? 'bg-[var(--ok)]/30' : ''"
        >
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
const daftar = [
  { id: 1, hari: 'Hari ini', judul: 'Tagihan bulan baru', isi: 'Tagihan kas Oktober sudah terbit.', waktu: '08.00', dibaca: false },
  { id: 2, hari: 'Hari ini', judul: 'Pengumuman baru', isi: 'Kerja bakti membersihkan selokan.', waktu: '07.30', dibaca: false },
  { id: 3, hari: 'Kemarin', judul: 'Pembayaran dikonfirmasi', isi: 'Transfer Rp 40.000 sudah dikonfirmasi.', waktu: '14.20', dibaca: true },
  { id: 4, hari: 'Kemarin', judul: 'Pengingat ronda', isi: 'Malam ini giliran ronda keluargamu.', waktu: '17.00', dibaca: true },
]

const grouped = {}
daftar.forEach(n => {
  if (!grouped[n.hari]) grouped[n.hari] = []
  grouped[n.hari].push(n)
})
</script>
