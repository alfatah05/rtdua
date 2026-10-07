<template>
  <div>
    <AppBackHeader title="Laporan" />
    <div class="mb-4">
      <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Bulan</label>
      <input v-model="bulan" type="month" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" @change="load" />
    </div>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="data">
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4 space-y-2">
        <div class="flex justify-between"><span class="text-[var(--mut)]">Saldo keseluruhan</span><span class="font-bold">{{ rp(data.saldo_keseluruhan) }}</span></div>
        <div class="flex justify-between"><span class="text-[var(--mut)]">Total masuk</span><span class="font-bold text-[var(--g)]">+ {{ rp(data.total_masuk) }}</span></div>
        <div class="flex justify-between"><span class="text-[var(--mut)]">Total keluar</span><span class="font-bold text-red-500">− {{ rp(data.total_keluar) }}</span></div>
        <div class="flex justify-between border-t border-[var(--line)] pt-2"><span class="font-bold">Netto bulan ini</span><span class="font-extrabold text-lg">{{ rp((data.total_masuk || 0) - (data.total_keluar || 0)) }}</span></div>
      </div>
      <h2 class="text-[15px] font-bold mb-2">Per kategori</h2>
      <div class="space-y-2 mb-5">
        <div v-for="(v, nama) in (data.per_kategori || {})" :key="nama" class="flex justify-between bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5">
          <span>{{ nama }}</span>
          <span class="font-bold text-right text-[13px]">
            <span v-if="v.masuk" class="text-[var(--g)]">+{{ rp(v.masuk) }}</span>
            <span v-if="v.keluar" class="text-red-500 ml-1">−{{ rp(v.keluar) }}</span>
          </span>
        </div>
        <p v-if="!Object.keys(data.per_kategori || {}).length" class="text-[13px] text-[var(--mut)]">Tidak ada transaksi</p>
      </div>
      <div class="space-y-2">
        <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="unduhCsv">Unduh CSV transaksi</button>
        <button type="button" class="w-full min-h-[48px] rounded-full border border-[var(--line)] font-bold" @click="salinRingkas">Salin ringkasan teks</button>
      </div>
      <p v-if="msg" class="text-[13px] text-[var(--g)] text-center mt-3">{{ msg }}</p>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { laporan } from '@shared/services/keuangan.js'

const bulan = ref(new Date().toISOString().slice(0, 7))
const loading = ref(true)
const err = ref('')
const data = ref(null)
const msg = ref('')

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await laporan(bulan.value)
  loading.value = false
  if (!res.ok) { err.value = res.error || 'Gagal'; return }
  data.value = res.data
}

function unduhCsv() {
  const rows = data.value?.transaksi || []
  const lines = [['tanggal', 'tipe', 'kategori', 'nominal', 'keterangan'].join(',')]
  for (const r of rows) {
    lines.push([r.tanggal || '', r.tipe || '', JSON.stringify(r.kategori || ''), r.nominal || 0, JSON.stringify(r.keterangan || '')].join(','))
  }
  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `laporan-kas-${bulan.value}.csv`
  a.click()
  msg.value = 'CSV diunduh'
}

async function salinRingkas() {
  const d = data.value
  if (!d) return
  const t = `Laporan ${d.bulan}\nMasuk: ${rp(d.total_masuk)}\nKeluar: ${rp(d.total_keluar)}\nSaldo: ${rp(d.saldo_keseluruhan)}`
  try {
    await navigator.clipboard.writeText(t)
    msg.value = 'Ringkasan disalin'
  } catch {
    msg.value = t
  }
}

onMounted(load)
</script>
