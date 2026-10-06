<template>
  <div>
    <AppBackHeader title="Catat pembayaran" />

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 mb-4">
      <p class="text-[13px] text-[var(--mut)] m-0">Keluarga</p>
      <p class="font-bold text-[15px] m-0">AB1-05 · Andi Wijaya</p>
      <p class="text-[13px] text-[var(--mut)] m-0 mt-1">Total tagihan: <b>{{ formatRp(totalTagihan) }}</b></p>
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
          <div v-for="p in hasilAlokasi.potongan" :key="p.tagihan_id" class="flex justify-between gap-2">
            <span>{{ labelJenis[p.jenis] || p.jenis }} {{ p.periode }}</span>
            <span class="font-bold text-[var(--g)]">− {{ formatRp(p.jumlah) }}</span>
          </div>
          <div v-if="hasilAlokasi.sisaBayar > 0" class="flex justify-between text-[var(--mut)] pt-1">
            <span>Kelebihan bayar</span>
            <span class="font-bold">{{ formatRp(hasilAlokasi.sisaBayar) }}</span>
          </div>
        </div>
      </div>

      <p v-if="saved" class="text-[13px] text-[var(--g)] text-center">Pembayaran tersimpan (dummy).</p>

      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold">
        {{ showPreview ? 'Simpan pembayaran' : 'Lihat pratinjau' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { alokasiPembayaran } from '@shared/utils/alokasiPembayaran.js'
import { formatRp } from '@shared/utils/format.js'
import { useToast } from '@shared/composables/useToast.js'

const { success } = useToast()

const tagihanContoh = [
  { id: 201, jenis: 'kas', periode: '2026-08', sisa: 40000 },
  { id: 202, jenis: 'denda_ronda', periode: '2026-09', sisa: 10000 },
  { id: 203, jenis: 'denda_ronda', periode: '2026-09', sisa: 10000 },
  { id: 204, jenis: 'denda_ronda', periode: '2026-10', sisa: 10000 },
]
const totalTagihan = tagihanContoh.reduce((s, t) => s + t.sisa, 0)

const nominal = ref('50000')
const metode = ref('tunai')
const tanggal = ref(new Date().toISOString().slice(0, 10))
const catatan = ref('')
const showPreview = ref(false)
const saved = ref(false)

const hasilAlokasi = computed(() => {
  const n = parseInt(String(nominal.value).replace(/\D/g, ''), 10) || 0
  return alokasiPembayaran(tagihanContoh, n)
})

const labelJenis = { kas: 'Kas', denda_ronda: 'Denda ronda', khusus: 'Iuran khusus' }

function onSubmit() {
  if (!showPreview.value) {
    showPreview.value = true
    saved.value = false
    return
  }
  saved.value = true
  success('Pembayaran tersimpan')
}
</script>
