<template>
  <div>
    <div
      class="rounded-[20px] p-5 text-white mb-5 relative overflow-hidden"
      style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)"
    >
      <p class="text-[13px] font-semibold text-white/90">Saldo kas</p>
      <p class="text-[32px] font-extrabold mt-1 tracking-tight">
        {{ loading ? '…' : formatRp(saldoKas) }}
      </p>
      <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
    </div>

    <div class="flex items-center justify-between mb-1 px-2">
      <h2 class="text-[17px] font-bold m-0">Riwayat kas</h2>
    </div>

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { portalKas } from '@shared/services/keuangan.js'

const loading = ref(true)
const err = ref('')
const saldoKas = ref(0)
const kasItems = ref([])

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
  loading.value = false
  if (!kas.ok) {
    err.value = kas.error || 'Gagal memuat kas'
    return
  }
  saldoKas.value = Number(kas.data?.saldo ?? 0)
  kasItems.value = kas.data?.items || []
})
</script>
