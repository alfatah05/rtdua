<template>
  <div class="pb-[calc(72px+env(safe-area-inset-bottom,0px))]">
    <AppBackHeader title="Rincian iuran" />

    <AppPageSpinner v-if="loading" />
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

      <div v-if="permintaanMenunggu.length" class="mb-5 space-y-2.5">
        <h2 class="text-[15px] font-bold m-0 mb-1">Status permintaan</h2>
        <div
          v-for="p in permintaanMenunggu"
          :key="'pm-' + p.id"
          class="w-full rounded-[20px] p-5 relative overflow-hidden shadow-[var(--sh)]"
          :style="stylePermintaan"
        >
          <div class="relative z-10 flex items-start gap-3">
            <div class="w-11 h-11 rounded-full grid place-items-center shrink-0 bg-white/20 text-white">
              <Clock :size="20" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[13px] font-semibold m-0 text-white/90">Status permintaan</p>
              <p class="text-[17px] font-extrabold m-0 mt-1 leading-snug text-white">
                Menunggu konfirmasi pengurus
              </p>
              <p class="text-[13px] m-0 mt-1.5 text-white/85">
                {{ rp(p.nominal_diajukan ?? p.nominal) }} · {{ formatTgl(p.diajukan_pada) }}
              </p>
            </div>
          </div>
          <div class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"></div>
        </div>
      </div>

      <h2 class="text-[15px] font-bold m-0 mb-2">Rincian tagihan</h2>
      <div
        class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] shadow-[var(--sh)] mb-4"
        style="padding: 5px"
      >
        <div
          class="flex items-center justify-between px-3"
          style="height: 30px; border-radius: 15px; background: rgba(34, 184, 99, 0.14)"
        >
          <span class="text-[12px] font-semibold text-[var(--gm)]">Jenis Pembayaran</span>
          <span class="text-[12px] font-semibold text-[var(--gm)]">Biaya</span>
        </div>

        <template v-if="tagihanTerbuka.length">
          <div v-for="t in tagihanTerbuka" :key="t.id">
            <button
              type="button"
              class="w-full flex items-center gap-3 text-left"
              :class="[
                bisaExpandKas(t) && isKasOpen(t.id) ? 'pt-2.5 pb-0.5' : 'py-2.5',
                bisaExpandKas(t) ? 'active:opacity-80' : 'cursor-default',
              ]"
              style="padding-left: 10px; padding-right: 10px"
              @click="bisaExpandKas(t) && toggleKas(t.id)"
            >
              <div
                class="w-9 h-9 rounded-full grid place-items-center shrink-0"
                :style="{ background: iconMeta(t.jenis).bg, color: iconMeta(t.jenis).color }"
              >
                <component :is="iconMeta(t.jenis).icon" :size="16" />
              </div>
              <div class="flex-1 min-w-0 flex items-center gap-1">
                <p class="text-[14px] font-semibold m-0 truncate">
                  {{ labelTagihan(t) }}
                </p>
                <ChevronDown
                  v-if="bisaExpandKas(t)"
                  :size="16"
                  class="shrink-0 text-[var(--text)] transition-transform duration-200"
                  :class="isKasOpen(t.id) ? 'rotate-180' : ''"
                />
              </div>
              <span class="text-[14px] font-bold tabular-nums shrink-0 whitespace-nowrap">
                {{ rp(t.sisa ?? t.nominal) }}
              </span>
            </button>

            <div
              v-if="bisaExpandKas(t) && isKasOpen(t.id)"
              class="pb-1"
            >
              <div
                v-for="c in komponenKas"
                :key="'k-' + t.id + '-' + c.id"
                class="flex items-center gap-3 py-1"
                style="padding-left: 10px; padding-right: 10px"
              >
                <div
                  class="w-9 shrink-0 opacity-0 pointer-events-none"
                  aria-hidden="true"
                ></div>
                <p class="flex-1 min-w-0 text-[13px] font-medium m-0 truncate text-[var(--mut)] leading-snug">
                  {{ c.nama }}
                </p>
                <span class="text-[13px] font-semibold tabular-nums shrink-0 whitespace-nowrap text-[var(--mut)] leading-snug">
                  {{ rp(c.nominal) }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex justify-end" style="padding: 2px 0 4px">
            <div
              class="inline-flex items-center justify-between gap-4 px-3 min-w-[48%]"
              style="height: 30px; border-radius: 15px; background: rgba(245, 158, 11, 0.16)"
            >
              <span class="text-[12px] font-bold" style="color: #B45309">Total</span>
              <span class="text-[13px] font-extrabold tabular-nums" style="color: #D97706">
                {{ rp(totalTerbuka) }}
              </span>
            </div>
          </div>
        </template>
        <p v-else class="text-[13px] text-[var(--mut)] text-center py-5 m-0">Tidak ada tagihan terbuka</p>
      </div>

      <h2 class="text-[15px] font-bold m-0 mb-1">Riwayat pembayaran</h2>

      <div v-if="riwayat.length" class="mb-2">
        <div
          v-for="r in riwayat"
          :key="'rw-' + (r.pembayaran_id || r.id || r.tanggal) + '-' + (r.tagihan_id || '')"
          class="flex items-center gap-3 py-2.5"
        >
          <div
            class="w-9 h-9 rounded-full grid place-items-center shrink-0"
            :style="{ background: iconMeta(r.jenis).bg, color: iconMeta(r.jenis).color }"
          >
            <component :is="iconMeta(r.jenis).icon" :size="16" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-[14px] font-semibold m-0 truncate">{{ labelRiwayat(r) }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">
              {{ formatTgl(r.tanggal) }}<template v-if="r.metode"> · {{ labelMetode(r.metode) }}</template>
            </p>
          </div>
          <span class="text-[14px] font-bold tabular-nums shrink-0 whitespace-nowrap">
            {{ rp(r.jumlah ?? r.nominal) }}
          </span>
        </div>
      </div>
      <p v-else class="text-[13px] text-[var(--mut)] text-center py-4 m-0">Belum ada riwayat</p>
    </template>

    <!-- Sticky bottom: tanpa background bar, hanya button -->
    <div
      v-if="!loading && !err"
      class="fixed bottom-0 left-0 right-0 z-30 pointer-events-none"
    >
      <div class="max-w-[560px] mx-auto px-4 pt-2 pb-[max(12px,env(safe-area-inset-bottom,0px))]">
        <button
          type="button"
          class="pointer-events-auto w-full min-h-[48px] rounded-full bg-[var(--btn)] text-[var(--btn-fg)] font-bold active:scale-[0.98] transition shadow-lg"
          @click="$router.push('/bayar')"
        >
          Bayar iuran
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'

import { ref, computed, onMounted } from 'vue'
import { Wallet, Home, ShieldAlert, Sparkles, Clock, ChevronDown } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalRingkasan, portalStatusPermintaan } from '@shared/services/keuangan.js'

