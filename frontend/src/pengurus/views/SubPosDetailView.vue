<template>
  <div>
    <AppBackHeader :title="subPos?.judul || 'Detail sub pos'" />

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else-if="subPos">
      <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] p-5 mb-4 shadow-[var(--sh)]">
        <p class="text-[13px] text-[var(--mut)] m-0">{{ labelJenis(subPos.jenis) }}</p>
        <p class="text-[18px] font-extrabold m-0 mt-1">{{ subPos.judul }}</p>
        <p class="text-[28px] font-extrabold m-0 mt-3 tracking-tight" :class="subPos.saldo > 0 ? 'text-[var(--g)]' : ''">
          {{ formatRp(subPos.saldo) }}
        </p>
        <p class="text-[12px] text-[var(--mut)] m-0 mt-1">Saldo tersedia</p>
      </div>

      <!-- Aksi -->
      <div class="grid grid-cols-3 gap-2 mb-5">
        <button
          v-for="a in aksiList"
          :key="a.key"
          type="button"
          class="min-h-[44px] rounded-full font-bold text-[13px] border"
          :class="formMode === a.key
            ? 'bg-[var(--g)] text-white border-[var(--g)]'
            : 'bg-[var(--card)] border-[var(--line)]'"
          @click="formMode = formMode === a.key ? '' : a.key"
        >
          {{ a.label }}
        </button>
      </div>

      <!-- Form masuk / keluar / alokasi -->
      <div v-if="formMode" class="rounded-[16px] border border-[var(--line)] bg-[var(--card)] p-4 space-y-3 mb-5">
        <p class="font-bold text-[15px] m-0">{{ formTitle }}</p>

        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal (Rp)</label>
          <input
            v-model="formNominal"
            type="text"
            inputmode="numeric"
            placeholder="50000"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          />
        </div>

        <div v-if="formMode === 'alokasi'">
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Pos kas tujuan</label>
          <select
            v-model="formPosKasId"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          >
            <option :value="null" disabled>Pilih pos kas</option>
            <option v-for="p in posKasAktif" :key="p.id" :value="p.id">
              {{ p.nama }} ({{ formatRp(p.saldo) }})
            </option>
          </select>
        </div>

        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal</label>
          <input
            v-model="formTanggal"
            type="date"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          />
        </div>

        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Keterangan (opsional)</label>
          <input
            v-model="formKet"
            type="text"
            placeholder="Catatan singkat"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          />
        </div>

        <p v-if="formErr" class="text-[13px] text-red-600 m-0">{{ formErr }}</p>
        <button
          type="button"
          class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-60"
          :disabled="saving"
          @click="onSubmit"
        >
          {{ saving ? 'Memproses…' : 'Simpan' }}
        </button>
      </div>

      <!-- Filter mutasi -->
      <div class="flex items-center justify-between mb-2 px-1">
        <h2 class="text-[17px] font-bold m-0">Riwayat</h2>
        <select
          v-model="filterTipe"
          class="min-h-[36px] px-3 rounded-full bg-[var(--search)] text-[13px] outline-none"
          @change="loadMutasi"
        >
          <option value="">Semua</option>
          <option value="masuk">Masuk</option>
          <option value="keluar">Keluar</option>
          <option value="alokasi">Alokasi</option>
        </select>
      </div>

      <AppPageSpinner v-if="loadingMutasi" class="!py-4" />
      <EmptyState v-else-if="!mutasi.length" title="Belum ada mutasi" />
      <div v-else class="space-y-0.5 px-1">
        <div
          v-for="m in mutasi"
          :key="m.id"
          class="flex items-start gap-3 px-2 py-3"
        >
          <div
            class="w-10 h-10 rounded-full grid place-items-center shrink-0"
            :class="iconClass(m.tipe)"
          >
            <ArrowDownLeft v-if="m.tipe === 'masuk'" :size="18" />
            <ArrowUpRight v-else-if="m.tipe === 'keluar'" :size="18" />
            <ArrowLeftRight v-else :size="18" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-bold text-[14px] m-0 leading-tight">{{ labelTipe(m.tipe) }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5 leading-tight">
              {{ formatTanggal(m.tanggal) }}
              <span v-if="m.keterangan"> · {{ m.keterangan }}</span>
            </p>
            <template v-if="!m.pembayaran_id">
              <div v-if="batalId === m.id" class="mt-1.5 space-y-1.5">
                <input
                  v-model="batalAlasan"
                  type="text"
                  placeholder="Alasan pembatalan"
                  class="w-full min-h-[40px] px-3 rounded-[10px] bg-[var(--search)] text-[13px] outline-none"
                />
                <div class="flex gap-2">
                  <button
                    type="button"
                    class="text-[12px] font-semibold text-red-600 min-h-[32px]"
                    :disabled="batalSaving"
                    @click="onBatalConfirm(m)"
                  >
                    {{ batalSaving ? '…' : 'Ya, batalkan' }}
                  </button>
                  <button
                    type="button"
                    class="text-[12px] font-semibold text-[var(--mut)] min-h-[32px]"
                    @click="batalId = 0; batalAlasan = ''"
                  >
                    Batal
                  </button>
                </div>
              </div>
              <button
                v-else
                type="button"
                class="text-[12px] font-semibold text-red-600 mt-1 min-h-[32px]"
                @click="batalId = m.id; batalAlasan = ''"
              >
                Batalkan
              </button>
            </template>
          </div>
          <span
            class="font-bold shrink-0 text-[14px]"
            :class="m.tipe === 'masuk' ? 'text-[var(--g)]' : (m.tipe === 'keluar' ? 'text-red-500' : 'text-sky-600')"
          >
            {{ m.tipe === 'masuk' ? '+' : '−' }}{{ formatRp(m.nominal) }}
          </span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { ArrowDownLeft, ArrowUpRight, ArrowLeftRight } from 'lucide-vue-next'
