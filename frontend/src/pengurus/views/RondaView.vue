<template>
  <div>
    <AppBackHeader title="Jadwal ronda" />

    <div class="rounded-[20px] border border-[var(--line)] overflow-hidden shadow-[var(--sh)] mb-4 bg-[var(--card)]">
      <div class="px-4 py-3 flex items-center justify-between" :style="headerStyle">
        <div class="min-w-0">
          <p class="text-[13px] font-semibold m-0" :class="headerTextClass">{{ labelKapan }}</p>
          <p v-if="malam && !malamLoading" class="text-[12px] m-0 mt-0.5 opacity-90" :class="headerTextClass">
            {{ formatHari(malam.tanggal) }} · {{ jamLabel(malam.jam_mulai) }}–{{ jamLabel(malam.jam_selesai) }}
          </p>
        </div>
        <button
          v-if="malam"
          type="button"
          class="w-9 h-9 rounded-full grid place-items-center shrink-0 active:scale-95 bg-white/20 text-white"
          aria-label="Kelola malam"
          @click="$router.push('/ronda/malam/' + malam.tanggal)"
        >
          <ChevronRight :size="20" />
        </button>
      </div>
      <div class="px-4 py-3">
        <p v-if="malamLoading" class="text-[13px] text-[var(--mut)] m-0">Memuat…</p>
        <template v-else-if="malam">
          <div v-for="k in (malam.keluarga || [])" :key="k.keluarga_id" class="flex items-center justify-between gap-3 py-1.5">
            <span class="text-[14px] font-semibold min-w-0 truncate">{{ k.nama || '—' }}</span>
            <span class="text-[13px] text-[var(--mut)] shrink-0">{{ k.alamat }}</span>
          </div>
          <p v-if="!(malam.keluarga || []).length" class="text-[13px] text-[var(--mut)] m-0">Belum ada keluarga bertugas</p>
        </template>
        <p v-else class="text-[13px] text-[var(--mut)] m-0">Belum ada jadwal ronda ke depan</p>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-1.5 text-center mb-5">
      <button
        v-for="a in aksi"
        :key="a.label"
        type="button"
        class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform"
        @click="onAksi(a)"
      >
        <span class="w-12 h-12 rounded-full grid place-items-center" :style="{ background: a.bg, color: a.color }">
          <component :is="a.icon" :size="20" />
        </span>
        <span class="text-[11px] font-semibold leading-tight">{{ a.label }}</span>
      </button>
    </div>

    <div class="flex items-center justify-between mb-2">
      <button type="button" class="w-10 h-10 rounded-full grid place-items-center bg-[var(--card2)] active:scale-95" aria-label="Bulan sebelumnya" @click="shiftMonth(-1)">
        <ChevronLeft :size="20" />
      </button>
      <p class="text-[15px] font-bold m-0">{{ bulanLabel }}</p>
      <button type="button" class="w-10 h-10 rounded-full grid place-items-center bg-[var(--card2)] active:scale-95" aria-label="Bulan berikutnya" @click="shiftMonth(1)">
        <ChevronRight :size="20" />
      </button>
    </div>

    <div class="grid grid-cols-7 gap-1 text-center text-[12px] mb-1">
      <span v-for="d in ['M', 'S', 'S', 'R', 'K', 'J', 'S']" :key="d" class="text-[var(--mut)] font-semibold py-1">{{ d }}</span>
    </div>
    <div class="grid grid-cols-7 gap-1 place-items-center">
      <button
        v-for="(day, i) in days"
        :key="i"
        type="button"
        class="w-9 h-9 rounded-full text-[13px] font-semibold grid place-items-center transition-transform active:scale-95"
        :class="dayClass(day)"
        :disabled="!day.n"
        @click="day.n && onPickDay(day)"
      >{{ day.n || '' }}</button>
    </div>

    <div v-if="selectedTanggal" class="mt-8 mb-6">
      <div class="flex items-start justify-between gap-2 mb-2">
        <div class="min-w-0">
          <p class="text-[13px] font-semibold m-0">{{ labelKapanTgl(selectedTanggal) }}</p>
          <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">
            {{ formatHari(selectedTanggal) }}
            <template v-if="selectedDetail"> · {{ jamLabel(selectedDetail.jam_mulai) }}–{{ jamLabel(selectedDetail.jam_selesai) }}</template>
          </p>
        </div>
        <button
          v-if="selectedDetail"
          type="button"
          class="w-9 h-9 rounded-full grid place-items-center shrink-0 active:scale-95 bg-[var(--card2)] text-[var(--text)]"
          aria-label="Kelola malam"
          @click="$router.push('/ronda/malam/' + selectedTanggal)"
        >
          <ChevronRight :size="20" />
        </button>
      </div>
      <p v-if="selectedLoading" class="text-[13px] text-[var(--mut)] py-2">Memuat…</p>
      <template v-else-if="selectedDetail">
        <div v-for="k in (selectedDetail.keluarga || [])" :key="k.keluarga_id" class="flex items-center justify-between gap-3 py-2">
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 bg-[var(--card2)] grid place-items-center text-[12px] font-bold text-[var(--gm)]">
              <img v-if="k.foto" :src="mediaUrl(k.foto)" alt="" class="w-full h-full object-cover" />
              <span v-else>{{ inisial(k.nama) }}</span>
            </div>
            <span class="text-[14px] font-semibold min-w-0 truncate">{{ k.nama || '—' }}</span>
          </div>
          <span class="text-[13px] text-[var(--mut)] shrink-0">{{ k.alamat }}</span>
        </div>
        <p v-if="!(selectedDetail.keluarga || []).length" class="text-[13px] text-[var(--mut)] m-0 py-1">Belum ada keluarga bertugas</p>
      </template>
      <div v-else class="py-3 flex flex-col items-center text-center">
        <p class="text-[13px] text-[var(--mut)] m-0 mb-3">Belum ada jadwal ronda di tanggal ini</p>
        <button type="button" class="min-h-[40px] px-5 rounded-full bg-[var(--g)] text-white text-[13px] font-bold active:scale-95 dark:bg-white dark:text-black" @click="goTambahKhusus">Tambah jadwal</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Calendar, List, Banknote, ChevronRight, ChevronLeft } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { kalenderRonda, malamTerdekat, detailMalam, listJadwalTetap, listJadwalKhusus } from '@shared/services/ronda.js'
