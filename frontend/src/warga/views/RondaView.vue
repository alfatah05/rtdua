<template>
  <div>
    <AppBackHeader title="Jadwal Ronda" />

    <!-- Card giliran berikutnya -->
    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4">
      <p class="text-[13px] font-semibold text-[var(--mut)]">Giliran keluargamu berikutnya</p>
      <p class="text-[17px] font-bold mt-1">Sabtu, 11 Oktober 2026</p>
      <p class="text-[13px] text-[var(--mut)]">21.00 – 24.00 · Belum absen</p>
      <button
        type="button"
        class="mt-3 min-h-[44px] px-5 rounded-full bg-[var(--g)] text-white font-bold text-[15px]"
        @click="absenMsg = 'Fitur absen kamera menyusul di Stage berikutnya'"
      >
        Absen ronda
      </button>
      <p v-if="absenMsg" class="text-[13px] text-[var(--mut)] mt-2">{{ absenMsg }}</p>
    </div>

    <h2 class="text-[15px] font-bold mb-2">Malam mendatang</h2>
    <div class="space-y-2 mb-5">
      <div v-for="m in malam" :key="m.tanggal" class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4"
        :class="m.milik ? 'ring-2 ring-[var(--g)]' : ''">
        <p class="font-bold text-[15px] m-0">{{ m.tanggal }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0">{{ m.keluarga }} · {{ m.jam }}</p>
      </div>
    </div>

    <h2 class="text-[15px] font-bold mb-2">Ringkasan bulan ini</h2>
    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
      <p class="text-[15px] m-0">Hadir <b>2</b> kali · Tidak hadir <b>0</b> kali</p>
      <p class="text-[13px] text-[var(--mut)] m-0 mt-1">Perkiraan denda: Rp 0</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
const absenMsg = ref('')
const malam = [
  { tanggal: 'Sabtu, 11 Okt', keluarga: 'Keluarga Anda', jam: '21.00–24.00', milik: true },
  { tanggal: 'Senin, 13 Okt', keluarga: 'AB1-05, AB2-10', jam: '21.00–24.00', milik: false },
  { tanggal: 'Rabu, 15 Okt', keluarga: 'AB11-12, AB12-03', jam: '21.00–24.00', milik: false },
]
</script>
