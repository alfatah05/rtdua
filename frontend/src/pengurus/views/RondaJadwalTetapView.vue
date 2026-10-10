<template>
  <div>
    <AppBackHeader title="Jadwal tetap" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Hari ronda</p>
      <div class="flex justify-between gap-1.5 mb-5">
        <button
          v-for="(label, h) in hariPendek"
          :key="h"
          type="button"
          class="w-11 h-11 rounded-full text-[12px] font-bold grid place-items-center shrink-0 transition-transform active:scale-95 border-0"
          :class="selected[h]
            ? 'bg-[var(--gd)] text-[var(--gm)]'
            : 'bg-[var(--card2)] text-[var(--mut)]'"
          :disabled="toggling"
          @click="toggleHari(h)"
        >
          {{ label }}
        </button>
      </div>

      <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Jam default</p>
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

      <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Periode</p>
      <select
        v-model="durasi"
        class="w-full min-h-[44px] px-4 rounded-full bg-[var(--search)] mb-3 outline-none appearance-none"
      >
        <option value="minggu">1 minggu (sisa minggu ini)</option>
        <option v-for="n in 12" :key="n" :value="String(n)">{{ n }} bulan</option>
      </select>

      <button
        type="button"
        class="w-full min-h-[44px] rounded-full bg-[var(--g)] text-white font-bold mb-2 disabled:opacity-50"
        :disabled="isiBusy || !adaHari"
        @click="jalankanBuatSlot"
      >
        {{ isiBusy ? 'Membuat…' : 'Buat jadwal' }}
      </button>
      <p v-if="msg" class="text-[13px] text-center mb-4" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>

      <div class="flex items-center justify-between gap-2 mb-3 mt-2">
        <p class="text-[15px] font-bold m-0">Jadwal ke depan</p>
        <select
          v-model="filterUnit"
          class="min-h-[36px] px-4 rounded-full bg-[var(--search)] text-[13px] font-semibold outline-none appearance-none"
          @change="pageOffset = 0"
        >
          <option value="minggu">Per minggu</option>
          <option value="bulan">Per bulan</option>
        </select>
      </div>

      <p v-if="listLoading" class="text-[13px] text-[var(--mut)] text-center py-4">Memuat jadwal…</p>
      <p v-else-if="!filteredMalam.length" class="text-[13px] text-[var(--mut)] text-center py-4">
        {{ emptyLabel }}
      </p>

      <div v-else class="space-y-3 mb-3">
        <div
          v-for="item in filteredMalam"
          :key="item.tanggal"
          class="rounded-[20px] border border-[var(--line)] overflow-hidden shadow-[var(--sh)] bg-[var(--card)]"
        >
          <div class="px-4 py-3 flex items-center justify-between" :style="cardHeaderStyle(item)">
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

      <div v-if="!listLoading && allMalam.length" class="flex items-center justify-between gap-2 mb-6">
        <button
          type="button"
          class="min-h-[40px] px-4 rounded-full bg-[var(--card2)] text-[13px] font-semibold disabled:opacity-40"
          :disabled="pageOffset <= 0"
          @click="pageOffset--"
        >
          Sebelumnya
        </button>
        <p class="text-[13px] font-semibold text-center m-0 min-w-0 truncate">{{ pageLabel }}</p>
        <button
          type="button"
          class="min-h-[40px] px-4 rounded-full bg-[var(--card2)] text-[13px] font-semibold disabled:opacity-40"
          :disabled="!canNext"
          @click="pageOffset++"
        >
          Berikutnya
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import {
  listJadwalTetap,
  simpanJadwalTetap,
  buatSlotKosong,
  kalenderRonda,
  detailMalam,
} from '@shared/services/ronda.js'

const hariPendek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']
const hariLabel = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']

