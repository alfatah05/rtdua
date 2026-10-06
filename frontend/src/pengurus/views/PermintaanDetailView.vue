<template>
  <div>
    <AppBackHeader title="Permintaan konfirmasi" />

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4 space-y-3">
      <div><p class="text-[13px] text-[var(--mut)] m-0">Keluarga</p><p class="font-bold m-0">AB2-22 · Budi Santoso</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Nominal diajukan</p><p class="font-bold text-lg m-0">Rp 50.000</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Pengirim</p><p class="font-bold m-0">Budi S · BCA</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Waktu</p><p class="font-bold m-0">6 Okt 2026, 09.40</p></div>
    </div>

    <div class="bg-[var(--search)] rounded-[16px] h-40 grid place-items-center text-[var(--mut)] text-[13px] mb-5">
      Bukti transfer (ketuk untuk perbesar — dummy)
    </div>

    <div class="space-y-2">
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="$router.push('/keuangan/permintaan/1/konfirmasi')">
        Konfirmasi
      </button>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-red-500/15 text-red-600 font-bold" @click="$router.push('/keuangan/permintaan/1/tolak')">
        Tolak
      </button>
    </div>
  </div>
</template>
<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
</script>
