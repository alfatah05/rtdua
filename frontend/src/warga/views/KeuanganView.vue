<template>
  <div>
    <AppBackHeader title="Keuangan" />

    <!-- Skeleton keuangan -->
    <div v-if="loading" class="animate-pulse" aria-hidden="true">
      <div class="rounded-[20px] bg-[var(--card2)] p-5 mb-5">
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-[var(--line)]/60" />
            <div class="space-y-2">
              <div class="h-3 rounded-full bg-[var(--line)]/60 w-20" />
              <div class="h-6 rounded-full bg-[var(--line)]/60 w-32" />
            </div>
          </div>
          <div class="h-6 w-24 rounded-full bg-[var(--line)]/60" />
        </div>
        <div class="grid grid-cols-2 gap-2 mt-4">
          <div class="h-10 rounded-full bg-[var(--line)]/60" />
          <div class="h-10 rounded-full bg-[var(--line)]/60" />
        </div>
      </div>
      <div class="h-4 rounded-full bg-[var(--card2)] w-28 mb-3 mx-2" />
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
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-11 h-11 rounded-full bg-white/20 text-white grid place-items-center shrink-0">
                <Wallet :size="20" />
              </div>
              <div class="min-w-0">
                <p class="text-[13px] font-semibold text-white/90 m-0">Saldo kas</p>
                <p class="text-[28px] font-extrabold m-0 mt-0.5 tracking-tight leading-none">
                  {{ formatRp(saldoKas) }}
                </p>
              </div>
            </div>
            <span class="text-[12px] font-semibold px-3 py-1 rounded-full bg-white/20 shrink-0">
              {{ labelBulan }}
            </span>
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
        </div>
        <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
      </div>

      <div class="flex items-center justify-between mb-1 px-2">
        <h2 class="text-[17px] font-bold m-0">Riwayat kas</h2>
      </div>

      <p v-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
      <EmptyState v-else-if="!kasItems.length" title="Belum ada transaksi" />
      <div v-else class="px-1 space-y-0.5">
        <div
          v-for="t in kasItems"
          :key="t.id"
          class="w-full flex flex-row items-center gap-3 px-2 py-3"
        >
          <div class="min-w-0 flex-1">
            <p class="font-bold text-[15px] m-0 leading-tight">{{ t.keterangan || t.kategori || (t.tipe === 'masuk' ? 'Masuk' : 'Keluar') }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">
              {{ formatTanggal(t.tanggal) }}
              <span v-if="t.kategori"> · {{ t.kategori }}</span>
            </p>
          </div>
          <span class="font-bold shrink-0" :class="t.tipe === 'masuk' ? 'text-[var(--g)]' : 'text-red-500'">
            {{ t.tipe === 'masuk' ? '+' : '−' }}{{ formatRp(t.nominal) }}
          </span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { ref, computed, onMounted } from 'vue'
import { Wallet, ArrowDownLeft, ArrowUpRight } from 'lucide-vue-next'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { portalKas } from '@shared/services/keuangan.js'

const loading = ref(true)
const err = ref('')
const saldoKas = ref(0)
const kasItems = ref([])

const labelBulan = computed(() => {
  const d = new Date()
  return d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

const ymSekarang = computed(() => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
})

const totalMasuk = computed(() =>
  kasItems.value
    .filter((t) => t.tipe === 'masuk' && String(t.tanggal || '').startsWith(ymSekarang.value))
    .reduce((s, t) => s + Number(t.nominal || 0), 0)
)

const totalKeluar = computed(() =>
  kasItems.value
    .filter((t) => t.tipe === 'keluar' && String(t.tanggal || '').startsWith(ymSekarang.value))
    .reduce((s, t) => s + Number(t.nominal || 0), 0)
)

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
  saldoKas.value = Number(kas.data?.saldo ?? 0)
  kasItems.value = kas.data?.items || []
  loading.value = false
})
</script>
