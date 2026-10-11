<template>
  <div>
    <AppBackHeader title="Jadwal ronda" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 mb-4 shadow-[var(--sh)]">
        <p class="text-[13px] text-[var(--mut)] m-0">{{ cardLabel }}</p>
        <template v-if="malam">
          <p class="font-bold text-[16px] m-0 mt-1">{{ formatHari(malam.tanggal) }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ jam(malam.jam_mulai) }}–{{ jam(malam.jam_selesai) }}</p>
          <p class="text-[13px] m-0 mt-2">Bertugas: {{ (malam.keluarga || []).map(k => k.alamat).join(', ') || '—' }}</p>
          <div v-if="statusSaya === 'hadir'" class="mt-3 text-[13px] font-semibold text-[var(--g)]">
            Sudah absen{{ absenWaktu ? ' · ' + absenWaktu : '' }}
          </div>
          <div v-else-if="bisaAbsen" class="mt-4">
            <label class="block w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold grid place-items-center cursor-pointer">
              {{ busy ? 'Mengirim…' : 'Absen ronda' }}
              <input type="file" accept="image/*" capture="environment" class="hidden" :disabled="busy" @change="onAbsen" />
            </label>
          </div>
          <p v-else-if="keluargaId && statusSaya === 'belum' && isHariIni" class="mt-3 text-[13px] text-amber-700 m-0">
            Tombol absen muncul saat jam ronda (±30 menit).
          </p>
          <p v-else-if="keluargaId && isGiliranSaya && !isHariIni" class="mt-3 text-[13px] text-[var(--mut)] m-0">
            Giliran keluargamu. Absen hanya di hari H.
          </p>
        </template>
        <p v-else class="text-[14px] text-[var(--mut)] m-0 mt-1">Tidak ada jadwal ronda terdekat</p>
        <p v-if="msg" class="text-[13px] text-center mt-3" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
      </div>

      <h2 class="text-[15px] font-bold mb-2">Malam mendatang</h2>
      <div class="space-y-1 mb-2">
        <div
          v-for="d in daftarMendatang"
          :key="d.tanggal"
          class="flex justify-between px-2 py-2.5 rounded-[12px]"
          :class="isTanggalSaya(d) ? 'bg-[var(--gd)]' : ''"
        >
          <div>
            <span class="font-semibold">{{ formatHariPendek(d.tanggal) }}</span>
            <span v-if="isTanggalSaya(d)" class="text-[12px] text-[var(--gm)] font-semibold ml-1">· giliranmu</span>
          </div>
          <span class="text-[13px] text-[var(--mut)]">{{ jam(d.jam_mulai) }}–{{ jam(d.jam_selesai) }}</span>
        </div>
        <p v-if="!daftarMendatang.length" class="text-[13px] text-[var(--mut)] text-center py-4">Belum ada jadwal</p>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useAuth } from '@shared/composables/useAuth.js'
import { malamIni, malamTerdekat, kalenderRonda, absenWarga } from '@shared/services/ronda.js'
import { uploadGambar } from '@shared/services/upload.js'

const { user } = useAuth()
const loading = ref(true)
const err = ref('')
const malam = ref(null)
const kalender = ref([])
const busy = ref(false)
const msg = ref('')
const msgOk = ref(true)

const keluargaId = computed(() => user.value?.keluarga_id ? Number(user.value.keluarga_id) : null)
const today = new Date().toISOString().slice(0, 10)

const rowSaya = computed(() => {
  if (!malam.value || !keluargaId.value) return null
  return (malam.value.keluarga || []).find((k) => Number(k.keluarga_id) === keluargaId.value) || null
})
const statusSaya = computed(() => rowSaya.value?.status || null)
const absenSaya = computed(() => rowSaya.value?.absen || null)
const absenWaktu = computed(() => {
  const w = absenSaya.value?.waktu || absenSaya.value?.waktu_server || absenSaya.value?.created_at || ''
  const s = String(w)
  if (s.length >= 16 && (s.includes(' ') || s.includes('T'))) return s.slice(11, 16)
  return s ? s.slice(0, 5) : ''
})
const isHariIni = computed(() => malam.value?.tanggal === today)
const isGiliranSaya = computed(() => !!rowSaya.value)
const cardLabel = computed(() => {
  if (!malam.value) return 'Giliran berikutnya'
  if (isHariIni.value) return 'Malam ini'
  return 'Giliran keluargamu berikutnya'
})

const daftarMendatang = computed(() => {
  const list = (kalender.value || []).filter((d) => d.tanggal >= today)
  return list.slice(0, 12)
})

function isTanggalSaya(d) {
  if (!keluargaId.value || !malam.value) return false
  return d.tanggal === malam.value.tanggal && isGiliranSaya.value
}

function jam(j) { return j ? String(j).slice(0, 5) : '—' }
function formatHari(tgl) {
  if (!tgl) return ''
  return new Date(tgl + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' })
}
function formatHariPendek(tgl) {
  if (!tgl) return ''
  return new Date(tgl + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' })
}

function dalamJendelaJam() {
  const m = malam.value
  if (!m?.jam_mulai || m.tanggal !== today) return false
  const now = new Date()
  const [hm, mm] = String(m.jam_mulai).slice(0, 5).split(':').map(Number)
  const [hs, ms] = String(m.jam_selesai || '00:00').slice(0, 5).split(':').map(Number)
  let mulai = new Date(now)
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

async function reloadMalam() {
  const m = await malamIni()
  if (m.ok && m.data) malam.value = m.data
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
  if (res.ok) await reloadMalam()
}

onMounted(async () => {
  const periode = new Date().toISOString().slice(0, 7)
  const [mIni, mDekat, k] = await Promise.all([
    malamIni(),
    malamTerdekat(),
    kalenderRonda(periode),
  ])
  loading.value = false
  if (!mIni.ok && !mDekat.ok && !k.ok) {
    err.value = mIni.error || mDekat.error || k.error || 'Gagal'
  }
  const tonight = mIni.ok ? mIni.data : null
  const next = mDekat.ok ? mDekat.data : null
  if (tonight && keluargaId.value) {
    const onDuty = (tonight.keluarga || []).some((x) => Number(x.keluarga_id) === keluargaId.value)
    malam.value = onDuty || !next ? tonight : next
  } else {
    malam.value = tonight || next
  }
  if (k.ok) kalender.value = k.data || []
})
</script>
