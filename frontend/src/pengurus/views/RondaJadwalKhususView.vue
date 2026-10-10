<template>
  <div>
    <AppBackHeader title="Jadwal khusus" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <div class="flex items-center justify-between mb-2">
        <button
          type="button"
          class="w-10 h-10 rounded-full grid place-items-center bg-[var(--card2)] active:scale-95"
          aria-label="Bulan sebelumnya"
          @click="shiftMonth(-1)"
        >
          <ChevronLeft :size="20" />
        </button>
        <p class="text-[15px] font-bold m-0">{{ bulanLabel }}</p>
        <button
          type="button"
          class="w-10 h-10 rounded-full grid place-items-center bg-[var(--card2)] active:scale-95"
          aria-label="Bulan berikutnya"
          @click="shiftMonth(1)"
        >
          <ChevronRight :size="20" />
        </button>
      </div>

      <div class="grid grid-cols-7 gap-1 text-center text-[12px] mb-1">
        <span v-for="d in ['M', 'S', 'S', 'R', 'K', 'J', 'S']" :key="d" class="text-[var(--mut)] font-semibold py-1">{{ d }}</span>
      </div>
      <div class="grid grid-cols-7 gap-1 mb-2">
        <button
          v-for="(day, i) in days"
          :key="i"
          type="button"
          class="aspect-square rounded-full text-[13px] font-semibold grid place-items-center relative transition-transform active:scale-95"
          :class="dayClass(day)"
          :disabled="!day.n || day.past"
          @click="day.n && !day.past && selectDate(day.date)"
        >
          {{ day.n || '' }}
          <span
            v-if="day.n && (day.tetap || day.khusus)"
            class="absolute bottom-0.5 w-1.5 h-1.5 rounded-full"
            :class="day.khusus ? 'bg-blue-500' : 'bg-[var(--g)]'"
          />
        </button>
      </div>
      <p class="text-[12px] text-[var(--mut)] mb-4">
        Hijau = jadwal tetap · Biru = jadwal khusus · Ketuk tanggal untuk memilih
      </p>
      <p v-if="selectedTanggal" class="text-[13px] font-semibold text-[var(--g)] mb-3">
        Terpilih: {{ formatHari(selectedTanggal) }}
      </p>

      <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Jam</p>
      <div class="flex gap-2 mb-4">
        <input
          v-model="jamMulai"
          type="time"
          class="flex-1 min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none"
        />
        <input
          v-model="jamSelesai"
          type="time"
          class="flex-1 min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none"
        />
      </div>

      <button
        type="button"
        class="w-full min-h-[44px] rounded-full bg-[var(--g)] text-white font-bold mb-2 disabled:opacity-50"
        :disabled="isiBusy || !selectedTanggal"
        @click="jalankanBuat"
      >
        {{ isiBusy ? 'Membuat…' : 'Buat jadwal' }}
      </button>
      <p v-if="msg" class="text-[13px] text-center mb-4" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>

      <p class="text-[15px] font-bold mb-3 mt-2">Semua jadwal khusus</p>
      <p v-if="listLoading" class="text-[13px] text-[var(--mut)] text-center py-4">Memuat…</p>
      <p v-else-if="!listMalam.length" class="text-[13px] text-[var(--mut)] text-center py-4">
        Belum ada jadwal khusus. Pilih tanggal di kalender, lalu ketuk Buat jadwal.
      </p>

      <div v-else class="space-y-3 mb-6">
        <div
          v-for="item in listMalam"
          :key="item.tanggal"
          class="rounded-[20px] border border-[var(--line)] overflow-hidden shadow-[var(--sh)] bg-[var(--card)]"
        >
          <div
            class="px-4 py-3 flex items-center justify-between"
            style="background: linear-gradient(145deg, #3B82F6, #2563EB 55%, #1D4ED8)"
          >
            <div class="min-w-0">
              <p class="text-[13px] font-semibold m-0 text-white">{{ labelKapan(item.tanggal) }}</p>
              <p class="text-[12px] m-0 mt-0.5 text-white/90">
                {{ formatHari(item.tanggal) }} · {{ jamLabel(item.jam_mulai) }}–{{ jamLabel(item.jam_selesai) }}
              </p>
            </div>
            <button
              type="button"
              class="w-9 h-9 rounded-full grid place-items-center shrink-0 active:scale-95 bg-white/20 text-white"
              aria-label="Kelola malam"
              @click="$router.push('/ronda/malam/' + item.tanggal)"
            >
              <ChevronRight :size="20" />
            </button>
          </div>
          <div class="px-4 py-3">
            <template v-if="detailMap[item.tanggal]?.keluarga?.length">
              <div
                v-for="k in detailMap[item.tanggal].keluarga"
                :key="k.keluarga_id"
                class="flex items-center justify-between gap-3 py-1.5"
              >
                <span class="text-[14px] font-semibold min-w-0 truncate">{{ k.nama || '—' }}</span>
                <span class="text-[13px] text-[var(--mut)] shrink-0">{{ k.alamat }}</span>
              </div>
            </template>
            <p v-else class="text-[13px] text-[var(--mut)] m-0">
              {{ detailMap[item.tanggal] ? 'Belum ada keluarga bertugas' : '…' }}
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import {
  listJadwalTetap,
  listJadwalKhusus,
  simpanJadwalKhusus,
  kalenderRonda,
  detailMalam,
} from '@shared/services/ronda.js'

