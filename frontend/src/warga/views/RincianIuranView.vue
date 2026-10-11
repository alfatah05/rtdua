<template>
  <div>
    <AppBackHeader title="Rincian iuran" />

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div
        class="w-full rounded-[20px] p-5 mb-4 relative overflow-hidden shadow-[var(--sh)]"
        :class="heroStatus === 'loading' ? 'border border-[var(--line)]' : ''"
        :style="heroStyle"
      >
        <div class="relative z-10 flex items-start gap-3">
          <div
            class="w-11 h-11 rounded-full grid place-items-center shrink-0"
            :class="heroStatus === 'loading' ? 'bg-[var(--card)] text-[var(--gm)]' : 'bg-white/20 text-white'"
          >
            <Wallet :size="20" />
          </div>
          <div class="flex-1 min-w-0">
            <p
              class="text-[13px] font-semibold m-0"
              :class="heroStatus === 'loading' ? 'text-[var(--mut)]' : 'text-white/90'"
            >
              {{ heroTitle }}
            </p>
            <p
              class="text-[28px] font-extrabold tracking-tight m-0 mt-1 leading-none"
              :class="heroStatus === 'loading' ? 'text-[var(--text)]' : 'text-white'"
            >
              {{ heroNominal }}
            </p>
            <p
              class="text-[13px] m-0 mt-2"
              :class="heroStatus === 'loading' ? 'text-[var(--mut)]' : 'text-white/85'"
            >
              {{ heroHint }}
            </p>
          </div>
        </div>
        <div
          v-if="heroStatus !== 'loading'"
          class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"
        ></div>
      </div>

      <div v-if="permintaanMenunggu.length || permintaanSelesai.length" class="mb-5 space-y-2">
        <h2 class="text-[15px] font-bold m-0 mb-1">Status permintaan</h2>
        <div
          v-for="p in permintaanMenunggu"
          :key="'pm-' + p.id"
          class="bg-amber-500/10 border border-amber-500/25 rounded-[16px] p-3.5"
        >
          <p class="font-bold m-0 text-[14px] text-amber-800 dark:text-amber-300">Menunggu konfirmasi pengurus</p>
          <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5">
            {{ rp(p.nominal_diajukan ?? p.nominal) }} · {{ formatTgl(p.diajukan_pada) }}
          </p>
        </div>
        <div
          v-for="p in permintaanSelesai"
          :key="'ps-' + p.id"
          class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5"
        >
          <p class="font-bold m-0 text-[14px]">{{ labelStatus(p.status) }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5">
            {{ rp(p.nominal_dikonfirmasi ?? p.nominal_diajukan ?? p.nominal) }}
            <span v-if="p.alasan_tolak"> · {{ p.alasan_tolak }}</span>
          </p>
        </div>
      </div>

      <h2 class="text-[15px] font-bold m-0 mb-2">Rincian tagihan</h2>
      <div
        class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] shadow-[var(--sh)] mb-5 px-4 py-4"
      >
        <div
          class="flex items-center justify-between rounded-full px-4 py-2.5 mb-1"
          style="background: rgba(34, 184, 99, 0.14)"
        >
          <span class="text-[13px] font-semibold text-[var(--gm)]">Jenis Pembayaran</span>
          <span class="text-[13px] font-semibold text-[var(--gm)]">Biaya</span>
        </div>

        <template v-if="tagihanTerbuka.length">
          <div
            v-for="t in tagihanTerbuka"
            :key="t.id"
            class="flex items-center gap-3 py-3"
          >
            <div
              class="w-10 h-10 rounded-full grid place-items-center shrink-0"
              :style="{ background: iconMeta(t.jenis).bg, color: iconMeta(t.jenis).color }"
            >
              <component :is="iconMeta(t.jenis).icon" :size="18" />
            </div>
            <p class="flex-1 min-w-0 text-[14px] font-semibold m-0 truncate">
              {{ labelTagihan(t) }}
            </p>
            <span class="text-[14px] font-bold tabular-nums shrink-0 whitespace-nowrap">
              {{ rp(t.sisa ?? t.nominal) }}
            </span>
          </div>

          <div class="flex justify-end pt-1">
            <div
              class="inline-flex items-center justify-between gap-4 rounded-full px-4 py-2 min-w-[48%]"
              style="background: rgba(34, 184, 99, 0.14)"
            >
              <span class="text-[13px] font-bold text-[var(--gm)]">Total</span>
              <span class="text-[14px] font-extrabold tabular-nums text-[var(--g)]">
                {{ rp(totalTerbuka) }}
              </span>
            </div>
          </div>
        </template>
        <p v-else class="text-[13px] text-[var(--mut)] text-center py-5 m-0">Tidak ada tagihan terbuka</p>
      </div>

      <h2 class="text-[15px] font-bold m-0 mb-1">Riwayat pembayaran</h2>
      <p class="text-[12px] text-[var(--mut)] m-0 mb-2">Per jenis tagihan yang sudah dibayar</p>

      <div v-if="riwayat.length" class="mb-6">
        <div
          v-for="(r, i) in riwayat"
          :key="(r.pembayaran_id || i) + '-' + (r.tagihan_id || i) + '-' + i"
          class="flex items-center gap-3 py-3 border-b border-[var(--line)] last:border-b-0"
        >
          <div class="min-w-0 flex-1">
            <p class="text-[14px] font-semibold m-0 leading-snug">{{ labelRiwayat(r) }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">
              {{ formatTgl(r.tanggal) }}
              <span v-if="r.metode"> · {{ labelMetode(r.metode) }}</span>
            </p>
          </div>
          <span class="text-[14px] font-bold tabular-nums shrink-0 text-[var(--g)]">
            {{ rp(r.jumlah) }}
          </span>
        </div>
      </div>
      <p v-else class="text-[13px] text-[var(--mut)] text-center py-4 mb-6">Belum ada pembayaran</p>

      <button
        type="button"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold active:scale-[0.98] transition"
        @click="$router.push('/transfer')"
      >
        Saya sudah transfer
      </button>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Wallet, Home, ShieldAlert, Sparkles } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalRingkasan, portalStatusPermintaan } from '@shared/services/keuangan.js'

