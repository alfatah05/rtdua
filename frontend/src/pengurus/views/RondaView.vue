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

    <div class="grid grid-cols-3 gap-2 text-center mb-5">
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
    <p v-if="aksiMsg" class="text-[13px] text-center mb-3" :class="aksiOk ? 'text-[var(--g)]' : 'text-red-600'">{{ aksiMsg }}</p>

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
    <div class="grid grid-cols-7 gap-1 place-items-center mb-4">
      <button
        v-for="(day, i) in days"
        :key="i"
        type="button"
        class="w-9 h-9 rounded-full text-[13px] font-semibold grid place-items-center transition-transform active:scale-95"
        :class="dayClass(day)"
        :disabled="!day.n"
        @click="day.n && onPickDay(day)"
      >
        {{ day.n || '' }}
      </button>
    </div>

    <div v-if="selectedTanggal" class="mb-6">
      <div class="flex items-start justify-between gap-2 mb-2">
        <div class="min-w-0">
          <p class="text-[13px] font-semibold m-0">{{ labelKapanTgl(selectedTanggal) }}</p>
          <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">
            {{ formatHari(selectedTanggal) }}
            <template v-if="selectedDetail">
              · {{ jamLabel(selectedDetail.jam_mulai) }}–{{ jamLabel(selectedDetail.jam_selesai) }}
            </template>
          </p>
        </div>
        <button
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
        <div
          v-for="k in (selectedDetail.keluarga || [])"
          :key="k.keluarga_id"
          class="flex items-center justify-between gap-3 py-2"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <div
              class="w-9 h-9 rounded-full overflow-hidden shrink-0 bg-[var(--card2)] grid place-items-center text-[12px] font-bold text-[var(--gm)]"
            >
              <img
                v-if="k.foto"
                :src="mediaUrl(k.foto)"
                alt=""
                class="w-full h-full object-cover"
              />
              <span v-else>{{ inisial(k.nama) }}</span>
            </div>
            <span class="text-[14px] font-semibold min-w-0 truncate">{{ k.nama || '—' }}</span>
          </div>
          <span class="text-[13px] text-[var(--mut)] shrink-0">{{ k.alamat }}</span>
        </div>
        <p v-if="!(selectedDetail.keluarga || []).length" class="text-[13px] text-[var(--mut)] m-0 py-1">
          Belum ada keluarga bertugas
        </p>
      </template>
      <p v-else class="text-[13px] text-[var(--mut)] py-2">Tidak ada jadwal ronda di tanggal ini</p>
    </div>

    <div v-if="showIsi" class="fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4" @click.self="showIsi = false">
      <div class="bg-[var(--bg)] rounded-[20px] p-5 w-full max-w-md">
        <p class="font-bold text-[16px] m-0 mb-1">Isi otomatis</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mb-4">
          Hanya hari di <b>jadwal tetap</b>. KK aktif dibagi bergiliran (urut blok/nomor) supaya semua kebagian.
        </p>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Keluarga per malam</label>
        <input v-model.number="isiPerMalam" type="number" min="1" max="20" class="w-full min-h-[44px] px-3 rounded-[12px] bg-[var(--search)] mb-3" />
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Durasi</label>
        <select v-model="isiDurasi" class="w-full min-h-[44px] px-3 rounded-[12px] bg-[var(--search)] mb-4">
          <option value="minggu">1 minggu (sisa minggu ini)</option>
          <option v-for="n in 12" :key="n" :value="String(n)">{{ n }} bulan</option>
        </select>
        <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-2" :disabled="isiBusy" @click="jalankanIsi">
          {{ isiBusy ? 'Mengisi…' : 'Jalankan' }}
        </button>
        <button type="button" class="w-full min-h-[44px] rounded-full bg-[var(--card2)] font-semibold" @click="showIsi = false">Batal</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Calendar, List, Wand2, ChevronRight, ChevronLeft } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { kalenderRonda, malamTerdekat, isiOtomatisRonda, detailMalam } from '@shared/services/ronda.js'
import { mediaUrl } from '@shared/services/upload.js'

const router = useRouter()
const malam = ref(null)
const malamLoading = ref(true)
const days = ref([])
const aksiMsg = ref('')
const aksiOk = ref(true)
const calYm = ref(new Date().toISOString().slice(0, 7))
const selectedTanggal = ref('')
const selectedDetail = ref(null)
const selectedLoading = ref(false)
const showIsi = ref(false)
const isiPerMalam = ref(2)
const isiDurasi = ref('1')
const isiBusy = ref(false)

const bulanLabel = computed(() => {
  const [y, m] = calYm.value.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

const aksi = [
  { label: 'Jadwal tetap', icon: Calendar, to: '/ronda/jadwal-tetap', bg: 'rgba(10,143,68,.18)', color: '#0A8F44' },
  { label: 'Jadwal khusus', icon: List, to: '/ronda/jadwal-khusus', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Isi otomatis', icon: Wand2, action: 'isi', bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
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
  return new Date(tgl + 'T00:00:00').toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  })
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
    out.push({ n, date, ronda: !!r, khusus: r?.sumber === 'khusus' })
  }
  days.value = out
}

async function onPickDay(day) {
  if (!day?.date) return
  selectedTanggal.value = day.date
  selectedDetail.value = null
  if (!day.ronda) {
    selectedLoading.value = false
    return
  }
  selectedLoading.value = true
  const res = await detailMalam(day.date)
  selectedLoading.value = false
  if (res.ok && res.data) selectedDetail.value = res.data
  else selectedDetail.value = null
}

function onAksi(a) {
  if (a.to) {
    router.push(a.to)
    return
  }
  if (a.action === 'isi') showIsi.value = true
}

async function jalankanIsi() {
  isiBusy.value = true
  aksiMsg.value = ''
  const res = await isiOtomatisRonda({
    keluarga_per_malam: isiPerMalam.value || 2,
    durasi: isiDurasi.value,
  })
  isiBusy.value = false
  showIsi.value = false
  aksiOk.value = !!res.ok
  if (res.ok) {
    const d = res.data || {}
    aksiMsg.value = `Selesai: ${d.dibuat ?? 0} malam · ${d.dilewati ?? 0} dilewati · ${d.total_kk ?? 0} KK`
    await loadKalender()
    const mRes = await malamTerdekat()
    if (mRes.ok) malam.value = mRes.data
  } else {
    aksiMsg.value = res.error || 'Gagal isi otomatis'
  }
}

async function loadKalender() {
  const res = await kalenderRonda(calYm.value)
  if (res.ok) buildDays(res.data)
  else buildDays([])
}

onMounted(async () => {
  malamLoading.value = true
  const [mRes] = await Promise.all([malamTerdekat(), loadKalender()])
  malamLoading.value = false
  if (mRes.ok) malam.value = mRes.data
})
</script>
