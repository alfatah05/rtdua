<template>
  <div class="pb-8">
    <AppBackHeader title="Detail kas" />

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-6 m-0">{{ err }}</p>

    <template v-else-if="row">
      <div
        v-if="row.dibatalkan"
        class="mb-3 rounded-[12px] bg-red-500/10 text-red-600 text-[13px] font-semibold px-3 py-2 text-center"
      >
        Transaksi ini sudah dibatalkan
        <span v-if="row.alasan_batal" class="block font-normal text-[12px] mt-0.5 opacity-90">{{ row.alasan_batal }}</span>
      </div>

      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] space-y-3 mb-5">
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Keterangan</p>
          <p class="font-bold m-0 text-[15px]">{{ row.keterangan || '—' }}</p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Nominal</p>
          <p
            class="font-bold text-lg m-0"
            :class="row.is_alokasi ? 'text-sky-600' : (row.tipe === 'masuk' ? 'text-[var(--g)]' : 'text-red-500')"
          >
            <template v-if="row.is_alokasi">{{ formatRp(row.nominal) }}</template>
            <template v-else>{{ row.tipe === 'masuk' ? '+' : '−' }}{{ formatRp(row.nominal) }}</template>
          </p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Jenis</p>
          <p class="font-bold m-0">{{ labelJenis }}</p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Kategori</p>
          <p class="font-bold m-0">{{ row.kategori || '—' }}</p>
        </div>
        <div v-if="row.kas_pos_nama">
          <p class="text-[13px] text-[var(--mut)] m-0">Pos kas</p>
          <p class="font-bold m-0">{{ row.kas_pos_nama }}</p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Metode</p>
          <p class="font-bold m-0">{{ labelMetode(row.metode) }}</p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Tanggal</p>
          <p class="font-bold m-0">{{ formatTanggal(row.tanggal) }}</p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Dicatat oleh</p>
          <p class="font-bold m-0">{{ row.dicatat_nama || '—' }}</p>
        </div>
        <div v-if="row.dicatat_pada">
          <p class="text-[13px] text-[var(--mut)] m-0">Waktu catat</p>
          <p class="font-bold m-0">{{ formatWaktu(row.dicatat_pada) }}</p>
        </div>
        <div v-if="row.otomatis">
          <p class="text-[12px] text-[var(--mut)] m-0 leading-snug">
            Transaksi otomatis dari pembayaran iuran. Batalkan lewat pembatalan pembayaran, bukan dari sini.
          </p>
        </div>
      </div>

      <button
        v-if="bisaBatal"
        type="button"
        class="w-full min-h-[48px] rounded-full bg-red-500/15 text-red-600 font-bold disabled:opacity-50 active:scale-[0.98] transition-transform"
        :disabled="batalLoading"
        @click="onBatal"
      >
        {{ batalLoading ? 'Membatalkan…' : 'Batalkan transaksi' }}
      </button>
      <p v-else-if="row.dibatalkan" class="text-[12px] text-[var(--mut)] text-center m-0">Tidak bisa dibatalkan lagi.</p>
      <p v-else-if="row.otomatis" class="text-[12px] text-[var(--mut)] text-center m-0">Kas dari iuran tidak dibatalkan di sini.</p>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { getKasDetail, batalkanKas } from '@shared/services/keuangan.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const router = useRouter()
const { success, show } = useToast()

const loading = ref(true)
const err = ref('')
const row = ref(null)
const batalLoading = ref(false)

const bisaBatal = computed(() => {
  const r = row.value
  if (!r || r.dibatalkan || r.otomatis) return false
  return true
})

const labelJenis = computed(() => {
  const r = row.value
  if (!r) return '—'
  if (r.is_alokasi) return 'Alokasi antar pos'
  return r.tipe === 'masuk' ? 'Pemasukan' : 'Pengeluaran'
})

function formatRp(n) {
  const x = Math.abs(Number(n) || 0)
  return 'Rp ' + x.toLocaleString('id-ID')
}

function formatTanggal(s) {
  if (!s) return '—'
  const d = new Date(String(s).slice(0, 10) + 'T12:00:00')
  if (Number.isNaN(d.getTime())) return s
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

function formatWaktu(s) {
  if (!s) return '—'
  const d = new Date(String(s).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return s
  return d.toLocaleString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function labelMetode(m) {
  const map = { tunai: 'Tunai', transfer: 'Transfer', qris: 'QRIS', lain: 'Lainnya' }
  const k = String(m || '').toLowerCase()
  return map[k] || (m || '—')
}

async function load() {
  loading.value = true
  err.value = ''
  const id = route.params.id
  const res = await getKasDetail(id)
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat detail'
    return
  }
  row.value = res.data
}

async function onBatal() {
  if (!bisaBatal.value || !row.value) return
  const ok = window.confirm(
    row.value.is_alokasi
      ? 'Batalkan alokasi ini? Kedua sisi (keluar & masuk) akan dibatalkan.'
      : 'Batalkan transaksi ini? Saldo kas akan disesuaikan.'
  )
  if (!ok) return
  const alasan = window.prompt('Alasan pembatalan (opsional):', '') ?? ''
  batalLoading.value = true
  const res = await batalkanKas(row.value.id, alasan)
  batalLoading.value = false
  if (!res.ok) {
    show(res.error || 'Gagal membatalkan')
    return
  }
  success('Transaksi dibatalkan')
  setTimeout(() => router.replace('/keuangan'), 500)
}

onMounted(load)
</script>
