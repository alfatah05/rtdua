<template>
  <div>
    <AppBackHeader title="Rincian iuran" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 mb-4">
        <p class="text-[13px] text-[var(--mut)] m-0">{{ heroLabel }}</p>
        <p class="text-[28px] font-extrabold mt-1 m-0">{{ heroNominal }}</p>
      </div>

      <div v-if="permintaanMenunggu.length || permintaanSelesai.length" class="mb-4 space-y-2">
        <h2 class="text-[15px] font-bold m-0 mb-1">Status permintaan</h2>
        <div
          v-for="p in permintaanMenunggu"
          :key="'pm-' + p.id"
          class="bg-amber-500/10 border border-amber-500/20 rounded-[16px] p-3.5"
        >
          <p class="font-bold m-0 text-[14px]">Menunggu konfirmasi pengurus</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ rp(p.nominal_diajukan ?? p.nominal) }} · {{ p.diajukan_pada || '—' }}</p>
        </div>
        <div
          v-for="p in permintaanSelesai"
          :key="'ps-' + p.id"
          class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5"
        >
          <p class="font-bold m-0 text-[14px]">{{ labelStatus(p.status) }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">
            {{ rp(p.nominal_dikonfirmasi ?? p.nominal_diajukan ?? p.nominal) }}
            <span v-if="p.alasan_tolak"> · {{ p.alasan_tolak }}</span>
          </p>
        </div>
      </div>

      <h2 class="text-[15px] font-bold mb-2">Tagihan</h2>
      <div class="space-y-2 mb-4">
        <div v-for="t in tagihan" :key="t.id" class="flex justify-between bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5">
          <div>
            <p class="font-bold m-0">{{ labelJenis(t.jenis) }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0">{{ t.periode }}</p>
          </div>
          <span class="font-bold">{{ rp(t.sisa ?? t.nominal) }}</span>
        </div>
        <p v-if="!tagihan.length" class="text-[13px] text-[var(--mut)] text-center py-2">Tidak ada tagihan terbuka</p>
      </div>

      <h2 class="text-[15px] font-bold mb-2">Riwayat pembayaran</h2>
      <div class="space-y-2 mb-5">
        <div v-for="b in pembayaran" :key="b.id" class="flex justify-between bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5">
          <div>
            <p class="font-bold m-0">{{ rp(b.nominal) }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0">{{ b.tanggal_bayar || '—' }} · {{ b.metode || '—' }}</p>
          </div>
          <span class="text-[12px] text-[var(--mut)]">{{ b.catatan || '' }}</span>
        </div>
        <p v-if="!pembayaran.length" class="text-[13px] text-[var(--mut)] text-center py-2">Belum ada pembayaran</p>
      </div>

      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="$router.push('/transfer')">
        Saya sudah transfer
      </button>
    </template>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalRingkasan, portalStatusPermintaan } from '@shared/services/keuangan.js'

const loading = ref(true)
const err = ref('')
const total = ref(0)
const tagihan = ref([])
const pembayaran = ref([])
const permintaan = ref([])

function rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID') }
function labelJenis(j) {
  const m = { kas: 'Kas', denda_ronda: 'Denda ronda', khusus: 'Iuran khusus' }
  return m[j] || j || 'Tagihan'
}
function labelStatus(s) {
  if (s === 'dikonfirmasi') return 'Dikonfirmasi'
  if (s === 'ditolak') return 'Ditolak'
  return s || '—'
}

const heroLabel = computed(() => {
  if (total.value > 0) return 'Total belum lunas'
  if (total.value < 0) return 'Kelebihan bayar'
  return 'Status iuran'
})
const heroNominal = computed(() => {
  if (total.value > 0) return rp(total.value)
  if (total.value < 0) return rp(-total.value)
  return 'Lunas'
})
const permintaanMenunggu = computed(() =>
  (permintaan.value || []).filter((p) => p.status === 'menunggu')
)
const permintaanSelesai = computed(() =>
  (permintaan.value || []).filter((p) => p.status !== 'menunggu').slice(0, 3)
)

onMounted(async () => {
  const [res, pm] = await Promise.all([portalRingkasan(), portalStatusPermintaan()])
  loading.value = false
  if (!res.ok) { err.value = res.error || 'Gagal'; return }
  total.value = Number(res.data?.total ?? 0)
  tagihan.value = res.data?.tagihan || []
  pembayaran.value = res.data?.pembayaran || []
  if (pm.ok) {
    permintaan.value = Array.isArray(pm.data) ? pm.data : (pm.data?.items || [])
  }
})
</script>
