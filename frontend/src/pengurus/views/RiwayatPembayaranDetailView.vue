<template>
  <div class="pb-8">
    <AppBackHeader title="Detail pembayaran" />

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-6 m-0">{{ err }}</p>

    <template v-else-if="row">
      <div
        v-if="row.dibatalkan"
        class="mb-3 rounded-[12px] bg-red-500/10 text-red-600 text-[13px] font-semibold px-3 py-2 text-center"
      >
        Pembayaran ini sudah dibatalkan
        <span v-if="row.alasan_batal" class="block font-normal text-[12px] mt-0.5 opacity-90">{{ row.alasan_batal }}</span>
      </div>

      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] space-y-3 mb-4">
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Keluarga</p>
          <p class="font-bold m-0 text-[15px]">{{ row.alamat || '—' }}</p>
          <p v-if="row.nama" class="text-[13px] text-[var(--mut)] m-0 mt-0.5">{{ row.nama }}</p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Nominal</p>
          <p class="font-bold text-lg m-0" :class="row.dibatalkan ? 'line-through text-[var(--mut)]' : 'text-[var(--g)]'">
            {{ formatRp(row.nominal) }}
          </p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Metode</p>
          <p class="font-bold m-0">{{ labelMetode(row.metode) }}</p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Tanggal bayar</p>
          <p class="font-bold m-0">{{ formatTanggal(row.tanggal_bayar) }}</p>
        </div>
        <div v-if="row.catatan">
          <p class="text-[13px] text-[var(--mut)] m-0">Catatan</p>
          <p class="font-bold m-0">{{ row.catatan }}</p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Dicatat oleh</p>
          <p class="font-bold m-0">{{ row.dicatat_nama || '—' }}</p>
        </div>
        <div v-if="row.dicatat_pada">
          <p class="text-[13px] text-[var(--mut)] m-0">Waktu catat</p>
          <p class="font-bold m-0">{{ formatWaktu(row.dicatat_pada) }}</p>
        </div>
      </div>

      <!-- Bukti transfer dari permintaan warga -->
      <div
        v-if="row.permintaan"
        class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] space-y-3 mb-4"
      >
        <p class="font-bold text-[15px] m-0">Transfer warga</p>
        <div v-if="row.permintaan.nama_pengirim || row.permintaan.bank_pengirim">
          <p class="text-[13px] text-[var(--mut)] m-0">Pengirim</p>
          <p class="font-bold m-0">
            {{ row.permintaan.nama_pengirim || '—' }}
            <span v-if="row.permintaan.bank_pengirim"> · {{ row.permintaan.bank_pengirim }}</span>
          </p>
        </div>
        <div v-if="row.permintaan.rekening">
          <p class="text-[13px] text-[var(--mut)] m-0">Rekening tujuan</p>
          <p class="font-bold m-0">
            {{ row.permintaan.rekening.nama || row.permintaan.rekening.jenis || '—' }}
            <span v-if="row.permintaan.rekening.nomor"> · {{ row.permintaan.rekening.nomor }}</span>
          </p>
          <p v-if="row.permintaan.rekening.atas_nama" class="text-[13px] text-[var(--mut)] m-0">
            a.n. {{ row.permintaan.rekening.atas_nama }}
          </p>
        </div>
        <div v-if="row.permintaan.nominal_diajukan">
          <p class="text-[13px] text-[var(--mut)] m-0">Nominal diajukan</p>
          <p class="font-bold m-0">{{ formatRp(row.permintaan.nominal_diajukan) }}</p>
        </div>
        <div v-if="buktiUrl">
          <p class="text-[13px] text-[var(--mut)] m-0 mb-2">Bukti transfer</p>
          <a :href="buktiUrl" target="_blank" rel="noopener" class="block">
            <img
              :src="buktiUrl"
              alt="Bukti transfer"
              class="w-full max-h-80 rounded-[12px] object-contain bg-[var(--search)]"
              loading="lazy"
              decoding="async"
            />
          </a>
          <p class="text-[12px] text-[var(--mut)] m-0 mt-1">Ketuk untuk membuka penuh</p>
        </div>
        <p v-else class="text-[13px] text-[var(--mut)] m-0">Tidak ada file bukti.</p>
      </div>

      <div v-else-if="String(row.metode || '').toLowerCase() === 'transfer'" class="mb-4 px-1">
        <p class="text-[13px] text-[var(--mut)] m-0">
          Pembayaran transfer dicatat pengurus (tanpa permintaan warga) — tidak ada unggahan bukti.
        </p>
      </div>

      <button
        type="button"
        class="w-full min-h-[48px] rounded-full border border-[var(--line)] font-bold mb-2 active:scale-[0.98] transition-transform"
        @click="$router.push('/keuangan/tagihan/' + row.keluarga_id)"
      >
        Lihat tagihan keluarga
      </button>

      <button
        v-if="bisaBatal"
        type="button"
        class="w-full min-h-[48px] rounded-full bg-red-500/15 text-red-600 font-bold disabled:opacity-50 active:scale-[0.98] transition-transform"
        :disabled="batalLoading"
        @click="onBatal"
      >
        {{ batalLoading ? 'Membatalkan…' : 'Batalkan pembayaran' }}
      </button>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { detailPembayaran, batalkanPembayaran } from '@shared/services/keuangan.js'
import { mediaUrl } from '@shared/services/upload.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const router = useRouter()
const { success, show } = useToast()

const loading = ref(true)
const err = ref('')
const row = ref(null)
const batalLoading = ref(false)

const bisaBatal = computed(() => row.value && !row.value.dibatalkan)

const buktiUrl = computed(() => {
  const f = row.value?.bukti_file || row.value?.permintaan?.bukti_file
  return f ? mediaUrl(f) : ''
})

function formatRp(n) {
  return 'Rp ' + Math.abs(Number(n) || 0).toLocaleString('id-ID')
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
  const res = await detailPembayaran(id)
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat detail'
    return
  }
  row.value = res.data
}

async function onBatal() {
  if (!bisaBatal.value || !row.value) return
  if (!window.confirm('Batalkan pembayaran ini? Saldo sub pos / tagihan akan disesuaikan.')) return
  const alasan = window.prompt('Alasan pembatalan (opsional):', '') ?? ''
  batalLoading.value = true
  const res = await batalkanPembayaran(row.value.id, alasan)
  batalLoading.value = false
  if (!res.ok) {
    show(res.error || 'Gagal membatalkan')
    return
  }
  success('Pembayaran dibatalkan')
  await load()
}

onMounted(load)
</script>
