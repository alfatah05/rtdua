<template>
  <div>
    <AppBackHeader title="Pengumuman" />

    <div v-for="(group, bulan) in grouped" :key="bulan" class="mb-6">
      <h2 class="text-[13px] font-bold text-[var(--mut)] mb-2">{{ bulan }}</h2>
      <div class="space-y-2">
        <button
          v-for="p in group"
          :key="p.id"
          type="button"
          class="w-full text-left bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 shadow-[var(--sh)] active:scale-[0.98] transition"
          @click="$router.push('/pengumuman/' + p.id)"
        >
          <div class="flex items-center justify-between gap-2 mb-1">
            <span v-if="p.pin" class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[var(--ok)] text-[var(--g)]">Pin</span>
            <span class="text-[12px] text-[var(--mut)] ml-auto">{{ p.tanggal }}</span>
          </div>
          <p class="font-bold text-[15px] m-0">{{ p.judul }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 mt-1 line-clamp-2">{{ p.ringkas }}</p>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const daftar = [
  { id: 1, pin: true, tanggal: '3 Okt', bulan: 'Oktober 2026', judul: 'Kerja bakti membersihkan selokan', ringkas: 'Hari Minggu, 12 Oktober pukul 07.00. Semua warga diharapkan hadir.' },
  { id: 2, pin: true, tanggal: '1 Okt', bulan: 'Oktober 2026', judul: 'Tagihan kas Oktober sudah terbit', ringkas: 'Silakan cek rincian iuran di menu Keuangan.' },
  { id: 3, pin: false, tanggal: '28 Sep', bulan: 'September 2026', judul: 'Jadwal ronda bulan Oktober', ringkas: 'Jadwal sudah tersedia. Cek giliran keluarga Anda.' },
  { id: 4, pin: false, tanggal: '15 Sep', bulan: 'September 2026', judul: 'Hasil rapat RT September', ringkas: 'Ringkasan keputusan rapat bulanan.' },
]

const grouped = {}
daftar.forEach(p => {
  if (!grouped[p.bulan]) grouped[p.bulan] = []
  grouped[p.bulan].push(p)
})
</script>
