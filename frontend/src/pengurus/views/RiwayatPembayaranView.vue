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

    <div class="flex gap-2 mb-3 px-1 overflow-x-auto">
      <button
        type="button"
        class="h-9 px-3 rounded-full text-[12px] font-semibold shrink-0 border"
        :class="!bulan ? 'bg-[var(--g)] text-white border-[var(--g)]' : 'bg-[var(--card)] border-[var(--line)]'"
        @click="setBulan('')"
      >
        Semua
      </button>
      <button
        v-for="b in bulanOptions"
        :key="b.value"
        type="button"
        class="h-9 px-3 rounded-full text-[12px] font-semibold shrink-0 border"
        :class="bulan === b.value ? 'bg-[var(--g)] text-white border-[var(--g)]' : 'bg-[var(--card)] border-[var(--line)]'"
        @click="setBulan(b.value)"
      >
        {{ b.label }}
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
        @click="$router.push('/keuangan/tagihan/' + p.keluarga_id)"
      >
        <div
          class="w-10 h-10 rounded-full grid place-items-center shrink-0"
          :class="p.dibatalkan ? 'bg-red-500/15 text-red-600' : 'bg-emerald-500/15 text-emerald-600'"
        >
          <History v-if="p.dibatalkan" :size="18" />
          <Check v-else :size="18" />
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
          <p class="font-bold text-[15px] m-0 tabular-nums" :class="p.dibatalkan ? 'line-through text-[var(--mut)]' : 'text-[var(--g)]'">
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
import { ref, computed, onMounted, watch } from 'vue'
import { Search, Check, History } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { listPembayaran } from '@shared/services/keuangan.js'

const q = ref('')
const bulan = ref(currentBulan())
const includeBatal = ref(false)
const loading = ref(true)
const err = ref('')
const items = ref([])

const bulanOptions = computed(() => {
  const out = []
  const now = new Date()
  for (let i = 0; i < 6; i++) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const value = `${y}-${m}`
    const label = d.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })
    out.push({ value, label })
  }
  return out
})

function currentBulan() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

function setBulan(v) {
  bulan.value = v
  load()
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
  const res = await listPembayaran({
    bulan: bulan.value || undefined,
    q: q.value.trim() || undefined,
    include_batal: includeBatal.value || undefined,
    limit: 200,
  })
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
