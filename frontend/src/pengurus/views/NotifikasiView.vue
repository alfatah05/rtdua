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
          class="flex gap-3 rounded-[16px] p-4 border border-[var(--line)]"
          :class="!n.dibaca ? 'bg-[var(--ok)]/40' : 'bg-[var(--card)]'"
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
  { id: 1, hari: 'Hari ini', judul: 'Permintaan konfirmasi baru', isi: 'Keluarga AB2-22 mengajukan transfer Rp 50.000', waktu: '10.15', dibaca: false },
  { id: 2, hari: 'Hari ini', judul: 'Budi (Bendahara) mencatat pembayaran', isi: 'Pembayaran tunai Keluarga Pratama', waktu: '09.00', dibaca: false },
  { id: 3, hari: 'Kemarin', judul: 'Sistem membuat tagihan bulan baru', isi: 'Tagihan kas Oktober terbit untuk 52 keluarga', waktu: '00.05', dibaca: true },
  { id: 4, hari: 'Kemarin', judul: 'Ani menambah data keluarga', isi: 'Keluarga baru AB12-08', waktu: '16.40', dibaca: true },
]

const grouped = {}
daftar.forEach(n => {
  if (!grouped[n.hari]) grouped[n.hari] = []
  grouped[n.hari].push(n)
})
</script>
