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
        <button
          v-for="p in permintaanMenunggu"
          :key="'pm-' + p.id"
          type="button"
          class="w-full text-left relative overflow-hidden touch-manipulation rounded-[1.125rem] active:scale-[0.98] transition"
          style="background: #3B82F6"
          @click="bukaBuktiPermintaan(p)"
        >
          <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-16">
            <div class="w-11 h-11 rounded-[0.875rem] grid place-items-center shrink-0 bg-white/20 text-white">
              <Clock :size="20" />
            </div>
            <div class="flex-1 min-w-0 py-2.5">
              <p class="text-sm font-bold m-0 leading-tight text-white">Menunggu konfirmasi</p>
              <p class="text-xs font-semibold m-0 mt-0.5 leading-tight text-white/85 tabular-nums truncate">
                {{ labelPermintaan(p) }} · {{ rp(p.nominal_diajukan ?? p.nominal) }}
              </p>
            </div>
            <ChevronRight :size="16" class="shrink-0 text-white/70" />
          </div>
        </button>
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

      <div
        class="flex p-1 rounded-full bg-[var(--card2)] mb-3"
        role="tablist"
        aria-label="Riwayat dan status iuran"
      >
        <button
          type="button"
          role="tab"
          class="flex-1 min-h-[40px] rounded-full text-[13px] font-semibold transition"
          :class="tab === 'riwayat'
            ? 'bg-[var(--card)] text-[var(--text)] shadow-[var(--sh)]'
            : 'text-[var(--mut)]'"
          :aria-selected="tab === 'riwayat'"
          @click="tab = 'riwayat'"
        >
          Riwayat Pembayaran
        </button>
        <button
          type="button"
          role="tab"
          class="flex-1 min-h-[40px] rounded-full text-[13px] font-semibold transition"
          :class="tab === 'status'
            ? 'bg-[var(--card)] text-[var(--text)] shadow-[var(--sh)]'
            : 'text-[var(--mut)]'"
          :aria-selected="tab === 'status'"
          @click="onTabStatus"
        >
          Status Iuran Warga
        </button>
      </div>

      <template v-if="tab === 'riwayat'">
        <div v-if="riwayat.length" class="mb-2 px-1">
          <button
            v-for="r in riwayat"
            :key="'rw-' + (r.pembayaran_id || r.id || r.tanggal) + '-' + (r.tagihan_id || '')"
            type="button"
            class="w-full flex items-center gap-3 py-2.5 text-left active:opacity-80 transition-opacity"
            @click="bukaBuktiRiwayat(r)"
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
            <ChevronRight :size="16" class="text-[var(--mut)] shrink-0 opacity-60" />
          </button>
        </div>
        <p v-else class="text-[13px] text-[var(--mut)] text-center py-4 m-0">Belum ada riwayat</p>
      </template>

      <template v-else>
        <div v-if="statusLoading" class="px-1 animate-pulse" aria-hidden="true">
          <div v-for="n in 6" :key="n" class="flex items-center gap-3 py-3">
            <div class="w-11 h-11 rounded-full bg-[var(--card2)] shrink-0" />
            <div class="flex-1 min-w-0 space-y-2">
              <div class="h-3.5 rounded-full bg-[var(--card2)] w-[55%]" />
              <div class="h-3 rounded-full bg-[var(--card2)] w-[30%]" />
            </div>
            <div class="h-6 w-16 rounded-full bg-[var(--card2)] shrink-0" />
          </div>
        </div>
        <p v-else-if="statusErr" class="text-[13px] text-red-600 text-center py-4">{{ statusErr }}</p>
        <div v-else class="px-1">
          <div
            v-for="k in statusList"
            :key="k.id"
            class="w-full flex items-center gap-3 py-3"
          >
            <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0 overflow-hidden">
              <img
                v-if="k.fotoUrl"
                :src="k.fotoUrl"
                alt=""
                class="w-full h-full object-cover"
                @error="k.fotoUrl = ''"
              />
              <template v-else>{{ k.inisial }}</template>
            </div>
            <div class="min-w-0 flex-1 text-left">
              <p class="font-bold m-0 text-[15px]">{{ k.nama }}</p>
              <p class="text-[13px] text-[var(--mut)] m-0">{{ k.alamat }}</p>
            </div>
            <span
              class="shrink-0 text-[11px] font-bold px-2.5 py-1 rounded-full"
              :class="k.status === 'lunas'
                ? 'bg-[var(--ok)] text-[var(--g)]'
                : 'bg-amber-500/15 text-amber-700 dark:text-amber-400'"
            >
              {{ k.status === 'lunas' ? 'Lunas' : 'Belum Lunas' }}
            </span>
          </div>
          <p v-if="!statusList.length" class="text-[13px] text-[var(--mut)] text-center py-4">Tidak ada data</p>
        </div>
      </template>
    </template>

    <div
      v-if="!loading && !err"
      class="fixed bottom-0 left-0 right-0 z-30 pointer-events-none"
    >
      <div class="max-w-[560px] mx-auto px-4 pt-2 pb-[max(12px,env(safe-area-inset-bottom,0px))]">
        <button
          type="button"
          class="pointer-events-auto w-full min-h-[48px] rounded-full font-bold transition bg-[var(--btn)] text-[var(--btn-fg)] active:scale-[0.98]"
          @click="onBayarClick"
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
import { useRouter } from 'vue-router'
import { Wallet, Home, ShieldAlert, Sparkles, Clock, ChevronDown, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalRingkasan, portalStatusPermintaan, portalStatusIuranWarga } from '@shared/services/keuangan.js'
import { mediaUrl } from '@shared/services/upload.js'
import { waitImages } from '@shared/utils/waitImages.js'
import { useToast } from '@shared/composables/useToast.js'

