<template>
  <div>
    <AppBackHeader title="Jadwal ronda" />

    <div class="rounded-[20px] border border-[var(--line)] overflow-hidden mb-4 bg-[var(--card)]">
      <div class="px-4 py-3 flex items-center justify-between" :style="headerStyle">
        <div class="min-w-0">
          <p class="text-[13px] font-semibold m-0" :class="headerTextClass">{{ labelKapan }}</p>
          <p v-if="malam && !malamLoading" class="text-[12px] m-0 mt-0.5 opacity-90" :class="headerTextClass">
            {{ formatHari(malam.tanggal) }} · {{ jamLabel(malam.jam_mulai) }}–{{ jamLabel(malam.jam_selesai) }}
          </p>
        </div>
      </div>
      <div class="px-4 py-3 space-y-1">
        <p v-if="malamLoading" class="text-[13px] text-[var(--mut)] m-0">Memuat…</p>
        <template v-else-if="malam">
          <div
            v-for="k in (malam.keluarga || [])"
            :key="k.keluarga_id"
            class="flex items-center justify-between gap-3 py-1"
          >
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 bg-[var(--card2)] grid place-items-center text-[12px] font-bold text-[var(--gm)]">
                <img v-if="k.foto" :src="mediaUrl(k.foto)" alt="" class="w-full h-full object-cover" />
                <span v-else>{{ inisial(k.nama) }}</span>
              </div>
              <span class="text-[14px] font-semibold min-w-0 truncate">
                {{ k.nama || '—' }}
                <span v-if="Number(k.keluarga_id) === keluargaId" class="text-[11px] font-bold text-amber-600"> · Anda</span>
              </span>
            </div>
            <span class="text-[13px] text-[var(--mut)] shrink-0 text-right">{{ k.alamat }}</span>
          </div>
          <p v-if="!(malam.keluarga || []).length" class="text-[13px] text-[var(--mut)] m-0">Belum ada keluarga bertugas</p>

          <div v-if="bisaAbsen" class="pt-2">
            <input ref="fileAbsen" type="file" accept="image/*" class="hidden" @change="onAbsen" />
            <button
              type="button"
              class="w-full min-h-[44px] rounded-full bg-[var(--g)] text-white font-bold active:scale-[0.98]"
              :disabled="busy"
              @click="fileAbsen?.click()"
            >
              {{ busy ? 'Mengirim…' : 'Absen dengan foto' }}
            </button>
          </div>
          <p v-else-if="statusSaya === 'hadir'" class="text-[13px] text-[var(--g)] font-semibold m-0 pt-1">Sudah absen</p>
          <p v-if="msg" class="text-[12px] m-0 pt-1" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
        </template>
        <p v-else class="text-[13px] text-[var(--mut)] m-0">Belum ada jadwal ronda untuk keluarga Anda</p>
      </div>
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
    <div class="grid grid-cols-7 gap-1 place-items-center" :class="calLoading ? 'opacity-60' : ''">
      <button
        v-for="(day, i) in days"
        :key="(day.date || 'x') + '-' + i"
        type="button"
        class="w-9 h-9 rounded-full text-[13px] font-semibold grid place-items-center transition-transform active:scale-95"
        :class="dayClass(day)"
        :disabled="!day.n"
        @click="day.n && onPickDay(day)"
      >{{ day.n || '' }}</button>
    </div>
    <p class="text-[11px] text-[var(--mut)] mt-2 mb-2 px-1">
      <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400 mr-1 align-middle"></span> Giliran Anda
      <span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-500/40 ml-3 mr-1 align-middle"></span> Khusus
      <span class="inline-block w-2.5 h-2.5 rounded-full bg-[var(--gd)] ml-3 mr-1 align-middle"></span> Ada ronda
    </p>

    <div v-if="selectedTanggal" class="mt-6 mb-6">
      <div class="mb-2">
        <p class="text-[13px] font-semibold m-0">{{ labelKapanTgl(selectedTanggal) }}</p>
        <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">
          {{ formatHari(selectedTanggal) }}
          <template v-if="selectedDetail"> · {{ jamLabel(selectedDetail.jam_mulai) }}–{{ jamLabel(selectedDetail.jam_selesai) }}</template>
        </p>
      </div>
      <p v-if="selectedLoading" class="text-[13px] text-[var(--mut)] py-2">Memuat…</p>
      <template v-else-if="selectedDetail">
        <div
          v-for="k in (selectedDetail.keluarga || [])"
          :key="k.keluarga_id"
          class="flex items-center justify-between gap-3 py-2"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 bg-[var(--card2)] grid place-items-center text-[12px] font-bold text-[var(--gm)]">
              <img v-if="k.foto" :src="mediaUrl(k.foto)" alt="" class="w-full h-full object-cover" />
              <span v-else>{{ inisial(k.nama) }}</span>
            </div>
            <span class="text-[14px] font-semibold min-w-0 truncate">
              {{ k.nama || '—' }}
              <span v-if="Number(k.keluarga_id) === keluargaId" class="text-[11px] font-bold text-amber-600"> · Anda</span>
            </span>
          </div>
          <span class="text-[13px] text-[var(--mut)] shrink-0 text-right">{{ k.alamat }}</span>
        </div>
        <p v-if="!(selectedDetail.keluarga || []).length" class="text-[13px] text-[var(--mut)] m-0 py-1">Belum ada keluarga bertugas</p>
      </template>
      <p v-else class="text-[13px] text-[var(--mut)] py-2">Belum ada jadwal ronda di tanggal ini</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import {
  kalenderRonda,
  malamTerdekat,
  detailMalam,
  absenWarga,
  listJadwalTetap,
  listJadwalKhusus,
  listTemplateCards,
} from '@shared/services/ronda.js'
import { mediaUrl, uploadGambar } from '@shared/services/upload.js'
import { useAuth } from '@shared/composables/useAuth.js'
import { useToast } from '@shared/composables/useToast.js'