import { mediaUrl } from '@shared/services/upload.js'

const router = useRouter()
const route = useRoute()
const malam = ref(null)
const malamLoading = ref(true)
const days = ref([])
const calYm = ref((() => {
  const t = new Date()
  return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}`
})())
const selectedTanggal = ref('')
const selectedDetail = ref(null)
const selectedLoading = ref(false)
const hariTetap = ref([false, false, false, false, false, false, false])
const khususSet = ref({})

const bulanLabel = computed(() => {
  const [y, m] = calYm.value.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

const aksi = [
  { label: 'Jadwal tetap', icon: Calendar, to: '/ronda/jadwal-tetap', bg: 'rgba(10,143,68,.18)', color: '#0A8F44' },
  { label: 'Jadwal khusus', icon: List, to: '/ronda/jadwal-khusus', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Denda', icon: Banknote, to: '/ronda/denda', bg: 'rgba(239,68,68,.18)', color: '#DC2626' },
]

function weekIndex(d) {
  const x = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const day = (x.getDay() + 6) % 7
  x.setDate(x.getDate() - day)
  x.setHours(0, 0, 0, 0)
  return x.getTime()
}

const labelKapan = computed(() => {
  if (malamLoading.value) return '…'
  if (!malam.value?.tanggal) return 'Ronda terdekat'
  return labelKapanTgl(malam.value.tanggal)
})

function labelKapanTgl(tanggal) {
  if (!tanggal) return 'Ronda'
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const tgl = new Date(tanggal + 'T00:00:00')
  if (tgl.getTime() === today.getTime()) return 'Malam ini'
  const wToday = weekIndex(today)
  const wTgl = weekIndex(tgl)
  const diff = Math.round((wTgl - wToday) / (7 * 86400000))
  if (diff === 0) return 'Minggu ini'
  if (diff === 1) return 'Minggu depan'
  if (diff > 1) return diff + ' minggu lagi'
  if (diff < 0) return 'Sudah lewat'
  return 'Ronda'
}

const headerStyle = computed(() => {
  if (!malam.value) return { background: 'var(--card2)' }
  if (malam.value.sumber === 'khusus') {
    return { background: 'linear-gradient(145deg, #3B82F6, #2563EB 55%, #1D4ED8)' }
  }
  return {
    background:
      'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)',
  }
})

const headerTextClass = computed(() => (malam.value ? 'text-white' : 'text-[var(--mut)]'))

function jamLabel(j) {
  if (!j) return '—'
  return String(j).slice(0, 5).replace(':', '.')
}

function formatHari(tgl) {
  if (!tgl) return ''
  return new Date(tgl + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' })
}

function inisial(nama) {
  if (!nama) return '?'
  const p = String(nama).trim().split(/\s+/)
  return ((p[0]?.[0] || '') + (p[1]?.[0] || '')).toUpperCase() || '?'
}

function dayClass(day) {
  if (!day.n) return 'text-transparent'
  const sel = day.date === selectedTanggal.value
  if (sel) {
    if (day.khusus) return 'bg-blue-500 text-white ring-2 ring-blue-300'
    if (day.ronda) return 'bg-[var(--g)] text-white ring-2 ring-[var(--gd)]'
    return 'bg-[var(--g)] text-white'
  }
  if (day.past && day.ronda) {
    if (day.khusus) return 'bg-blue-500/15 text-blue-600/80 opacity-70'
    return 'bg-[var(--gd)] text-[var(--gm)] opacity-55'
  }
  if (day.khusus) return 'bg-blue-500/20 text-blue-700'
  if (day.ronda) return 'bg-[var(--gd)] text-[var(--gm)]'
  return 'text-[var(--text)]'
}

function shiftMonth(delta) {
  const [y, m] = calYm.value.split('-').map(Number)
  const d = new Date(y, m - 1 + delta, 1)
  calYm.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
  loadKalender()
}

function todayStr() {
  const t0 = new Date()
  return [t0.getFullYear(), String(t0.getMonth() + 1).padStart(2, '0'), String(t0.getDate()).padStart(2, '0')].join('-')
}

function buildDays(kal) {
  const [y, m] = calYm.value.split('-').map(Number)
  const first = new Date(y, m - 1, 1)
  const startPad = first.getDay()
  const lastDate = new Date(y, m, 0).getDate()
  const byDate = {}
  for (const r of kal || []) byDate[r.tanggal] = r
  const today = todayStr()
  const out = []
  for (let i = 0; i < startPad; i++) out.push({ n: null })
  for (let n = 1; n <= lastDate; n++) {
    const date = `${calYm.value}-${String(n).padStart(2, '0')}`
    const r = byDate[date]
    const dariKhusus = !!khususSet.value[date]
    const khusus = r?.sumber === 'khusus' || (!r && dariKhusus)
    const ronda = !!r || khusus
    out.push({ n, date, past: date < today, ronda, khusus, tetap: !!r && r.sumber === 'tetap' && !khusus, projected: false })
  }
  days.value = out
}

async function onPickDay(day) {
  if (!day?.date) return
  selectedTanggal.value = day.date
  selectedDetail.value = null
  selectedLoading.value = true
  const res = await detailMalam(day.date)
  selectedLoading.value = false
  selectedDetail.value = res.ok && res.data ? res.data : null
}

function goTambahKhusus() {
  if (!selectedTanggal.value) return
  router.push({ path: '/ronda/jadwal-khusus', query: { tanggal: selectedTanggal.value } })
}

function onAksi(a) {
  if (a.to) router.push(a.to)
}

async function loadKalender() {
  const res = await kalenderRonda(calYm.value)
  buildDays(res.ok ? res.data : [])
}

async function loadHariTetap() {
  const j = await listJadwalTetap()
  if (j.ok) {
    const next = [false, false, false, false, false, false, false]
    for (const row of j.data || []) {
      const h = Number(row.hari)
      if (h >= 0 && h <= 6) next[h] = true
    }
    hariTetap.value = next
  }
}

async function loadKhususSet() {
  const res = await listJadwalKhusus()
  const s = {}
  if (res.ok && Array.isArray(res.data)) {
    for (const j of res.data) {
      if (j?.tanggal) s[j.tanggal] = true
    }
  }
  khususSet.value = s
}

async function reloadAll() {
  malamLoading.value = true
  await Promise.all([loadHariTetap(), loadKhususSet()])
  const [mRes] = await Promise.all([malamTerdekat(), loadKalender()])
  malamLoading.value = false
  malam.value = mRes.ok && mRes.data ? mRes.data : null
  if (selectedTanggal.value) {
    const res = await detailMalam(selectedTanggal.value)
    selectedDetail.value = res.ok ? res.data : null
  }
}

onMounted(reloadAll)
watch(() => route.fullPath, (p) => {
  if (p === '/ronda' || p === '/ronda/') reloadAll()
})
</script>
