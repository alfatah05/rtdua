<template>
  <div>
    <AppBackHeader title="Absen ronda" />

    <p class="text-[13px] text-[var(--mut)] m-0 mb-4 leading-snug">
      Pilih tanggal, lalu absenkan keluarga yang hadir. Hanya pengurus yang mencatat absen.
    </p>

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
      </div>

      <AppPageSpinner v-if="selectedLoading" class="!py-6" />
      <template v-else-if="selectedDetail">
        <div
          v-for="k in (selectedDetail.keluarga || [])"
          :key="k.keluarga_id"
          class="flex items-center justify-between gap-3 py-2.5 border-b border-[var(--line)] last:border-0"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 bg-[var(--card2)] grid place-items-center text-[12px] font-bold text-[var(--gm)]">
              <img v-if="k.foto" :src="mediaUrl(k.foto)" alt="" class="w-full h-full object-cover" />
              <span v-else>{{ inisial(k.nama) }}</span>
            </div>
            <div class="min-w-0">
              <p class="text-[14px] font-semibold m-0 truncate">{{ k.nama || '—' }}</p>
              <p class="text-[12px] m-0 truncate" :class="k.status === 'hadir' ? 'text-[var(--g)]' : 'text-[var(--mut)]'">
                {{ k.alamat || '' }}
                <span v-if="k.status === 'hadir'"> · Hadir</span>
                <span v-else> · Belum absen</span>
              </p>
            </div>
          </div>
          <button
            v-if="k.status === 'hadir' && k.absen?.id && !selectedDetail.terkunci"
            type="button"
            class="shrink-0 min-h-[36px] px-3 rounded-full text-[12px] font-bold text-red-600 bg-red-500/10 active:scale-95 disabled:opacity-50"
            :disabled="busy"
            @click="onBatal(k)"
          >Batalkan</button>
          <button
            v-else-if="k.status !== 'hadir' && !selectedDetail.terkunci"
            type="button"
            class="shrink-0 min-h-[36px] px-3 rounded-full text-[12px] font-bold text-white bg-[var(--g)] active:scale-95 disabled:opacity-50"
            :disabled="busy || !selectedDetail.id"
            @click="onAbsen(k)"
          >Absen</button>
          <span v-else-if="selectedDetail.terkunci" class="text-[11px] text-[var(--mut)] shrink-0">Terkunci</span>
        </div>
        <p v-if="!(selectedDetail.keluarga || []).length" class="text-[13px] text-[var(--mut)] m-0 py-1">Belum ada keluarga bertugas</p>
      </template>
      <p v-else class="text-[13px] text-[var(--mut)] m-0 py-3">Belum ada jadwal ronda di tanggal ini</p>

      <p v-if="msg" class="text-[13px] text-center mt-3 m-0" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import {
  kalenderRonda,
  detailMalam,
  listJadwalTetap,
  listJadwalKhusus,
  absenManual,
  batalkanAbsen,
} from '@shared/services/ronda.js'
import { mediaUrl } from '@shared/services/upload.js'

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
const busy = ref(false)
const msg = ref('')
const msgOk = ref(true)

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
    const dow = new Date(y, m - 1, n).getDay()
    const dariTetap = !!hariTetap.value[dow]
    const ronda = !!r || khusus || (!khusus && dariTetap)
    out.push({ n, date, past: date < today, ronda, khusus, tetap: (r?.sumber === 'tetap' || dariTetap) && !khusus, projected: false })
  }
  days.value = out
}

async function onPickDay(day) {
  if (!day?.date) return
  selectedTanggal.value = day.date
  selectedDetail.value = null
  msg.value = ''
  selectedLoading.value = true
  const res = await detailMalam(day.date)
  selectedLoading.value = false
  selectedDetail.value = res.ok && res.data ? res.data : null
}

async function loadKalender() {
  const res = await kalenderRonda(calYm.value)
  buildDays(res.ok && Array.isArray(res.data) ? res.data : [])
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

async function onAbsen(k) {
  if (!selectedDetail.value?.id) {
    msgOk.value = false
    msg.value = 'Malam ronda belum terbentuk. Buka kelola malam dulu jika perlu.'
    return
  }
  busy.value = true
  msg.value = ''
  const res = await absenManual(selectedDetail.value.id, k.keluarga_id)
  busy.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Absen dicatat' : (res.error || 'Gagal absen')
  if (res.ok) {
    const r = await detailMalam(selectedTanggal.value)
    selectedDetail.value = r.ok ? r.data : selectedDetail.value
  }
}

async function onBatal(k) {
  if (!k.absen?.id) return
  if (!confirm('Batalkan absen keluarga ini?')) return
  busy.value = true
  msg.value = ''
  const res = await batalkanAbsen(k.absen.id)
  busy.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Absen dibatalkan' : (res.error || 'Gagal')
  if (res.ok) {
    const r = await detailMalam(selectedTanggal.value)
    selectedDetail.value = r.ok ? r.data : selectedDetail.value
  }
}

async function reloadAll() {
  await Promise.all([loadHariTetap(), loadKhususSet()])
  await loadKalender()
  // default pilih hari ini bila ada ronda
  const today = todayStr()
  const hit = days.value.find((d) => d.date === today && d.ronda)
  if (hit) await onPickDay(hit)
}

onMounted(reloadAll)
</script>
