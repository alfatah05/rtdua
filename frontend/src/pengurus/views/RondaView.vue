<template>
  <div>
    <AppBackHeader title="Jadwal ronda" />

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4">
      <p class="text-[13px] font-semibold text-[var(--mut)] m-0">Malam ini</p>
      <template v-if="malamLoading">
        <p class="text-[14px] text-[var(--mut)] mt-2 m-0">Memuat…</p>
      </template>
      <template v-else-if="malam">
        <p class="text-[17px] font-bold mt-1 m-0">{{ formatHari(malam.tanggal) }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0">{{ jamLabel(malam.jam_mulai) }} – {{ jamLabel(malam.jam_selesai) }}</p>
        <div class="mt-3 space-y-1">
          <p v-for="k in (malam.keluarga || []).slice(0, 4)" :key="k.keluarga_id" class="text-[14px] m-0">
            {{ k.alamat }} —
            <span :class="k.status === 'hadir' ? 'text-[var(--g)] font-semibold' : 'text-amber-600 font-semibold'">
              {{ k.status === 'hadir' ? 'Hadir' : 'Belum absen' }}
            </span>
          </p>
        </div>
        <button type="button" class="mt-3 text-[13px] font-semibold text-[var(--g)]" @click="$router.push('/ronda/malam/' + malam.tanggal)">
          Kelola malam ini →
        </button>
      </template>
      <template v-else>
        <p class="text-[14px] text-[var(--mut)] mt-2 m-0">Tidak ada jadwal ronda malam ini</p>
      </template>
    </div>

    <div class="grid grid-cols-3 gap-2 text-center mb-5">
      <button v-for="a in aksi" :key="a.label" type="button" class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform" @click="onAksi(a)">
        <span class="w-12 h-12 rounded-full grid place-items-center" :style="{ background: a.bg, color: a.color }">
          <component :is="a.icon" :size="20" />
        </span>
        <span class="text-[11px] font-semibold leading-tight">{{ a.label }}</span>
      </button>
    </div>
    <p v-if="aksiMsg" class="text-[13px] text-center mb-3" :class="aksiOk ? 'text-[var(--g)]' : 'text-red-600'">{{ aksiMsg }}</p>

    <h2 class="text-[15px] font-bold mb-2">{{ bulanLabel }}</h2>
    <div class="grid grid-cols-7 gap-1 text-center text-[12px] mb-2">
      <span v-for="d in ['M','S','S','R','K','J','S']" :key="d" class="text-[var(--mut)] font-semibold py-1">{{ d }}</span>
    </div>
    <div class="grid grid-cols-7 gap-1">
      <button
        v-for="(day, i) in days"
        :key="i"
        type="button"
        class="aspect-square rounded-full text-[13px] font-semibold grid place-items-center relative"
        :class="day.ronda ? (day.khusus ? 'bg-blue-500/20 text-blue-700' : 'bg-[var(--gd)] text-[var(--gm)]') : 'text-[var(--text)]'"
        :disabled="!day.n"
        @click="day.ronda && $router.push('/ronda/malam/' + day.date)"
      >
        {{ day.n || '' }}
        <span v-if="day.ronda" class="absolute bottom-0.5 w-1 h-1 rounded-full bg-[var(--g)]"></span>
      </button>
    </div>
    <p class="text-[12px] text-[var(--mut)] mt-3">Titik = ada ronda · Biru = jadwal khusus</p>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Calendar, List, Wand2 } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { kalenderRonda, malamIni, isiOtomatisRonda } from '@shared/services/ronda.js'

const router = useRouter()
const malam = ref(null)
const malamLoading = ref(true)
const days = ref([])
const aksiMsg = ref('')
const aksiOk = ref(true)
const periode = new Date().toISOString().slice(0, 7)

const bulanLabel = computed(() => {
  const [y, m] = periode.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

const aksi = [
  { label: 'Jadwal tetap', icon: Calendar, to: '/ronda/jadwal-tetap', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Jadwal khusus', icon: List, to: '/ronda/jadwal-khusus', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Isi otomatis', icon: Wand2, action: 'isi', bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
]

function jamLabel(j) {
  if (!j) return '—'
  return String(j).slice(0, 5).replace(':', '.')
}
function formatHari(tgl) {
  if (!tgl) return ''
  const d = new Date(tgl + 'T00:00:00')
  return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

function buildDays(kal) {
  const [y, m] = periode.split('-').map(Number)
  const first = new Date(y, m - 1, 1)
  const startPad = first.getDay()
  const lastDate = new Date(y, m, 0).getDate()
  const byDate = {}
  for (const r of kal || []) {
    byDate[r.tanggal] = r
  }
  const out = []
  for (let i = 0; i < startPad; i++) out.push({ n: null })
  for (let n = 1; n <= lastDate; n++) {
    const date = `${periode}-${String(n).padStart(2, '0')}`
    const r = byDate[date]
    out.push({
      n,
      date,
      ronda: !!r,
      khusus: r?.sumber === 'khusus',
    })
  }
  days.value = out
}

async function onAksi(a) {
  if (a.to) {
    router.push(a.to)
    return
  }
  if (a.action === 'isi') {
    if (!confirm('Isi otomatis akan membagi KK aktif ke jadwal tetap (urut blok/nomor), lalu generate malam bulan ini. Lanjutkan?')) return
    aksiMsg.value = 'Mengisi jadwal…'
    const res = await isiOtomatisRonda({ periode, keluarga_per_malam: 2 })
    aksiOk.value = !!res.ok
    if (res.ok) {
      const gen = res.data?.generate?.dibuat ?? 0
      const kk = res.data?.total_kk ?? 0
      aksiMsg.value = `Selesai: ${kk} KK dibagi · ${gen} malam dibuat`
      await loadKalender()
      const mRes = await malamIni()
      if (mRes.ok) malam.value = mRes.data
    } else {
      aksiMsg.value = res.error || 'Gagal isi otomatis'
    }
  }
}

async function loadKalender() {
  const res = await kalenderRonda(periode)
  if (res.ok) buildDays(res.data)
  else buildDays([])
}

onMounted(async () => {
  malamLoading.value = true
  const [mRes] = await Promise.all([malamIni(), loadKalender()])
  malamLoading.value = false
  if (mRes.ok) malam.value = mRes.data
})
</script>