import { formatRp } from '@shared/utils/format.js'
import { useToast } from '@shared/composables/useToast.js'
import {
  detailSubPos,
  listSubPosMutasi,
  subPosMasuk,
  subPosKeluar,
  subPosAlokasi,
  subPosBatalMutasi,
  listKasPos,
} from '@shared/services/keuangan.js'

const route = useRoute()
const { success, error: toastError } = useToast()

const loading = ref(true)
const loadingMutasi = ref(false)
const err = ref('')
const subPos = ref(null)
const mutasi = ref([])
const filterTipe = ref('')
const posKas = ref([])

const formMode = ref('')
const formNominal = ref('')
const formTanggal = ref(new Date().toISOString().slice(0, 10))
const formKet = ref('')
const formPosKasId = ref(null)
const formErr = ref('')
const saving = ref(false)
const batalId = ref(0)
const batalAlasan = ref('')
const batalSaving = ref(false)

const aksiList = [
  { key: 'masuk', label: 'Masuk' },
  { key: 'keluar', label: 'Keluar' },
  { key: 'alokasi', label: 'Alokasi' },
]

const formTitle = computed(() => {
  if (formMode.value === 'masuk') return 'Catat masuk manual'
  if (formMode.value === 'keluar') return 'Catat keluar'
  if (formMode.value === 'alokasi') return 'Alokasi ke pos kas'
  return ''
})

const posKasAktif = computed(() => posKas.value.filter((p) => p.aktif !== false))

function labelJenis(j) {
  if (j === 'komponen') return 'Komponen iuran'
  if (j === 'khusus') return 'Iuran khusus'
  if (j === 'denda') return 'Denda ronda'
  return j || '—'
}

function labelTipe(t) {
  if (t === 'masuk') return 'Masuk'
  if (t === 'keluar') return 'Keluar'
  if (t === 'alokasi') return 'Alokasi ke pos kas'
  return t || '—'
}

