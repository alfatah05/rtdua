<template>
  <div>
    <AppBackHeader :title="isMasuk ? 'Kas masuk' : 'Kas keluar'" />

    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal</label>
        <input v-model="nominal" type="text" inputmode="numeric" placeholder="100000"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Kategori</label>
        <select v-model="kategori" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
          <option v-for="k in kategoriList" :key="k" :value="k">{{ k }}</option>
        </select>
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
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Keterangan</label>
        <input v-model="ket" type="text" placeholder="Keterangan"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal</label>
        <input v-model="tanggal" type="date" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold">Simpan</button>
      <p v-if="msg" class="text-[13px] text-[var(--g)] text-center">{{ msg }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const isMasuk = computed(() => route.path.includes('kas-masuk'))
const nominal = ref('')
const kategori = ref(isMasuk.value ? 'Saldo awal' : 'Operasional')
const metode = ref('tunai')
const ket = ref('')
const tanggal = ref('2026-10-06')
const msg = ref('')
const { success } = useToast()

function onSave() { success('Transaksi tersimpan'); msg.value = 'Transaksi tersimpan (dummy)' }

const kategoriList = computed(() =>
  isMasuk.value
    ? ['Saldo awal', 'Donasi', 'Sewa balai', 'Lainnya']
    : ['Operasional', 'Pengembalian kelebihan', 'Perawatan', 'Lainnya']
)
</script>
