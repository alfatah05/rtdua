<template>
  <div>
    <AppMainHeader />

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

    <div class="flex items-center justify-between mb-3">
      <button type="button" class="w-9 h-9 rounded-full grid place-items-center bg-[var(--card2)]" @click="shiftMonth(-1)">‹</button>
      <p class="text-[15px] font-bold m-0 capitalize">{{ labelBulan }}</p>
      <button type="button" class="w-9 h-9 rounded-full grid place-items-center bg-[var(--card2)]" @click="shiftMonth(1)">›</button>
    </div>
    <div class="grid grid-cols-7 gap-1 text-center mb-1">
      <span v-for="h in hariLabels" :key="h" class="text-[11px] font-semibold text-[var(--mut)] py-1">{{ h }}</span>
    </div>
    <div class="grid grid-cols-7 gap-1 mb-2">
      <button
        v-for="(day, idx) in days"
        :key="idx"
        type="button"
        class="aspect-square rounded-full text-[13px] font-semibold grid place-items-center transition"
        :class="dayClass(day)"
        :disabled="!day.n"
        @click="onPickDay(day)"
      >
        {{ day.n || '' }}
      </button>
    </div>
    <p class="text-[11px] text-[var(--mut)] mb-4 px-1">
      <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400 mr-1 align-middle"></span> Giliran keluarga Anda
      <span class="inline-block w-2.5 h-2.5 rounded-full bg-[var(--gd)] ml-3 mr-1 align-middle"></span> Ada ronda
    </p>

    <div v-if="selectedTanggal" class="mt-4 mb-6">
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
import { ref, computed, onMounted } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { kalenderRonda, malamTerdekat, detailMalam, absenWarga } from '@shared/services/ronda.js'
import { mediaUrl, uploadGambar } from '@shared/services/upload.js'
import { useAuth } from '@shared/composables/useAuth.js'

const { user } = useAuth()
const keluargaId = computed(() => Number(user.value?.keluarga_id || 0))

const malam = ref(null)
const malamLoading = ref(true)
const calYm = ref('')
const days = ref([])
const selectedTanggal = ref('')
const selectedDetail = ref(null)
const selectedLoading = ref(false)
const busy = ref(false)
const msg = ref('')
const msgOk = ref(true)
const fileAbsen = ref(null)

const hariLabels = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']
const today = (() => {
  const t = new Date()
  return [t.getFullYear(), String(t.getMonth() + 1).padStart(2, '0'), String(t.getDate()).padStart(2, '0')].join('-')
})()

const labelBulan = computed(() => {
  if (!calYm.value) return ''
  const [y, m] = calYm.value.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

const labelKapan = computed(() => {
  if (!malam.value?.tanggal) return 'Ronda terdekat Anda'
  return labelKapanTgl(malam.value.tanggal)
})

function labelKapanTgl(tanggal) {
  if (!tanggal) return ''
  if (tanggal === today) return 'Malam ini'
  const t0 = new Date(today + 'T00:00:00')
  const t1 = new Date(tanggal + 'T00:00:00')
  const diff = Math.round((t1 - t0) / 86400000)
  if (diff === 1) return 'Besok'
  if (diff > 1 && diff < 7) return `Dalam ${diff} hari`
  return formatHari(tanggal)
}

const headerStyle = computed(() => {
  if (!malam.value) return { background: 'var(--card2)' }
  if (malam.value.tanggal === today) {
    return {
      background: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)',
    }
  }
  return { background: 'var(--card2)' }
})
const headerTextClass = computed(() => (malam.value?.tanggal === today ? 'text-white' : 'text-[var(--text)]'))

const rowSaya = computed(() => {
  if (!malam.value || !keluargaId.value) return null
  return (malam.value.keluarga || []).find((k) => Number(k.keluarga_id) === keluargaId.value) || null
})
const statusSaya = computed(() => rowSaya.value?.status || '')

function dalamJendelaJam() {
  const m = malam.value
  if (!m?.jam_mulai || m.tanggal !== today) return false
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
    return 'bg-amber-400/35 text-amber-800'
  }
  if (sel) {
    if (day.ronda) return 'bg-[var(--g)] text-white ring-2 ring-[var(--gd)]'
    return 'bg-[var(--g)] text-white'
  }
  if (day.past && day.ronda) return 'bg-[var(--gd)] text-[var(--gm)] opacity-55'
  if (day.ronda) return 'bg-[var(--gd)] text-[var(--gm)]'
  return 'text-[var(--text)]'
}

function shiftMonth(delta) {
  const [y, m] = calYm.value.split('-').map(Number)
  const d = new Date(y, m - 1 + delta, 1)
  calYm.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
  loadKalender()
}

function buildDays(kal) {
  const [y, m] = calYm.value.split('-').map(Number)
  const first = new Date(y, m - 1, 1)
  const startPad = first.getDay()
  const lastDate = new Date(y, m, 0).getDate()
  const byDate = {}
  for (const r of kal || []) byDate[r.tanggal] = r
  const out = []
  for (let i = 0; i < startPad; i++) out.push({ n: null })
  for (let n = 1; n <= lastDate; n++) {
    const date = `${calYm.value}-${String(n).padStart(2, '0')}`
    const r = byDate[date]
    const ronda = !!r
    const ids = (r?.keluarga_ids || []).map(Number)
    const saya = keluargaId.value > 0 && ids.includes(keluargaId.value)
    out.push({ n, date, past: date < today, ronda, saya, khusus: r?.sumber === 'khusus' })
  }
  days.value = out
}

async function loadKalender() {
  const res = await kalenderRonda(calYm.value)
  buildDays(res.ok ? res.data : [])
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
    return
  }
  const res = await absenWarga(malam.value.id, up.data.path)
  busy.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Absen berhasil dicatat' : (res.error || 'Gagal absen')
  if (res.ok) {
    const m = await malamTerdekat()
    if (m.ok) malam.value = m.data
  }
}

onMounted(async () => {
  const now = new Date()
  calYm.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  malamLoading.value = true
  const [mRes] = await Promise.all([malamTerdekat(), loadKalender()])
  malamLoading.value = false
  malam.value = mRes.ok ? mRes.data : null
  const dToday = days.value.find((d) => d.date === today)
  if (dToday?.ronda) onPickDay(dToday)
})
</script>