const loading = ref(true)
const err = ref('')
const total = ref(0)
const tagihan = ref([])
const riwayat = ref([])
const permintaan = ref([])

const BULAN = [
  '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}

function bulanDariPeriode(periode) {
  if (!periode || String(periode).length < 7) return ''
  const m = Number(String(periode).slice(5, 7))
  return BULAN[m] || String(periode)
}

function labelJenisDasar(jenis) {
  if (jenis === 'kas') return 'Kas'
  if (jenis === 'denda_ronda') return 'Denda ronda'
  if (jenis === 'khusus') return 'Iuran khusus'
  if (jenis === 'kelebihan') return 'Kelebihan bayar'
  return jenis || 'Tagihan'
}

function iconMeta(jenis) {
  if (jenis === 'kas') {
    return { icon: Home, bg: 'rgba(34,184,99,.16)', color: '#0F9D4E' }
  }
  if (jenis === 'denda_ronda') {
    return { icon: ShieldAlert, bg: 'rgba(245,158,11,.16)', color: '#D97706' }
  }
  if (jenis === 'khusus') {
    return { icon: Sparkles, bg: 'rgba(59,130,246,.16)', color: '#2563EB' }
  }
  return { icon: Wallet, bg: 'rgba(107,114,128,.14)', color: '#6B7280' }
}

/** Contoh: "Kas Oktober", "Denda ronda Oktober" */
function labelTagihan(t) {
  const jenis = labelJenisDasar(t.jenis)
  const bln = bulanDariPeriode(t.periode)
  return bln ? `${jenis} ${bln}` : jenis
}

function labelRiwayat(r) {
  if (r.jenis === 'kelebihan') return 'Kelebihan bayar'
  if (r.jenis === 'pembayaran') return 'Pembayaran'
  return labelTagihan(r)
}

function labelMetode(m) {
  if (m === 'transfer') return 'Transfer'
  if (m === 'tunai') return 'Tunai'
  return m || ''
}

function labelStatus(s) {
  if (s === 'dikonfirmasi') return 'Dikonfirmasi'
  if (s === 'ditolak') return 'Ditolak'
  if (s === 'menunggu') return 'Menunggu konfirmasi'
  return s || '—'
}

function formatTgl(s) {
  if (!s) return '—'
  const raw = String(s).replace(' ', 'T')
  const d = new Date(raw.length === 10 ? raw + 'T00:00:00' : raw)
  if (Number.isNaN(d.getTime())) return String(s).slice(0, 10)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const tagihanTerbuka = computed(() =>
  (tagihan.value || []).filter((t) => Number(t.sisa ?? t.nominal ?? 0) > 0)
)
const totalTerbuka = computed(() =>
  tagihanTerbuka.value.reduce((s, t) => s + Number(t.sisa ?? t.nominal ?? 0), 0)
)

const heroStatus = computed(() => {
  if (loading.value) return 'loading'
  if (total.value > 0) return 'pending'
  if (total.value < 0) return 'lebih'
  return 'lunas'
})
const heroTitle = computed(() => {
  if (heroStatus.value === 'lebih') return 'Kelebihan bayar'
  return 'Jumlah tagihan anda'
})
const heroNominal = computed(() => {
  if (total.value > 0) return rp(total.value)
  if (total.value < 0) return rp(-total.value)
  return 'Lunas'
})
const heroHint = computed(() => {
  if (total.value > 0) return 'Belum lunas · Rincian di bawah'
  if (total.value < 0) return 'Saldo menguntungkan'
  return 'Semua tagihan sudah dibayar'
})

const styleTagihan = {
  background:
    'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #F59E0B, #D97706 55%, #B45309)',
}
const styleLunas = {
  background:
    'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)',
}
const styleLebih = {
  background:
    'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #3B82F6, #2563EB 55%, #1D4ED8)',
}
const styleLoading = { background: 'var(--card2)' }

const heroStyle = computed(() => {
  if (heroStatus.value === 'pending') return styleTagihan
  if (heroStatus.value === 'lunas') return styleLunas
  if (heroStatus.value === 'lebih') return styleLebih
  return styleLoading
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
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat rincian'
    return
  }
  total.value = Number(res.data?.total ?? 0)
  tagihan.value = res.data?.tagihan || []
  if (Array.isArray(res.data?.riwayat) && res.data.riwayat.length) {
    riwayat.value = res.data.riwayat
  } else {
    riwayat.value = (res.data?.pembayaran || []).map((b) => ({
      pembayaran_id: b.id,
      tagihan_id: null,
      tanggal: b.tanggal_bayar,
      jenis: 'pembayaran',
      periode: '',
      jumlah: b.nominal,
      metode: b.metode,
    }))
  }
  if (pm.ok) {
    permintaan.value = Array.isArray(pm.data) ? pm.data : (pm.data?.items || [])
  }
})
</script>
