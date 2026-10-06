<template>
  <div>
    <AppBackHeader title="Rincian tagihan" />

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4 text-center">
      <p class="text-[13px] text-[var(--mut)] m-0">{{ keluarga }}</p>
      <p class="text-[28px] font-extrabold mt-1 m-0">Rp 70.000</p>
      <span class="inline-block mt-2 text-[12px] font-bold px-2.5 py-1 rounded-full bg-amber-500/15 text-amber-700">Belum lunas</span>
    </div>

    <h2 class="text-[15px] font-bold mb-2">Tagihan</h2>
    <div class="space-y-2 mb-5">
      <div v-for="t in tagihan" :key="t.id" class="flex justify-between bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
        <div>
          <p class="font-bold text-[15px] m-0">{{ t.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ t.periode }}</p>
        </div>
        <span class="font-bold">{{ t.sisa }}</span>
      </div>
    </div>

    <h2 class="text-[15px] font-bold mb-2">Riwayat pembayaran</h2>
    <div class="space-y-2 mb-6">
      <div v-for="p in bayar" :key="p.id" class="flex justify-between bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
        <div>
          <p class="font-bold text-[15px] m-0">{{ p.metode }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ p.tanggal }}</p>
        </div>
        <span class="font-bold text-[var(--g)]">{{ p.nominal }}</span>
      </div>
    </div>

    <div class="space-y-2">
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="$router.push('/keuangan/tagihan/3/bayar')">
        Catat pembayaran
      </button>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--card2)] font-bold" @click="$router.push('/keuangan/tagihan/3/batal-denda')">
        Batalkan denda
      </button>
    </div>
  </div>
</template>

<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const keluarga = 'AB1-05 · Andi Wijaya'
const tagihan = [
  { id: 1, nama: 'Kas', periode: 'Oktober 2026', sisa: 'Rp 40.000' },
  { id: 2, nama: 'Denda ronda', periode: 'September 2026', sisa: 'Rp 30.000' },
]
const bayar = [
  { id: 1, metode: 'Transfer', tanggal: '5 Sep 2026', nominal: 'Rp 40.000' },
]
</script>
