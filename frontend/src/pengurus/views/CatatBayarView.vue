<template>
  <div>
    <AppBackHeader title="Catat pembayaran" />

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 mb-4">
      <p class="text-[13px] text-[var(--mut)] m-0">Keluarga</p>
      <p class="font-bold text-[15px] m-0">AB1-05 · Andi Wijaya</p>
      <p class="text-[13px] text-[var(--mut)] m-0 mt-1">Total tagihan: <b>Rp 70.000</b></p>
    </div>

    <form class="space-y-4" @submit.prevent="onSubmit">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal diterima</label>
        <input v-model="nominal" type="text" inputmode="numeric" placeholder="50000"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Metode</label>
        <div class="flex gap-2">
          <button type="button" class="flex-1 min-h-[44px] rounded-full text-[13px] font-semibold border"
            :class="metode === 'tunai' ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="metode = 'tunai'">Tunai</button>
          <button type="button" class="flex-1 min-h-[44px] rounded-full text-[13px] font-semibold border"
            :class="metode === 'transfer' ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="metode = 'transfer'">Transfer</button>
        </div>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal</label>
        <input v-model="tanggal" type="date" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Catatan (opsional)</label>
        <input v-model="catatan" type="text" placeholder="Opsional"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>

      <div v-if="showPreview" class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
        <p class="text-[13px] font-bold mb-2">Pratinjau potongan</p>
        <div class="space-y-1 text-[14px]">
          <div class="flex justify-between"><span>Kas Oktober</span><span class="font-bold text-[var(--g)]">− Rp 40.000 (lunas)</span></div>
          <div class="flex justify-between"><span>Denda ronda</span><span class="font-bold">− Rp 10.000</span></div>
          <div class="flex justify-between border-t border-[var(--line)] pt-2 mt-2"><span>Sisa denda</span><span class="font-bold">Rp 20.000</span></div>
        </div>
      </div>

      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold">
        {{ showPreview ? 'Simpan pembayaran' : 'Lihat pratinjau' }}
      </button>
      <p v-if="saved" class="text-[13px] text-[var(--g)] text-center">Pembayaran tersimpan (dummy). Contoh alokasi: kas lunas, sisa denda Rp 20.000</p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'

const nominal = ref('50000')
const metode = ref('tunai')
const tanggal = ref('2026-10-06')
const catatan = ref('')
const showPreview = ref(false)
const saved = ref(false)

function onSubmit() {
  if (!showPreview.value) {
    showPreview.value = true
    return
  }
  saved.value = true
}
</script>