const { user } = useAuth()
const route = useRoute()
const toast = useToast()
const keluargaId = computed(() => Number(user.value?.keluarga_id || 0))

const malam = ref(null)
const malamLoading = ref(true)
const days = ref([])
const calYm = ref(ymFromDate(new Date()))
const calLoading = ref(false)
const selectedTanggal = ref('')
const selectedDetail = ref(null)
const selectedLoading = ref(false)
const busy = ref(false)
const msg = ref('')
const msgOk = ref(true)
const fileAbsen = ref(null)

const kalCache = new Map()
const hariTetapDow = ref([])
const khususMap = ref({})
const templateIds = ref({})
const nMingguTemplate = ref(1)

let kalReqSeq = 0
let midnightTimer = null
let metaLoaded = false

function ymFromDate(d) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

function todayStr() {
  const t0 = new Date()
  return [t0.getFullYear(), String(t0.getMonth() + 1).padStart(2, '0'), String(t0.getDate()).padStart(2, '0')].join('-')
}

const bulanLabel = computed(() => {
  const [y, m] = calYm.value.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

function weekIndex(d) {
  const x = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const day = (x.getDay() + 6) % 7
  x.setDate(x.getDate() - day)
  x.setHours(0, 0, 0, 0)
  return x.getTime()
}

const labelKapan = computed(() => {
  if (malamLoading.value) return '…'
  if (!malam.value?.tanggal) return 'Ronda terdekat Anda'
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

const rowSaya = computed(() => {
  if (!malam.value || !keluargaId.value) return null
  return (malam.value.keluarga || []).find((k) => Number(k.keluarga_id) === keluargaId.value) || null
})
const statusSaya = computed(() => rowSaya.value?.status || '')

function dalamJendelaJam() {
  const m = malam.value
  if (!m?.jam_mulai || m.tanggal !== todayStr()) return false
  const now = new Date()
  const [hm, mm] = String(m.jam_mulai).slice(0, 5).split(':').map(Number)
  const [hs, ms] = String(m.jam_selesai || '00:00').slice(0, 5).split(':').map(Number)
  const mulai = new Date(now)
  mulai.setHours(hm, mm || 0, 0, 0)
  let selesai = new Date(now)
  selesai.setHours(hs, ms || 0, 0, 0)
  if (selesai <= mulai) selesai.setDate(selesai.getDate() + 1)
  const tol = 30 * 60 * 1000
  return now.getTime() >= mulai.getTime() - tol && now.getTime() <= selesai.getTime() + tol
}

const bisaAbsen = computed(() => {
  if (!malam.value || malam.value.terkunci) return false
  if (!rowSaya.value || statusSaya.value === 'hadir') return false
  return dalamJendelaJam()
})

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
  if (day.saya) {
    if (sel) return 'bg-amber-500 text-white ring-2 ring-amber-300'
    if (day.past) return 'bg-amber-400/25 text-amber-700/80 opacity-70'
    return 'bg-amber-400/40 text-amber-900'
  }
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

function mingguDariTanggal(y, m, day, n) {
  const nSafe = Math.max(1, n || 1)
  const dow = new Date(y, m - 1, day).getDay()
  let count = 0
  for (let d = 1; d <= day; d++) {
    if (new Date(y, m - 1, d).getDay() === dow) count++
  }
  if (count < 1) return 1
  return ((count - 1) % nSafe) + 1
}

function buildDays(kal) {
  const [y, m] = calYm.value.split('-').map(Number)
  const first = new Date(y, m - 1, 1)
  const startPad = first.getDay()
  const lastDate = new Date(y, m, 0).getDate()
  const byDate = {}
  for (const r of kal || []) {
    if (r?.tanggal) byDate[r.tanggal] = r
  }
  const today = todayStr()
  const kid = keluargaId.value
  const nMinggu = nMingguTemplate.value
  const out = []
  for (let i = 0; i < startPad; i++) out.push({ n: null })
  for (let n = 1; n <= lastDate; n++) {
    const date = `${calYm.value}-${String(n).padStart(2, '0')}`
    const r = byDate[date]
    const dow = new Date(y, m - 1, n).getDay()
    const khMeta = khususMap.value[date]
    const khusus = r?.sumber === 'khusus' || !!khMeta
    const tetapApi = r?.sumber === 'tetap'
    const tetapHari = hariTetapDow.value.includes(dow)
    const ronda = !!r || khusus || (!khusus && tetapHari)

    let ids = []
    if (Array.isArray(r?.keluarga_ids) && r.keluarga_ids.length) {
      ids = r.keluarga_ids.map(Number)
    } else if (khusus && Array.isArray(khMeta?.keluarga_ids)) {
      ids = khMeta.keluarga_ids.map(Number)
    } else if (!khusus && tetapHari) {
      const minggu = mingguDariTanggal(y, m, n, nMinggu)
      const key = `${minggu}-${dow}`
      ids = (templateIds.value[key] || []).map(Number)
    }

    const saya = kid > 0 && ids.includes(kid)
    out.push({
      n,
      date,
      past: date < today,
      ronda,
      saya,
      khusus,
      tetap: (tetapApi || tetapHari) && !khusus,
    })
  }
  days.value = out
}

async function loadMeta() {
  if (metaLoaded) return
  // skipUnauthorized: endpoint ini tadinya pengurus-only; jangan kick sesi warga
  const silent = { skipUnauthorized: true }
  const [tetapRes, khususRes, tplRes] = await Promise.all([
    listJadwalTetap(silent),
    listJadwalKhusus(silent),
    listTemplateCards(silent),
  ])

  if (tetapRes.ok && Array.isArray(tetapRes.data)) {
    hariTetapDow.value = tetapRes.data.map((t) => Number(t.hari)).filter((h) => h >= 0 && h <= 6)
  }

  const km = {}
  if (khususRes.ok && Array.isArray(khususRes.data)) {
    for (const k of khususRes.data) {
      if (!k?.tanggal) continue
      km[k.tanggal] = {
        keluarga_ids: (k.keluarga_ids || []).map(Number),
        jam_mulai: k.jam_mulai,
        jam_selesai: k.jam_selesai,
      }
    }
  }
  khususMap.value = km

  const map = {}
  let maxM = 1
  if (tplRes.ok && Array.isArray(tplRes.data)) {
    for (const c of tplRes.data) {
      const minggu = Number(c.minggu) || 1
      const hari = Number(c.hari)
      if (minggu > maxM) maxM = minggu
      const ids = (c.keluarga || []).map((x) => Number(x.keluarga_id)).filter((x) => x > 0)
      map[`${minggu}-${hari}`] = ids
    }
  }
  templateIds.value = map
  nMingguTemplate.value = maxM
  metaLoaded = true
}

async function loadKalender(ym, { force = false } = {}) {
  const target = ym || calYm.value
  const seq = ++kalReqSeq
  const isCurrent = () => target === calYm.value

  if (!force && kalCache.has(target) && isCurrent()) {
    buildDays(kalCache.get(target))
    calLoading.value = false
  } else if (isCurrent() && !kalCache.has(target)) {
    buildDays([])
    calLoading.value = true
  }

  try {
    const res = await kalenderRonda(target)
    const data = res.ok && Array.isArray(res.data) ? res.data : []
    kalCache.set(target, data)
    if (!hariTetapDow.value.length && data.length) {
      const set = new Set()
      for (const r of data) {
        if (!r?.tanggal || r.sumber === 'khusus') continue
        const d = new Date(r.tanggal + 'T12:00:00')
        if (!Number.isNaN(d.getTime())) set.add(d.getDay())
      }
      if (set.size) hariTetapDow.value = Array.from(set)
    }
    if (isCurrent() && seq === kalReqSeq) {
      buildDays(data)
      calLoading.value = false
    }
  } catch (e) {
    if (isCurrent() && seq === kalReqSeq) {
      calLoading.value = false
      if (kalCache.has(target)) buildDays(kalCache.get(target))
      else buildDays([])
    }
  }
}

function shiftMonth(delta) {
  const [y, m] = calYm.value.split('-').map(Number)
  const d = new Date(y, m - 1 + delta, 1)
  const next = ymFromDate(d)
  calYm.value = next
  selectedTanggal.value = ''
  selectedDetail.value = null
  if (kalCache.has(next)) {
    buildDays(kalCache.get(next))
    calLoading.value = false
  } else {
    buildDays([])
    calLoading.value = true
  }
  loadKalender(next, { force: true })
  prefetchAround(d)
}

function prefetchAround(centerDate) {
  for (const off of [-2, -1, 1, 2, 3, 4, 5, 6]) {
    const d = new Date(centerDate.getFullYear(), centerDate.getMonth() + off, 1)
    const ym = ymFromDate(d)
    if (!kalCache.has(ym)) loadKalender(ym, { force: false })
  }
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

async function onAbsen(e) {
  const file = e.target.files?.[0]
  e.target.value = ''
  if (!file || !malam.value?.id) return
  busy.value = true
  msg.value = ''
  const up = await uploadGambar(file, 'ronda', { maxSide: 1600 })
  if (!up.ok) {
    busy.value = false
    msgOk.value = false
    msg.value = up.error || 'Upload gagal'
    toast.error(msg.value)
    return
  }
  const res = await absenWarga(malam.value.id, up.data.path)
  busy.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Absen berhasil dicatat' : (res.error || 'Gagal absen')
  if (res.ok) {
    toast.success('Absen berhasil dicatat')
    const m = await malamTerdekat()
    if (m.ok) malam.value = m.data
  } else {
    toast.error(msg.value)
  }
}

function scheduleMidnightRefresh() {
  if (midnightTimer) clearTimeout(midnightTimer)
  const now = new Date()
  const next = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 2)
  midnightTimer = setTimeout(async () => {
    const ym = ymFromDate(new Date())
    if (calYm.value !== ym) {
      calYm.value = ym
      selectedTanggal.value = ''
      selectedDetail.value = null
    }
    kalCache.clear()
    metaLoaded = false
    await reloadAll()
    scheduleMidnightRefresh()
  }, Math.max(1000, next.getTime() - now.getTime()))
}

async function reloadAll() {
  malamLoading.value = true
  await loadMeta()
  const ym = calYm.value
  const [mRes] = await Promise.all([malamTerdekat(), loadKalender(ym, { force: true })])
  malamLoading.value = false
  malam.value = mRes.ok && mRes.data ? mRes.data : null
  if (kalCache.has(ym)) buildDays(kalCache.get(ym))
  else buildDays([])
  const [y, m] = ym.split('-').map(Number)
  prefetchAround(new Date(y, m - 1, 1))
  if (selectedTanggal.value) {
    const res = await detailMalam(selectedTanggal.value)
    selectedDetail.value = res.ok ? res.data : null
  } else {
    const t = todayStr()
    const dToday = days.value.find((d) => d.date === t)
    if (dToday?.ronda) onPickDay(dToday)
  }
}

onMounted(() => {
  calYm.value = ymFromDate(new Date())
  reloadAll()
  scheduleMidnightRefresh()
})

onUnmounted(() => {
  if (midnightTimer) clearTimeout(midnightTimer)
})

watch(() => route.fullPath, (p) => {
  if (p === '/ronda' || p === '/ronda/') {
    calYm.value = ymFromDate(new Date())
    metaLoaded = false
    reloadAll()
  }
})

watch(keluargaId, () => {
  if (kalCache.has(calYm.value)) buildDays(kalCache.get(calYm.value))
  else buildDays([])
})
</script>
