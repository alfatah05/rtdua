<template>
  <div>
    <AppBackHeader title="Detail kas" />
    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] space-y-3 mb-5">
      <div><p class="text-[13px] text-[var(--mut)] m-0">Keterangan</p><p class="font-bold m-0">Beli alat kebersihan</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Nominal</p><p class="font-bold text-red-500 text-lg m-0">− Rp 150.000</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Kategori</p><p class="font-bold m-0">Operasional</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Metode</p><p class="font-bold m-0">Tunai</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Tanggal</p><p class="font-bold m-0">28 September 2026</p></div>
      <div><p class="text-[13px] text-[var(--mut)] m-0">Dicatat oleh</p><p class="font-bold m-0">Budi (Bendahara)</p></div>
    </div>
    <button type="button" class="w-full min-h-[48px] rounded-full bg-red-500/15 text-red-600 font-bold" @click="onBatal">
      Batalkan transaksi
    </button>
  </div>
</template>
<script setup>
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useToast } from '@shared/composables/useToast.js'
const router = useRouter()
const { success } = useToast()
function onBatal() {
  success('Transaksi dibatalkan')
  setTimeout(() => router.back(), 600)
}
</script>
