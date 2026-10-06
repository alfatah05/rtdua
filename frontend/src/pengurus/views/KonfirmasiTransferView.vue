<template>
  <div>
    <AppBackHeader title="Konfirmasi transfer" />
    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 mb-4">
      <p class="text-[13px] text-[var(--mut)] m-0">AB2-22 · Budi Santoso</p>
      <p class="font-bold text-lg m-0">Diajukan: Rp 50.000</p>
    </div>
    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal diterima</label>
        <input v-model="nominal" type="text" inputmode="numeric" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal</label>
        <input v-model="tanggal" type="date" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 text-[14px]">
        <p class="font-bold mb-2 m-0">Pratinjau potongan</p>
        <div class="flex justify-between"><span>Kas Oktober</span><span class="font-bold text-[var(--g)]">− Rp 40.000</span></div>
        <div class="flex justify-between"><span>Denda</span><span class="font-bold">− Rp 10.000</span></div>
      </div>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold">Konfirmasi & simpan</button>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useToast } from '@shared/composables/useToast.js'
const router = useRouter()
const { success } = useToast()
const nominal = ref('50000')
const tanggal = ref('2026-10-06')
function onSave() {
  success('Transfer dikonfirmasi')
  setTimeout(() => router.back(), 600)
}
</script>
