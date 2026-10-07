<template>
  <div>
    <AppMainHeader show-profil />

    <div
      class="rounded-[20px] p-5 text-white mb-4 relative overflow-hidden"
      style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)"
    >
      <p class="text-[13px] font-semibold text-white/90">Saldo kas</p>
      <p class="text-[32px] font-extrabold mt-1 tracking-tight">
        {{ loadingSaldo ? '…' : formatRp(saldo) }}
      </p>
      <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-5 gap-2 text-center mb-5">
      <button
        v-for="a in aksi"
        :key="a.label"
        type="button"
        class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform"
        @click="$router.push(a.to)"
      >
        <span
          class="w-12 h-12 rounded-full grid place-items-center relative"
          :style="{ background: a.bg, color: a.color }"
        >
          <component :is="a.icon" :size="20" />
          <span
            v-if="a.badge"
            class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] rounded-full bg-[var(--g)] text-white text-[10px] font-bold grid place-items-center px-1"
          >{{ a.badge }}</span>
        </span>
        <span class="text-[11px] font-semibold leading-tight">{{ a.label }}</span>
      </button>
    </div>

    <div class="flex items-center justify-between mb-1 px-2">
      <h2 class="text-[17px] font-bold m-0">Riwayat kas</h2>
      <button type="button" class="text-[13px] font-semibold text-[var(--g)]" @click="$router.push('/keuangan/filter-kas')">
        Filter
      </button>
    </div>

    <p v-if="loadingList" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="loadErr" class="text-[13px] text-red-600 text-center py-4">{{ loadErr }}</p>
    <EmptyState v-else-if="!transaksi.length" title="Belum ada transaksi" />
    <div v-else class="px-1 space-y-0.5">
      <button
        v-for="t in transaksi"
        :key="t.id"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/keuangan/kas/' + t.id)"
      >
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ t.keterangan || t.kategori }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ formatTanggal(t.tanggal) }} · {{ t.kategori }}</p>
        </div>
        <span class="font-bold shrink-0" :class="t.tipe === 'masuk' ? 'text-[var(--g)]' : 'text-red-500'">
          {{ t.tipe === 'masuk' ? '+' : '−' }}{{ formatRp(t.nominal) }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { ArrowDownLeft, ArrowUpRight, ClipboardList, FileText, SlidersHorizontal } from 'lucide-vue-next'
import { formatRp } from '@shared/utils/format.js'
import { listKas, listPermintaan } from '@shared/services/keuangan.js'

const saldo = ref(0)
const transaksi = ref([])
const loadingSaldo = ref(true)
const loadingList = ref(true)
const loadErr = ref('')
const badgePermintaan = ref(0)

const aksi = computed(() => [
  { label: 'Kas masuk', icon: ArrowDownLeft, to: '/keuangan/kas-masuk', bg: 'rgba(16,185,129,.18)', color: '#059669' },
  { label: 'Kas keluar', icon: ArrowUpRight, to: '/keuangan/kas-keluar', bg: 'rgba(239,68,68,.15)', color: '#DC2626' },
  { label: 'Iuran', icon: ClipboardList, to: '/keuangan/iuran', bg: 'rgba(16,185,129,.18)', color: '#059669', badge: badgePermintaan.value || undefined },
  { label: 'Laporan', icon: FileText, to: '/keuangan/laporan', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Filter', icon: SlidersHorizontal, to: '/keuangan/filter-kas', bg: 'rgba(107,114,128,.18)', color: '#4B5563' },
])

function formatTanggal(t) {
  if (!t) return ''
  const d = new Date(t + (String(t).length === 10 ? 'T00:00:00' : ''))
  if (Number.isNaN(d.getTime())) return t
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function load() {
  loadingSaldo.value = true
  loadingList.value = true
  loadErr.value = ''
  const [kas, perm] = await Promise.all([
    listKas({}),
    listPermintaan('menunggu'),
  ])
  loadingSaldo.value = false
  loadingList.value = false
  if (!kas.ok) {
    loadErr.value = kas.error || 'Gagal memuat kas.'
    return
  }
  saldo.value = kas.data?.saldo ?? 0
  transaksi.value = kas.data?.items || []
  if (perm.ok) {
    badgePermintaan.value = (perm.data || []).length
  }
}

onMounted(load)
</script>