const loading = ref(true)
const err = ref('')
const total = ref(0)
const tagihan = ref([])
const riwayat = ref([])
const permintaan = ref([])
const komponenKas = ref([])
const totalKasBulanan = ref(0)
const kasOpen = ref({})

function bisaExpandKas(t) {
  return t?.jenis === 'kas' && (komponenKas.value || []).length > 0
}
function isKasOpen(id) {
  return !!kasOpen.value[id]
}
function toggleKas(id) {
  kasOpen.value = { ...kasOpen.value, [id]: !kasOpen.value[id] }
}

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
  if (jenis === 'kas') return 'Iuran'
  if (jenis === 'denda_ronda') return 'Denda ronda'
  if (jenis === 'khusus') return 'Iuran khusus'
  if (jenis === 'kelebihan') return 'Kelebihan bayar'
  return jenis || 'Tagihan'
}

function iconMeta(jenis) {
  if (jenis === 'kas') {
    return { icon: Home, bg: 'rgba(59,130,246,.16)', color: '#2563EB' }
  }
  if (jenis === 'denda_ronda') {
    return { icon: ShieldAlert, bg: 'rgba(168,85,247,.16)', color: '#7C3AED' }
  }
  if (jenis === 'khusus') {
    return { icon: Sparkles, bg: 'rgba(236,72,153,.16)', color: '#DB2777' }
  }
  if (jenis === 'kelebihan') {
    return { icon: Wallet, bg: 'rgba(6,182,212,.16)', color: '#0891B2' }
  }
  return { icon: Wallet, bg: 'rgba(99,102,241,.14)', color: '#4F46E5' }
}

function labelTagihan(t) {
  // Tagihan khusus: tampilkan judul saja (tanpa suffix periode)
  if (t.jenis === 'khusus' && t.nama) {
    return t.nama
  }
  const jenis = labelJenisDasar(t.jenis)
  const bln = bulanDariPeriode(t.periode)
  return bln ? `${jenis} ${bln}` : jenis
}

function labelRiwayat(r) {
  if (r.jenis === 'kelebihan') return 'Kelebihan bayar'
  if (r.jenis === 'pembayaran') return 'Pembayaran'
  if (r.jenis === 'khusus') {
    if (r.nama) return r.nama
    const t = tagihan.value.find((x) => Number(x.id) === Number(r.tagihan_id))
    if (t?.nama) return t.nama
  }
  return labelTagihan(r)
}

function labelMetode(m) {
  if (m === 'transfer') return 'Transfer'
  if (m === 'tunai') return 'Tunai'
  return m || ''
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
  if (heroStatus.value === 'pending') return 'Segera lunasi agar data RT tetap akurat'
  if (heroStatus.value === 'lunas') return 'Tidak ada tagihan terbuka'
  if (heroStatus.value === 'lebih') return 'Saldo lebih akan dipotong tagihan berikutnya'
  return 'Memuat data...'
})

const styleTagihan = {
  background: '#F59E0B',
}
const styleLunas = {
  background: '#22B863',
}
const styleLebih = {
  background: '#3B82F6',
}
const styleLoading = {
  background: 'var(--card2)',
}

const heroStyle = computed(() => {
  if (heroStatus.value === 'pending') return styleTagihan
  if (heroStatus.value === 'lunas') return styleLunas
  if (heroStatus.value === 'lebih') return styleLebih
  return styleLoading
})

const permintaanMenunggu = computed(() =>
  (permintaan.value || []).filter((p) => p.status === 'menunggu')
)

const stylePermintaan = {
  background: '#F59E0B',
}

onMounted(async () => {
  const [res, pm] = await Promise.all([portalRingkasan(), portalStatusPermintaan()])
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat rincian'
    return
  }
  total.value = Number(res.data?.total ?? 0)
  tagihan.value = res.data?.tagihan || []
  komponenKas.value = Array.isArray(res.data?.komponen_kas) ? res.data.komponen_kas : []
  totalKasBulanan.value = Number(res.data?.total_kas_bulanan ?? 0)
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