const loading = ref(true)
const listLoading = ref(false)
const err = ref('')
const selected = ref([false, false, false, false, false, false, false])
const jamMulai = ref('21:00')
const jamSelesai = ref('00:00')
const durasi = ref('minggu')
const toggling = ref(false)
const isiBusy = ref(false)
const msg = ref('')
const msgOk = ref(true)
const filterUnit = ref('minggu')
const pageOffset = ref(0)
const allMalam = ref([])
const detailMap = ref({})

const adaHari = computed(() => selected.value.some(Boolean))

function startOfWeek(d) {
  const x = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const day = (x.getDay() + 6) % 7
  x.setDate(x.getDate() - day)
  x.setHours(0, 0, 0, 0)
  return x
}

function endOfWeek(d) {
  const s = startOfWeek(d)
  const e = new Date(s)
  e.setDate(e.getDate() + 6)
  return e
}

function weekIndex(d) {
  return startOfWeek(d).getTime()
}

const pageWindow = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  if (filterUnit.value === 'bulan') {
    const base = new Date(today.getFullYear(), today.getMonth() + pageOffset.value, 1)
    const start = new Date(base.getFullYear(), base.getMonth(), 1)
    const end = new Date(base.getFullYear(), base.getMonth() + 1, 0)
    start.setHours(0, 0, 0, 0)
    end.setHours(0, 0, 0, 0)
    return { start, end }
  }
  const base = startOfWeek(today)
  base.setDate(base.getDate() + pageOffset.value * 7)
  const start = new Date(base)
  const end = endOfWeek(base)
  return { start, end }
})

const pageLabel = computed(() => {
  const { start, end } = pageWindow.value
  if (filterUnit.value === 'bulan') {
    if (pageOffset.value === 0) return 'Bulan ini'
    if (pageOffset.value === 1) return 'Bulan depan'
    return start.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
  }
  if (pageOffset.value === 0) return 'Minggu ini'
  if (pageOffset.value === 1) return 'Minggu depan'
  const a = start.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
  const b = end.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  return `${a} – ${b}`
})

const filteredMalam = computed(() => {
  const { start, end } = pageWindow.value
  const s = start.getTime()
  const e = end.getTime()
  return allMalam.value.filter((item) => {
    const t = new Date(item.tanggal + 'T00:00:00').getTime()
    return t >= s && t <= e
  })
})

const canNext = computed(() => {
  if (!allMalam.value.length) return false
  const last = allMalam.value[allMalam.value.length - 1]
  if (!last?.tanggal) return false
  const lastT = new Date(last.tanggal + 'T00:00:00').getTime()
  return lastT > pageWindow.value.end.getTime()
})

const emptyLabel = computed(() => {
  if (!allMalam.value.length) {
    return 'Belum ada jadwal. Pilih hari + periode, lalu ketuk Buat jadwal.'
  }
  return `Tidak ada jadwal di ${pageLabel.value.toLowerCase()}.`
})

function labelKapan(tanggal) {
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
  return 'Ronda'
}

function cardHeaderStyle(item) {
  const nearest = allMalam.value[0]?.tanggal
  const isNearest = item.tanggal === nearest
  if (isNearest) {
    if (item.sumber === 'khusus') {
      return { background: 'linear-gradient(145deg, #3B82F6, #2563EB 55%, #1D4ED8)' }
    }
    return {
      background:
        'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)',
    }
  }
  return {
    background:
      'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.18), transparent 55%), linear-gradient(145deg, #9CA3AF, #6B7280 55%, #4B5563)',
  }
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
  })
}

async function loadDetailsFor(list) {
  const map = { ...detailMap.value }
  const need = list.filter((x) => !map[x.tanggal]).slice(0, 24)
  await Promise.all(
    need.map(async (item) => {
      const res = await detailMalam(item.tanggal)
      if (res.ok && res.data) map[item.tanggal] = res.data
      else map[item.tanggal] = { keluarga: [] }
    }),
  )
  detailMap.value = map
}

watch(filteredMalam, (list) => {
  if (list.length) loadDetailsFor(list)
})