const RECEIPT_KEY = 'rtdua_bayar_bukti'
const router = useRouter()
const { show } = useToast()

const loading = ref(true)
const err = ref('')
const total = ref(0)
const tagihan = ref([])
const riwayat = ref([])
const permintaan = ref([])
const komponenKas = ref([])
const totalKasBulanan = ref(0)
const kasOpen = ref({})
const tab = ref('riwayat')

const statusLoading = ref(false)
const statusErr = ref('')
const statusList = ref([])
const statusLoaded = ref(false)

function bisaExpandKas(t) {
  return t?.jenis === 'kas' && (komponenKas.value || []).length > 0
}
function isKasOpen(id) {
  return !!kasOpen.value[id]
}
function toggleKas(id) {
  kasOpen.value = { ...kasOpen.value, [id]: !kasOpen.value[id] }
}

function inisial(nama) {
  return String(nama || '?').split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()
}

async function onTabStatus() {
  tab.value = 'status'
  if (statusLoaded.value || statusLoading.value) return
  statusLoading.value = true
  statusErr.value = ''
  const res = await portalStatusIuranWarga()
  if (!res.ok) {
    statusErr.value = res.error || 'Gagal memuat status'
    statusLoading.value = false
    return
  }
  const rows = (res.data || []).map((k) => ({
    id: k.id,
    nama: k.nama || '—',
    alamat: k.alamat || '',
    status: k.status === 'lunas' ? 'lunas' : 'belum_lunas',
    inisial: inisial(k.nama),
    fotoUrl: k.foto ? mediaUrl(k.foto) : '',
  }))
  statusList.value = rows
  statusLoaded.value = true
  await waitImages(rows.map((r) => r.fotoUrl).filter(Boolean))
  statusLoading.value = false
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

function bukaBukti(payload) {
  try {
    sessionStorage.setItem(RECEIPT_KEY, JSON.stringify(payload))
  } catch (e) {}
  router.push('/bayar/bukti')
}

function bukaBuktiPermintaan(p) {
  const rek = p.rekening || (p.bank_pengirim
    ? { nama: p.bank_pengirim, jenis: 'bank', nomor: '', atas_nama: '' }
    : null)
  bukaBukti({
    id: p.id,
    status: 'menunggu',
    nominal: Number(p.nominal_diajukan ?? p.nominal ?? 0),
    nama_pengirim: p.nama_pengirim || '',
    bank_pengirim: p.bank_pengirim || '',
    diajukan_pada: p.diajukan_pada || null,
    metode: 'transfer',
    metode_label: rek?.nama || p.bank_pengirim || 'Transfer',
    rekening: rek,
    items: [],
  })
}

function bukaBuktiRiwayat(r) {
  const pid = r.pembayaran_id || r.id || null
  const siblings = (riwayat.value || []).filter(
    (x) => Number(x.pembayaran_id || x.id || 0) === Number(pid || 0)
  )
  const lines = (siblings.length ? siblings : [r]).map((x) => {
    const lab = labelRiwayat(x)
    const jml = Number(x.jumlah ?? x.nominal ?? 0)
    return { id: x.tagihan_id || null, label: lab, sisa: jml, jumlah: jml }
  })
  const totalBayar = Number(
    r.nominal_bayar
      ?? lines.reduce((s, it) => s + Number(it.jumlah || 0), 0)
      ?? r.jumlah
      ?? 0
  )
  let rek = r.rekening || null
  if (!rek) {
    const bank = r.bank_pengirim || ''
    const metodeRek = r.metode_rekening || ''
    if (bank || metodeRek) {
      const parts = String(metodeRek).split(':')
      rek = {
        nama: bank || parts[1] || metodeRek || '',
        jenis: (parts[0] || 'bank').toLowerCase(),
        nomor: '',
        atas_nama: '',
      }
    }
  }
  bukaBukti({
    id: pid,
    status: 'dikonfirmasi',
    nominal: totalBayar,
    nama_pengirim: r.nama_pengirim || '',
    bank_pengirim: r.bank_pengirim || '',
    diajukan_pada: r.tanggal || null,
    tanggal: r.tanggal || null,
    metode: r.metode || '',
    metode_label: labelMetode(r.metode) || rek?.nama || r.bank_pengirim || '',
    rekening: rek,
    items: lines,
  })
}

const tagihanTerbuka = computed(() =>
  (tagihan.value || []).filter((t) => Number(t.sisa ?? t.nominal ?? 0) > 0)
)
const totalTerbuka = computed(() =>
  tagihanTerbuka.value.reduce((s, t) => s + Number(t.sisa ?? t.nominal ?? 0), 0)
)
const permintaanMenunggu = computed(() =>
  (permintaan.value || []).filter((p) => p.status === 'menunggu')
)

function onBayarClick() {
  router.push('/bayar')
}

function parseTagihanIds(raw) {
  if (!raw) return []
  if (Array.isArray(raw)) return raw.map(Number).filter((n) => n > 0)
  try {
    const j = typeof raw === 'string' ? JSON.parse(raw) : raw
    if (Array.isArray(j)) return j.map(Number).filter((n) => n > 0)
  } catch (_) {}
  return String(raw)
    .split(/[,\s]+/)
    .map(Number)
    .filter((n) => n > 0)
}

function labelPermintaan(p) {
  const ids = parseTagihanIds(p?.tagihan_ids)
  if (ids.length) {
    const labels = ids
      .map((id) => {
        const t = (tagihan.value || []).find((x) => Number(x.id) === id)
        return t ? labelTagihan(t) : null
      })
      .filter(Boolean)
    if (labels.length === 1) return labels[0]
    if (labels.length > 1) return labels[0] + ' +' + (labels.length - 1)
  }
  const rek = p?.rekening?.nama || p?.bank_pengirim || p?.metode_rekening
  return rek || 'Transfer'
}

const heroStatus = computed(() => {
  if (loading.value) return 'loading'
  if (total.value > 0) return 'pending'
  if (total.value < 0) return 'lebih'
  return 'lunas'
})
const heroTitle = computed(() => {
  if (heroStatus.value === 'pending') return 'Iuran yang belum di bayar'
  if (heroStatus.value === 'lunas') return 'Iuran yang belum di bayar'
  if (heroStatus.value === 'lebih') return 'Kelebihan bayar'
  return 'Iuran'
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

const styleTagihan = { background: '#F59E0B' }
const styleLunas = { background: '#22B863' }
const styleLebih = { background: '#3B82F6' }
const styleLoading = { background: 'var(--card2)' }

const heroStyle = computed(() => {
  if (heroStatus.value === 'pending') return styleTagihan
  if (heroStatus.value === 'lunas') return styleLunas
  if (heroStatus.value === 'lebih') return styleLebih
  return styleLoading
})

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
      nama_pengirim: b.nama_pengirim || '',
      bank_pengirim: b.bank_pengirim || '',
      metode_rekening: b.metode_rekening || '',
      rekening: b.rekening || null,
      nominal_bayar: b.nominal,
    }))
  }
  if (pm.ok) {
    permintaan.value = Array.isArray(pm.data) ? pm.data : (pm.data?.items || [])
  }
})
</script>
