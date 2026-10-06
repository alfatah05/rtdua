<template>
  <div>
    <AppBackHeader title="Laporan" />

    <div class="mb-4">
      <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Bulan</label>
      <select v-model="bulan" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
        <option>Oktober 2026</option>
        <option>September 2026</option>
      </select>
    </div>

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4 space-y-2">
      <div class="flex justify-between"><span class="text-[var(--mut)]">Saldo awal</span><span class="font-bold">Rp 10.000.000</span></div>
      <div class="flex justify-between"><span class="text-[var(--mut)]">Total masuk</span><span class="font-bold text-[var(--g)]">+ Rp 2.800.000</span></div>
      <div class="flex justify-between"><span class="text-[var(--mut)]">Total keluar</span><span class="font-bold text-red-500">− Rp 350.000</span></div>
      <div class="flex justify-between border-t border-[var(--line)] pt-2"><span class="font-bold">Saldo akhir</span><span class="font-extrabold text-lg">Rp 12.450.000</span></div>
    </div>

    <h2 class="text-[15px] font-bold mb-2">Per kategori</h2>
    <div class="space-y-2 mb-5">
      <div v-for="k in kategori" :key="k.nama" class="flex justify-between bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5">
        <span>{{ k.nama }}</span>
        <span class="font-bold" :class="k.tipe === 'masuk' ? 'text-[var(--g)]' : 'text-red-500'">{{ k.nominal }}</span>
      </div>
    </div>

    <div class="space-y-2">
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="msg = 'PDF dibuat (dummy)'">Ekspor PDF</button>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--card2)] font-bold" @click="msg = 'XLSX dibuat (dummy)'">Ekspor XLSX</button>
      <button type="button" class="w-full min-h-[48px] rounded-full border border-[var(--line)] font-bold" @click="msg = 'Ringkasan disalin'">Salin ringkasan teks</button>
    </div>
    <p v-if="msg" class="text-[13px] text-[var(--g)] text-center mt-3">{{ msg }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const bulan = ref('Oktober 2026')
const msg = ref('')
const kategori = [
  { nama: 'Iuran kas', nominal: '+ Rp 1.600.000', tipe: 'masuk' },
  { nama: 'Iuran khusus', nominal: '+ Rp 200.000', tipe: 'masuk' },
  { nama: 'Operasional', nominal: '− Rp 350.000', tipe: 'keluar' },
]
</script>