async function loadKalenderList() {
  listLoading.value = true
  const today = new Date()
  const months = []
  for (let i = 0; i < 12; i++) {
    const d = new Date(today.getFullYear(), today.getMonth() + i, 1)
    months.push(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`)
  }
  const results = await Promise.all(months.map((b) => kalenderRonda(b)))
  const rows = []
  for (const r of results) {
    if (r.ok && Array.isArray(r.data)) rows.push(...r.data)
  }
  const todayStr = [
    today.getFullYear(),
    String(today.getMonth() + 1).padStart(2, '0'),
    String(today.getDate()).padStart(2, '0'),
  ].join('-')
  const seen = new Set()
  const uniq = []
  for (const x of rows) {
    if (!x?.tanggal || x.tanggal < todayStr) continue
    if (seen.has(x.tanggal)) continue
    seen.add(x.tanggal)
    uniq.push(x)
  }
  uniq.sort((a, b) => a.tanggal.localeCompare(b.tanggal))
  allMalam.value = uniq
  listLoading.value = false
}

async function load() {
  loading.value = true
  err.value = ''
  const j = await listJadwalTetap()
  loading.value = false
  if (!j.ok) {
    err.value = j.error || 'Gagal memuat jadwal tetap'
    return
  }
  const next = [false, false, false, false, false, false, false]
  let jamSet = false
  for (const row of j.data || []) {
    const h = Number(row.hari)
    if (h >= 0 && h <= 6) {
      next[h] = true
      if (!jamSet && row.jam_mulai) {
        jamMulai.value = String(row.jam_mulai).slice(0, 5)
        jamSelesai.value = String(row.jam_selesai || '00:00').slice(0, 5)
        jamSet = true
      }
    }
  }
  selected.value = next
  await loadKalenderList()
}

async function toggleHari(h) {
  if (toggling.value) return
  toggling.value = true
  msg.value = ''
  const on = !selected.value[h]
  const payload = on
    ? {
        hari: h,
        jam_mulai: jamMulai.value + ':00',
        jam_selesai: jamSelesai.value + ':00',
        keluarga_ids: [],
      }
    : { hari: h, hapus: true }

  const res = await simpanJadwalTetap(payload)
  toggling.value = false
  if (!res.ok) {
    msgOk.value = false
    msg.value = res.error || 'Gagal menyimpan hari'
    return
  }
  const copy = selected.value.slice()
  copy[h] = on
  selected.value = copy
}

async function jalankanBuatSlot() {
  if (!adaHari.value) {
    msgOk.value = false
    msg.value = 'Pilih minimal satu hari ronda'
    return
  }
  isiBusy.value = true
  msg.value = ''
  for (let h = 0; h < 7; h++) {
    if (!selected.value[h]) continue
    await simpanJadwalTetap({
      hari: h,
      jam_mulai: jamMulai.value + ':00',
      jam_selesai: jamSelesai.value + ':00',
      keluarga_ids: [],
    })
  }
  const hariList = []
  selected.value.forEach((on, h) => {
    if (on) hariList.push(h)
  })
  const res = await buatSlotKosong({
    durasi: durasi.value,
    hari: hariList,
  })
  isiBusy.value = false
  msgOk.value = !!res.ok
  if (res.ok) {
    const d = res.data || {}
    const parts = []
    if (d.dibuat) parts.push(`${d.dibuat} dibuat`)
    if (d.dihapus) parts.push(`${d.dihapus} dihapus`)
    if (d.dilewati) parts.push(`${d.dilewati} tetap`)
    const range = d.dari && d.sampai ? ` (${d.dari} s/d ${d.sampai})` : ''
    msg.value = parts.length ? `Selesai: ${parts.join(' · ')}${range}` : 'Jadwal sudah sesuai'
    detailMap.value = {}
    pageOffset.value = 0
    await loadKalenderList()
  } else {
    msg.value = res.error || 'Gagal buat jadwal'
  }
}

onMounted(load)
</script>
