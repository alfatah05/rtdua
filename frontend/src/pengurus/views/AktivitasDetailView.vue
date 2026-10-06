<template>
  <div>
    <AppBackHeader title="Detail aktivitas" />
    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] space-y-4">
      <div><p class="text-[13px] text-[var(--mut)] m-0">Pelaku</p><p class="font-bold text-[15px] m-0">Budi (Bendahara)</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Aksi</p><p class="font-bold text-[15px] m-0">Mengonfirmasi pembayaran</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Waktu</p><p class="font-bold text-[15px] m-0">6 Oktober 2026, 10.15</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Nilai sebelum</p><p class="text-[15px] m-0 bg-[var(--search)] rounded-xl p-3 mt-1">Status: menunggu · Nominal: Rp 50.000</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Nilai sesudah</p><p class="text-[15px] m-0 bg-[var(--search)] rounded-xl p-3 mt-1">Status: dikonfirmasi · Nominal: Rp 50.000</p></div>
    </div>
  </div>
</template>
<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
</script>
