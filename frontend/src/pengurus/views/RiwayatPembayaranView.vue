<template>
  <div>
    <AppBackHeader title="Riwayat pembayaran" />

    <div class="flex gap-2 mb-3 px-1">
      <div class="flex-1 flex items-center gap-2 bg-[var(--search)] rounded-full px-4 h-11">
        <Search :size="16" class="text-[var(--mut)]" />
        <input
          v-model="q"
          type="search"
          placeholder="Cari nama / alamat"
          class="flex-1 bg-transparent outline-none text-[14px]"
          @keyup.enter="load"
        />
      </div>
    </div>

    <div class="flex gap-2 mb-3 px-1 items-center">
      <button
        type="button"
        class="h-9 px-3 rounded-full text-[12px] font-semibold shrink-0 border"
        :class="modeSemua ? 'bg-[var(--g)] text-white border-[var(--g)]' : 'bg-[var(--card)] border-[var(--line)]'"
        @click="pilihSemua"
      >
        Semua
      </button>

      <button
        type="button"
        class="h-9 px-3 rounded-full text-[12px] font-semibold shrink-0 border inline-flex items-center gap-1.5"
        :class="!modeSemua ? 'bg-[var(--g)] text-white border-[var(--g)]' : 'bg-[var(--card)] border-[var(--line)]'"
        @click="showBulanSheet = true"
      >
        <Calendar :size="14" />
        <span>{{ labelBulanAktif }}</span>
      </button>
    </div>

    <label class="flex items-center gap-2 px-2 mb-3 text-[13px] text-[var(--mut)]">
      <input v-model="includeBatal" type="checkbox" class="accent-[var(--g)]" @change="load" />
      Tampilkan yang dibatalkan
    </label>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <EmptyState v-else-if="!items.length" title="Belum ada pembayaran" />

    <div v-else class="px-1 space-y-0.5">
      <button
        v-for="p in items"
        :key="p.id"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        :class="p.dibatalkan ? 'opacity-55' : ''"
        @click="$router.push('/keuangan/riwayat-pembayaran/' + p.id)"
      >
        <div
          class="w-10 h-10 rounded-full grid place-items-center shrink-0"
          :class="iconWrapClass(p)"
        >
          <component :is="iconFor(p)" :size="18" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0 leading-tight truncate">
            {{ p.alamat || '—' }}<span v-if="p.nama"> · {{ p.nama }}</span>
          </p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">
            {{ formatTanggal(p.tanggal_bayar) }} · {{ labelMetode(p.metode) }}
            <span v-if="p.dibatalkan" class="text-red-600 font-semibold"> · Dibatalkan</span>
          </p>
        </div>
        <div class="text-right shrink-0">
          <p
            class="font-bold text-[15px] m-0 tabular-nums"
            :class="p.dibatalkan ? 'line-through text-[var(--mut)]' : 'text-[var(--g)]'"
          >
            {{ formatRp(p.nominal) }}
          </p>
          <p v-if="p.dicatat_nama" class="text-[11px] text-[var(--mut)] m-0 mt-0.5 truncate max-w-[100px]">
            {{ p.dicatat_nama }}
          </p>
        </div>
      </button>
    </div>

    <!-- Sheet pilih bulan -->
    <Teleport to="body">
      <div
        v-if="showBulanSheet"
        class="fixed inset-0 z-[80] flex flex-col justify-end"
        @click.self="showBulanSheet = false"
      >
        <div class="absolute inset-0 bg-black/40" @click="showBulanSheet = false" />
        <div class="relative bg-[var(--card)] rounded-t-[20px] max-h-[70vh] flex flex-col shadow-xl">
          <div class="flex items-center justify-between px-4 pt-4 pb-2">
            <p class="font-bold text-[16px] m-0">Pilih bulan</p>
            <button
              type="button"
              class="text-[13px] font-semibold text-[var(--g)] px-2 py-1"
              @click="showBulanSheet = false"
            >
              Tutup
            </button>
          </div>
          <div class="overflow-y-auto px-2 pb-6">
            <button
              v-for="b in bulanOptions"
              :key="b.value"
              type="button"
              class="w-full flex items-center justify-between px-3 py-3 rounded-[12px] text-left"
              :class="!modeSemua && bulan === b.value ? 'bg-[var(--g)]/10' : 'active:bg-[var(--search)]'"
              @click="pilihBulan(b.value)"
            >
              <span class="font-semibold text-[14px]">{{ b.label }}</span>
              <span
                v-if="!modeSemua && bulan === b.value"
                class="text-[12px] font-bold text-[var(--g)]"
              >Aktif</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Search, Calendar, Banknote, ArrowLeftRight, History } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { listPembayaran } from '@shared/services/keuangan.js'

