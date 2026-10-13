<template>
  <div>
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
          <div
            v-for="t in tagihanTerbuka"
            :key="t.id"
            class="flex items-center gap-3 py-2.5"
            style="padding-left: 10px; padding-right: 10px"
          >
            <div
              class="w-9 h-9 rounded-full grid place-items-center shrink-0"
              :style="{ background: iconMeta(t.jenis).bg, color: iconMeta(t.jenis).color }"
            >
              <component :is="iconMeta(t.jenis).icon" :size="16" />
            </div>
            <p class="flex-1 min-w-0 text-[14px] font-semibold m-0 truncate">
              {{ labelTagihan(t) }}
            </p>
            <span class="text-[14px] font-bold tabular-nums shrink-0 whitespace-nowrap">
              {{ rp(t.sisa ?? t.nominal) }}
            </span>
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

      <button
        type="button"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold active:scale-[0.98] transition mb-6"
        @click="$router.push('/transfer')"
      >
        Bayar tagihan
      </button>

      <h2 class="text-[15px] font-bold m-0 mb-1">Riwayat pembayaran</h2>

      <div v-if="riwayat.length" class="mb-2">
        <div
          v-for="(r, i) in riwayat"
          :key="(r.pembayaran_id || i) + '-' + (r.tagihan_id || i) + '-' + i"
          class="flex items-center gap-3 py-3 border-b border-[var(--line)] last:border-b-0"
        >
          <div
            class="w-9 h-9 rounded-full grid place-items-center shrink-0"
            :style="{ background: iconMeta(r.jenis).bg, color: iconMeta(r.jenis).color }"
          >
            <component :is="iconMeta(r.jenis).icon" :size="16" />
          </div>
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
      <p v-else class="text-[13px] text-[var(--mut)] text-center py-4 mb-2">Belum ada pembayaran</p>

    </template>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'

import { ref, computed, onMounted } from 'vue'
import { Wallet, Home, ShieldAlert, Sparkles, Clock } from 'lucide-vue-next'
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

const stylePermintaan = {
  background:
    'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #FBBF24, #F59E0B 55%, #D97706)',
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