const loading = ref(true)
const listLoading = ref(false)
const err = ref('')
const jamMulai = ref('21:00')
const jamSelesai = ref('00:00')
const selectedTanggal = ref('')
const isiBusy = ref(false)
const msg = ref('')
const msgOk = ref(true)

const calYm = ref(new Date().toISOString().slice(0, 7))
const days = ref([])
const hariTetap = ref([false, false, false, false, false, false, false])
const kalMap = ref({})
const listMalam = ref([])
const detailMap = ref({})

const bulanLabel = computed(() => {
  const [y, m] = calYm.value.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

function todayStr() {
  const t = new Date()
  return [
    t.getFullYear(),
    String(t.getMonth() + 1).padStart(2, '0'),
    String(t.getDate()).padStart(2, '0'),
  ].join('-')
}

function shiftMonth(delta) {
  const [y, m] = calYm.value.split('-').map(Number)
  const d = new Date(y, m - 1 + delta, 1)
  calYm.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
  buildDays()
  loadKalenderMonth()
}

function dayClass(day) {
  if (!day.n) return 'text-transparent'
  if (day.past) return 'text-[var(--mut)] opacity-40'
  const sel = day.date === selectedTanggal.value
  if (sel) {
    if (day.khusus) return 'bg-blue-500 text-white ring-2 ring-blue-300'
    return 'bg-[var(--g)] text-white ring-2 ring-[var(--gd)]'
  }
  if (day.khusus) return 'bg-blue-500/20 text-blue-700'
  if (day.tetap) return 'bg-[var(--gd)] text-[var(--gm)]'
  return 'text-[var(--text)]'
}

function selectDate(date) {
  selectedTanggal.value = date
  msg.value = ''
}

const khususSet = computed(() => {
  const s = {}
  for (const item of listMalam.value) {
    if (item?.tanggal) s[item.tanggal] = true
  }
  return s
})

function buildDays() {
  const [y, m] = calYm.value.split('-').map(Number)
  const first = new Date(y, m - 1, 1)
  const startPad = first.getDay()
  const lastDate = new Date(y, m, 0).getDate()
  const today = todayStr()
  const out = []
  for (let i = 0; i < startPad; i++) out.push({ n: null })
  for (let n = 1; n <= lastDate; n++) {
    const date = `${calYm.value}-${String(n).padStart(2, '0')}`
    const r = kalMap.value[date]
    const dow = new Date(y, m - 1, n).getDay()
    const khusus = r?.sumber === 'khusus' || !!khususSet.value[date]
    const tetap = !khusus && (r?.sumber === 'tetap' || (!!hariTetap.value[dow] && date >= today))
    out.push({
      n,
      date,
      past: date < today,
      khusus,
      tetap,
    })
  }
  days.value = out
}

watch(khususSet, () => buildDays())

function weekIndex(d) {
  const x = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const day = (x.getDay() + 6) % 7
  x.setDate(x.getDate() - day)
  x.setHours(0, 0, 0, 0)
  return x.getTime()
}

function labelKapan(tanggal) {
  if (!tanggal) return 'Khusus'
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
  return 'Jadwal khusus'
}

function jamLabel(j) {
  if (!j) return '—'
  return String(j).slice(0, 5).replace(':', '.')
}

function formatHari(tgl) {
  if (!tgl) return ''
  return new Date(tgl + 'T00:00:00').toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

async function loadKalenderMonth() {
  const res = await kalenderRonda(calYm.value)
  const map = { ...kalMap.value }
  Object.keys(map).forEach((k) => {
    if (k.startsWith(calYm.value)) delete map[k]
  })
  if (res.ok && Array.isArray(res.data)) {
    for (const r of res.data) {
      if (r?.tanggal) map[r.tanggal] = r
    }
  }
  kalMap.value = map
  buildDays()
}

async function loadDetailsFor(list) {
  const map = { ...detailMap.value }
  const need = list.filter((x) => !map[x.tanggal]).slice(0, 40)
  await Promise.all(
    need.map(async (item) => {
      const res = await detailMalam(item.tanggal)
      if (res.ok && res.data) map[item.tanggal] = res.data
      else map[item.tanggal] = { keluarga: [] }
    }),
  )
  detailMap.value = map
}

watch(listMalam, (list) => {
  if (list.length) loadDetailsFor(list)
})

async function loadList() {
  listLoading.value = true
  const today = new Date()
  const months = []
  for (let i = 0; i < 12; i++) {
    const d = new Date(today.getFullYear(), today.getMonth() + i, 1)
    months.push(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`)
  }
  const [khususRes, ...kalResults] = await Promise.all([
    listJadwalKhusus(),
    ...months.map((b) => kalenderRonda(b)),
  ])

  const byTgl = {}
  for (const r of kalResults) {
    if (r.ok && Array.isArray(r.data)) {
      for (const row of r.data) {
        if (row?.sumber === 'khusus' && row.tanggal) byTgl[row.tanggal] = row
      }
    }
  }
  if (khususRes.ok && Array.isArray(khususRes.data)) {
    for (const j of khususRes.data) {
      if (!j?.tanggal) continue
      if (!byTgl[j.tanggal]) {
        byTgl[j.tanggal] = {
          tanggal: j.tanggal,
          jam_mulai: j.jam_mulai,
          jam_selesai: j.jam_selesai,
          sumber: 'khusus',
        }
      }
    }
  }

  const t0 = todayStr()
  listMalam.value = Object.values(byTgl)
    .filter((x) => x.tanggal >= t0)
    .sort((a, b) => a.tanggal.localeCompare(b.tanggal))
  listLoading.value = false
}

async function load() {
  loading.value = true
  err.value = ''
  const j = await listJadwalTetap()
  if (j.ok) {
    const next = [false, false, false, false, false, false, false]
    for (const row of j.data || []) {
      const h = Number(row.hari)
      if (h >= 0 && h <= 6) next[h] = true
    }
    hariTetap.value = next
  }
  await Promise.all([loadKalenderMonth(), loadList()])
  loading.value = false
}

async function jalankanBuat() {
  if (!selectedTanggal.value) {
    msgOk.value = false
    msg.value = 'Pilih tanggal di kalender dulu'
    return
  }
  isiBusy.value = true
  msg.value = ''
  const res = await simpanJadwalKhusus({
    tanggal: selectedTanggal.value,
    jam_mulai: jamMulai.value + ':00',
    jam_selesai: jamSelesai.value + ':00',
    keluarga_ids: [],
  })
  isiBusy.value = false
  msgOk.value = !!res.ok
  if (res.ok) {
    msg.value = `Jadwal khusus ${formatHari(selectedTanggal.value)} dibuat`
    detailMap.value = {}
    await Promise.all([loadKalenderMonth(), loadList()])
  } else {
    msg.value = res.error || 'Gagal buat jadwal khusus'
  }
}

onMounted(load)
</script>
