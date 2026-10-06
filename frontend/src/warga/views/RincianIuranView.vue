<template>
  <div>
    <AppBackHeader title="Rincian iuran" />

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4 text-center">
      <p class="text-[13px] font-semibold text-[var(--mut)]">Total tagihan</p>
      <p class="text-[28px] font-extrabold mt-1">Rp 10.000</p>
    </div>

    <h2 class="text-[15px] font-bold mb-2">Detail tagihan</h2>
    <div class="space-y-2 mb-5">
      <div v-for="t in tagihan" :key="t.id" class="flex justify-between items-center bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
        <div>
          <p class="font-bold text-[15px] m-0">{{ t.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ t.periode }}</p>
        </div>
        <span class="font-bold">{{ t.sisa }}</span>
      </div>
    </div>

    <h2 class="text-[15px] font-bold mb-2">Riwayat pembayaran</h2>
    <div class="space-y-2 mb-6">
      <div v-for="p in pembayaran" :key="p.id" class="flex justify-between bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
        <div>
          <p class="font-bold text-[15px] m-0">{{ p.metode }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ p.tanggal }}</p>
        </div>
        <span class="font-bold text-[var(--g)]">{{ p.nominal }}</span>
      </div>
    </div>

    <button
      type="button"
      class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold text-[15px]"
      @click="$router.push('/transfer')"
    >
      Saya sudah transfer
    </button>
  </div>
</template>

<script setup>
const tagihan = [
  { id: 1, nama: 'Kas', periode: 'Oktober 2026', sisa: 'Rp 10.000' },
  { id: 2, nama: 'Denda ronda', periode: 'September 2026', sisa: 'Rp 0' },
]
const pembayaran = [
  { id: 1, metode: 'Transfer', tanggal: '5 Sep 2026', nominal: 'Rp 40.000' },
]
</script>