const q = ref('')
const modeSemua = ref(false)
const bulan = ref(currentBulan())
const includeBatal = ref(false)
const loading = ref(true)
const err = ref('')
const items = ref([])
const showBulanSheet = ref(false)

const bulanOptions = computed(() => {
  const out = []
  const now = new Date()
  // 24 bulan ke belakang
  for (let i = 0; i < 24; i++) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    out.push({
      value: `${y}-${m}`,
      label: d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }),
    })
  }
  return out
})

const labelBulanAktif = computed(() => {
  if (modeSemua.value) return 'Pilih bulan'
  const found = bulanOptions.value.find((b) => b.value === bulan.value)
  if (found) {
    // singkat: Nov 2026
    const [y, m] = bulan.value.split('-')
    const d = new Date(Number(y), Number(m) - 1, 1)
    return d.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })
  }
  return bulan.value || 'Pilih bulan'
})

function currentBulan() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

function pilihSemua() {
  modeSemua.value = true
  showBulanSheet.value = false
  load()
}

function pilihBulan(value) {
  bulan.value = value
  modeSemua.value = false
  showBulanSheet.value = false
  load()
}

function isTransfer(p) {
  const m = String(p?.metode || '').toLowerCase()
  return m === 'transfer' || m === 'qris' || !!p?.permintaan_id
}

function iconFor(p) {
  if (p.dibatalkan) return History
  if (isTransfer(p)) return ArrowLeftRight
  return Banknote
}

function iconWrapClass(p) {
  if (p.dibatalkan) return 'bg-red-500/15 text-red-600'
  if (isTransfer(p)) return 'bg-sky-500/15 text-sky-600'
  return 'bg-emerald-500/15 text-emerald-600'
}

function formatTanggal(t) {
  if (!t) return '—'
  const d = new Date(String(t).slice(0, 10) + 'T12:00:00')
  if (Number.isNaN(d.getTime())) return t
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function labelMetode(m) {
  const map = { tunai: 'Tunai', transfer: 'Transfer', qris: 'QRIS' }
  const k = String(m || '').toLowerCase()
  return map[k] || (m || 'Tunai')
}

/** Cadangan filter di client jika backend tidak memfilter */
function filterClient(list) {
  let out = Array.isArray(list) ? list.slice() : []
  if (!modeSemua.value && bulan.value) {
    const prefix = String(bulan.value) // YYYY-MM
    out = out.filter((p) => String(p.tanggal_bayar || '').startsWith(prefix))
  }
  return out
}

let searchTimer = null
watch(q, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(load, 350)
})

async function load() {
  loading.value = true
  err.value = ''
  const params = {
    q: q.value.trim() || undefined,
    include_batal: includeBatal.value || undefined,
    limit: 500,
  }
  // kirim bulan hanya jika mode filter bulan aktif
  if (!modeSemua.value && bulan.value) {
    params.bulan = String(bulan.value).trim()
  }
  const res = await listPembayaran(params)
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat riwayat.'
    items.value = []
    return
  }
  items.value = filterClient(res.data || [])
}

onMounted(load)
</script>
