<template>
  <div>
    <AppBackHeader title="Keuangan" />

    <div v-if="loading" class="animate-pulse" aria-hidden="true">
      <div class="rounded-[20px] bg-[var(--card2)] p-5 mb-5">
        <div class="flex items-center justify-between gap-3 mb-2">
          <div class="h-3 rounded-full bg-[var(--line)]/60 w-28" />
          <div class="h-6 w-24 rounded-full bg-[var(--line)]/60" />
        </div>
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-full bg-[var(--line)]/60 shrink-0" />
          <div class="h-7 rounded-full bg-[var(--line)]/60 w-40" />
        </div>
        <div class="grid grid-cols-2 gap-2 mt-4">
          <div class="h-10 rounded-full bg-[var(--line)]/60" />
          <div class="h-10 rounded-full bg-[var(--line)]/60" />
        </div>
        <div class="mt-4 pt-3 space-y-2.5 border-t border-[var(--line)]/40">
          <div v-for="n in 3" :key="n" class="flex items-center justify-between gap-3">
            <div class="h-3.5 rounded-full bg-[var(--line)]/60 w-[40%]" />
            <div class="h-3.5 rounded-full bg-[var(--line)]/60 w-20" />
          </div>
        </div>
      </div>
      <div class="h-10 rounded-full bg-[var(--card2)] mb-3" />
      <div class="px-1 space-y-3">
        <div v-for="n in 5" :key="n" class="flex items-center gap-3 py-2">
          <div class="flex-1 space-y-2">
            <div class="h-3.5 rounded-full bg-[var(--card2)] w-[60%]" />
            <div class="h-3 rounded-full bg-[var(--card2)] w-[40%]" />
          </div>
          <div class="h-3.5 rounded-full bg-[var(--card2)] w-16" />
        </div>
      </div>
    </div>

    <template v-else>
      <div
        class="rounded-[20px] p-5 text-white mb-5 relative overflow-hidden"
        style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)"
      >
        <div class="relative z-10">
          <div class="flex items-center justify-between gap-2 mb-1.5">
            <p class="text-[13px] font-semibold text-white/90 m-0">Total saldo kas</p>
            <span class="text-[12px] font-semibold px-3 py-1 rounded-full bg-white/20 shrink-0">
              {{ labelBulan }}
            </span>
          </div>
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-11 h-11 rounded-full bg-white/20 text-white grid place-items-center shrink-0">
              <Wallet :size="20" />
            </div>
            <p class="text-[26px] font-extrabold m-0 tracking-tight leading-none whitespace-nowrap tabular-nums">
              {{ formatRp(saldoKas) }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-2 mt-4">
            <div class="flex items-center gap-2 min-h-[40px] px-3 rounded-full bg-white/20">
              <ArrowDownLeft :size="16" class="text-emerald-200 shrink-0" />
              <span class="text-[13px] font-bold text-emerald-100 truncate">
                {{ formatRp(totalMasuk) }}
              </span>
            </div>
            <div class="flex items-center gap-2 min-h-[40px] px-3 rounded-full bg-white/20">
              <ArrowUpRight :size="16" class="text-red-200 shrink-0" />
              <span class="text-[13px] font-bold text-red-100 truncate">
                {{ formatRp(totalKeluar) }}
              </span>
            </div>
          </div>

          <div v-if="daftarKas.length" class="mt-4 pt-3 border-t border-white/20 space-y-0">
            <div
              v-for="k in daftarKas"
              :key="k.id"
              class="flex items-center gap-3 py-1.5"
            >
              <div class="w-8 h-8 rounded-full grid place-items-center shrink-0 bg-white/15 text-white">
                <Landmark :size="15" />
              </div>
              <p class="flex-1 min-w-0 text-[13px] font-medium m-0 truncate text-white/90">
                {{ k.nama }}
              </p>
              <span class="text-[13px] font-bold tabular-nums shrink-0 text-white whitespace-nowrap">
                {{ formatRp(k.saldo) }}
              </span>
            </div>
          </div>
        </div>
        <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
      </div>

      <div
        class="flex p-1 rounded-full bg-[var(--card2)] mb-3"
        role="tablist"
        aria-label="Jenis riwayat"
      >
        <button
          type="button"
          role="tab"
          class="flex-1 min-h-[40px] rounded-full text-[13px] font-semibold transition"
          :class="tab === 'kas'
            ? 'bg-[var(--card)] text-[var(--text)] shadow-[var(--sh)]'
            : 'text-[var(--mut)]'"
          :aria-selected="tab === 'kas'"
          @click="tab = 'kas'"
        >
          Riwayat kas
        </button>
        <button
          type="button"
          role="tab"
          class="flex-1 min-h-[40px] rounded-full text-[13px] font-semibold transition"
          :class="tab === 'iuran'
            ? 'bg-[var(--card)] text-[var(--text)] shadow-[var(--sh)]'
            : 'text-[var(--mut)]'"
          :aria-selected="tab === 'iuran'"
          @click="tab = 'iuran'"
        >
          Riwayat iuran
        </button>
      </div>

      <p v-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

      <template v-else-if="tab === 'kas'">
        <EmptyState v-if="!grupKas.length" title="Belum ada transaksi" />
        <div v-else class="px-1">
          <div v-for="g in grupKas" :key="g.key" class="mb-3">
            <p class="text-[13px] font-bold text-[var(--mut)] m-0 px-2 py-2">{{ g.label }}</p>
            <div
              v-for="t in g.items"
              :key="t.id"
              class="w-full flex flex-row items-center gap-3 px-2 py-3"
            >
              <div class="min-w-0 flex-1">
                <p class="font-bold text-[15px] m-0 leading-tight">{{ labelKasItem(t) }}</p>
                <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">
                  {{ formatTanggal(t.tanggal) }}
                  <span v-if="isAlokasi(t)"> · Alokasi</span>
                  <span v-else-if="t.kategori"> · {{ t.kategori }}</span>
                  <span v-if="!isAlokasi(t) && t.kas_pos_nama"> · {{ t.kas_pos_nama }}</span>
                </p>
              </div>
              <span
                class="font-bold shrink-0 whitespace-nowrap"
                :class="isAlokasi(t) ? 'text-sky-600' : (t.tipe === 'masuk' ? 'text-[var(--g)]' : 'text-red-500')"
              >
                <template v-if="isAlokasi(t)">{{ formatRp(t.nominal) }}</template>
                <template v-else>{{ t.tipe === 'masuk' ? '+' : '−' }}{{ formatRp(t.nominal) }}</template>
              </span>
            </div>
          </div>
        </div>
      </template>

      <template v-else>
        <EmptyState v-if="!grupIuran.length" title="Belum ada pembayaran iuran" />
        <div v-else class="px-1">
          <div v-for="g in grupIuran" :key="g.key" class="mb-3">
            <p class="text-[13px] font-bold text-[var(--mut)] m-0 px-2 py-2">{{ g.label }}</p>
            <div
              v-for="p in g.items"
              :key="p.id"
              class="w-full flex flex-row items-center gap-3 px-2 py-3"
            >
              <div class="min-w-0 flex-1">
                <p class="font-bold text-[15px] m-0 leading-tight">{{ p.nama_kepala || p.alamat || 'Warga' }}</p>
                <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">
                  {{ formatTanggal(p.tanggal) }}
                  <span v-if="p.metode"> · {{ p.metode }}</span>
                </p>
              </div>
              <span class="font-bold shrink-0 whitespace-nowrap text-[var(--g)]">
                +{{ formatRp(p.nominal) }}
              </span>
            </div>
          </div>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { ref, computed, onMounted } from 'vue'
import { Wallet, ArrowDownLeft, ArrowUpRight, Landmark } from 'lucide-vue-next'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { portalKas } from '@shared/services/keuangan.js'

const loading = ref(true)
const err = ref('')
const saldoKas = ref(0)
const daftarKas = ref([])
const kasItems = ref([])
const pembayaranItems = ref([])
const tab = ref('kas')

const BULAN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

const labelBulan = computed(() => {
  const d = new Date()
  return d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

const ymSekarang = computed(() => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
})

function isAlokasi(t) {
  return !!(t?.is_alokasi || t?.display_tipe === 'alokasi' || t?.kategori === 'Alokasi antar pos')
}

const totalMasuk = computed(() =>
  kasItems.value
    .filter((t) => !isAlokasi(t) && t.tipe === 'masuk' && String(t.tanggal || '').startsWith(ymSekarang.value))
    .reduce((s, t) => s + Number(t.nominal || 0), 0)
)

const totalKeluar = computed(() =>
  kasItems.value
    .filter((t) => !isAlokasi(t) && t.tipe === 'keluar' && String(t.tanggal || '').startsWith(ymSekarang.value))
    .reduce((s, t) => s + Number(t.nominal || 0), 0)
)

function ymFromTanggal(t) {
  const s = String(t || '')
  return s.length >= 7 ? s.slice(0, 7) : ''
}

function labelBulanYm(ym) {
  if (!ym || ym.length < 7) return 'Lainnya'
  const m = Number(ym.slice(5, 7))
  const y = ym.slice(0, 4)
  return `Kas ${BULAN[m] || ym} ${y}`
}

function labelIuranYm(ym) {
  if (!ym || ym.length < 7) return 'Lainnya'
  const m = Number(ym.slice(5, 7))
  const y = ym.slice(0, 4)
  return `Iuran ${BULAN[m] || ym} ${y}`
}

function groupByMonth(items, getTanggal, labelFn) {
  const map = new Map()
  for (const it of items) {
    const ym = ymFromTanggal(getTanggal(it))
    if (!map.has(ym)) map.set(ym, [])
    map.get(ym).push(it)
  }
  const keys = [...map.keys()].sort((a, b) => (a < b ? 1 : a > b ? -1 : 0))
  return keys.map((k) => ({
    key: k || 'x',
    label: labelFn(k),
    items: map.get(k),
  }))
}

const grupKas = computed(() =>
  groupByMonth(kasItems.value, (t) => t.tanggal, labelBulanYm)
)

const grupIuran = computed(() =>
  groupByMonth(pembayaranItems.value, (p) => p.tanggal, labelIuranYm)
)

function labelKasItem(t) {
  if (t.keterangan) return t.keterangan
  if (t.kategori) return t.kategori
  return t.tipe === 'masuk' ? 'Masuk' : 'Keluar'
}

function formatTanggal(t) {
  if (!t) return ''
  const d = new Date(String(t).length === 10 ? t + 'T00:00:00' : String(t).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return String(t)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(async () => {
  loading.value = true
  err.value = ''
  const kas = await portalKas()
  if (!kas.ok) {
    err.value = kas.error || 'Gagal memuat kas'
    loading.value = false
    return
  }
  const d = kas.data || {}
  saldoKas.value = Number(d.saldo ?? 0)
  daftarKas.value = Array.isArray(d.pos) ? d.pos.filter((p) => p.aktif !== false) : []
  const rawItems = Array.isArray(d.items) ? d.items : []
  kasItems.value = rawItems.filter((t) => !t.otomatis && !(t.keterangan || '').startsWith('Otomatis dari pembayaran'))
  pembayaranItems.value = Array.isArray(d.pembayaran) ? d.pembayaran : []
  loading.value = false
})
</script>