function iconClass(t) {
  if (t === 'masuk') return 'bg-emerald-500/15 text-emerald-600'
  if (t === 'keluar') return 'bg-red-500/15 text-red-600'
  return 'bg-sky-500/15 text-sky-600'
}

function formatTanggal(t) {
  if (!t) return '—'
  const d = String(t).slice(0, 10)
  const [y, m, day] = d.split('-')
  if (!y || !m || !day) return d
  const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
  return `${Number(day)} ${bulan[Number(m) - 1]} ${y}`
}

function parseNominal(s) {
  return parseInt(String(s || '').replace(/[^\d]/g, ''), 10) || 0
}

async function loadDetail() {
  const id = Number(route.params.id)
  if (!id) {
    err.value = 'ID tidak valid'
    loading.value = false
    return
  }
  loading.value = true
  err.value = ''
  const res = await detailSubPos(id)
  loading.value = false
  if (!res.ok || !res.data) {
    err.value = res.error || 'Sub pos tidak ditemukan'
    return
  }
  subPos.value = res.data
}

async function loadMutasi() {
  const id = Number(route.params.id)
  if (!id) return
  loadingMutasi.value = true
  const params = {}
  if (filterTipe.value) params.tipe = filterTipe.value
  const res = await listSubPosMutasi(id, params)
  loadingMutasi.value = false
  if (!res.ok) {
    mutasi.value = []
    return
  }
  const data = res.data
  mutasi.value = Array.isArray(data?.items) ? data.items : (Array.isArray(data) ? data : [])
  if (data?.sub_pos) {
    subPos.value = { ...subPos.value, ...data.sub_pos }
  }
}

async function loadPosKas() {
  const res = await listKasPos()
  if (res.ok) {
    const data = res.data
    posKas.value = Array.isArray(data) ? data : (data?.items || data?.pos || [])
  }
}

async function onSubmit() {
  formErr.value = ''
  const nominal = parseNominal(formNominal.value)
  if (nominal < 1) {
    formErr.value = 'Nominal wajib lebih dari 0'
    return
  }
  const id = Number(route.params.id)
  const payload = {
    nominal,
    tanggal: formTanggal.value || undefined,
    keterangan: formKet.value || undefined,
  }
  if (formMode.value === 'alokasi') {
    if (!formPosKasId.value) {
      formErr.value = 'Pilih pos kas tujuan'
      return
    }
    payload.pos_kas_id = formPosKasId.value
  }

  saving.value = true
  let res
  if (formMode.value === 'masuk') res = await subPosMasuk(id, payload)
  else if (formMode.value === 'keluar') res = await subPosKeluar(id, payload)
  else res = await subPosAlokasi(id, payload)
  saving.value = false

  if (!res.ok) {
    formErr.value = res.error || 'Gagal menyimpan'
    return
  }
  success(formMode.value === 'alokasi' ? 'Alokasi berhasil' : 'Disimpan')
  formNominal.value = ''
  formKet.value = ''
  formMode.value = ''
  if (res.data?.saldo != null && subPos.value) {
    subPos.value = { ...subPos.value, saldo: res.data.saldo }
  }
  await loadDetail()
  await loadMutasi()
}

async function onBatalConfirm(m) {
  const alasan = String(batalAlasan.value || '').trim()
  if (!alasan) {
    toastError('Alasan wajib')
    return
  }
  batalSaving.value = true
  const res = await subPosBatalMutasi(m.id, alasan)
  batalSaving.value = false
  if (!res.ok) {
    toastError(res.error || 'Gagal membatalkan')
    return
  }
  success('Mutasi dibatalkan')
  batalId.value = 0
  batalAlasan.value = ''
  await loadDetail()
  await loadMutasi()
}

watch(() => route.params.id, async () => {
  await loadDetail()
  await loadMutasi()
})

onMounted(async () => {
  await loadDetail()
  await Promise.all([loadMutasi(), loadPosKas()])
})
</script>
