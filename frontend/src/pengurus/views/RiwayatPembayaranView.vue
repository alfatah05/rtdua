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
        @click="bukaPickerBulan"
      >
        <Calendar :size="14" />
        <span>{{ labelBulanAktif }}</span>
      </button>

      <!-- input month tersembunyi: dibuka lewat pill -->
      <input
        ref="bulanInput"
        v-model="bulan"
        type="month"
        class="sr-only absolute opacity-0 w-0 h-0 pointer-events-none"
        tabindex="-1"
        @change="onBulanChange"
      />
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
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
const bulanInput = ref(null)

const labelBulanAktif = computed(() => {
  if (modeSemua.value || !bulan.value) return 'Pilih bulan'
  const [y, m] = String(bulan.value).split('-')
  if (!y || !m) return bulan.value
  const d = new Date(Number(y), Number(m) - 1, 1)
  if (Number.isNaN(d.getTime())) return bulan.value
  return d.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })
})

function currentBulan() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

function pilihSemua() {
  modeSemua.value = true
  load()
}

async function bukaPickerBulan() {
  modeSemua.value = false
  if (!bulan.value) bulan.value = currentBulan()
  await nextTick()
  const el = bulanInput.value
  if (!el) {
    load()
    return
  }
  try {
    if (typeof el.showPicker === 'function') {
      el.showPicker()
    } else {
      el.focus()
      el.click()
    }
  } catch {
    el.focus()
    el.click()
  }
  // jika browser tidak memicu change (sudah nilai sama), tetap reload filter bulan
  load()
}

function onBulanChange() {
  modeSemua.value = false
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
    limit: 200,
  }
  if (!modeSemua.value && bulan.value) {
    params.bulan = bulan.value
  }
  const res = await listPembayaran(params)
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat riwayat.'
    items.value = []
    return
  }
  items.value = res.data || []
}

onMounted(load)
</script>
