<template>
  <div>
    <AppMainHeader />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <button
        type="button"
        class="w-full bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 mb-4 text-left shadow-[var(--sh)] active:scale-[0.99] transition"
        @click="$router.push('/rincian-iuran')"
      >
        <p class="text-[13px] text-[var(--mut)] m-0">{{ iuranLabel }}</p>
        <p class="text-[24px] font-extrabold m-0 mt-1">{{ iuranNominal }}</p>
        <p class="text-[13px] text-[var(--g)] font-semibold m-0 mt-1">Lihat rincian →</p>
      </button>

      <button
        type="button"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-5"
        @click="$router.push('/transfer')"
      >
        Saya sudah transfer
      </button>

      <div v-if="permintaan.length" class="mb-5">
        <h2 class="text-[15px] font-bold mb-2">Status transfer</h2>
        <div class="space-y-2">
          <div
            v-for="p in permintaan"
            :key="p.id"
            class="rounded-[16px] p-3.5 border"
            :class="p.status === 'menunggu' ? 'bg-amber-500/10 border-amber-500/20' : 'bg-[var(--card)] border-[var(--line)]'"
          >
            <p class="font-bold m-0 text-[14px]">{{ labelStatus(p.status) }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0">
              {{ rp(p.nominal_diajukan ?? p.nominal) }}
              <span v-if="p.diajukan_pada"> · {{ p.diajukan_pada }}</span>
            </p>
            <p v-if="p.alasan_tolak" class="text-[12px] text-red-600 m-0 mt-1">{{ p.alasan_tolak }}</p>
          </div>
        </div>
      </div>

      <h2 class="text-[15px] font-bold mb-1">Kas RT</h2>
      <p class="text-[13px] text-[var(--mut)] m-0 mb-2">Transparansi saldo dan mutasi</p>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 mb-3">
        <p class="text-[13px] text-[var(--mut)] m-0">Saldo kas</p>
        <p class="text-[22px] font-extrabold m-0 mt-1">{{ rp(saldoKas) }}</p>
      </div>
      <div class="space-y-2">
        <div
          v-for="t in kasItems"
          :key="t.id"
          class="flex justify-between gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5"
        >
          <div class="min-w-0">
            <p class="font-bold m-0 text-[14px]">{{ t.keterangan || t.kategori || (t.tipe === 'masuk' ? 'Masuk' : 'Keluar') }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0">{{ t.tanggal || '—' }} · {{ t.tipe }}</p>
          </div>
          <span class="font-bold shrink-0" :class="t.tipe === 'masuk' ? 'text-[var(--g)]' : 'text-red-600'">
            {{ t.tipe === 'masuk' ? '+' : '−' }}{{ rp(t.nominal) }}
          </span>
        </div>
        <p v-if="!kasItems.length" class="text-[13px] text-[var(--mut)] text-center py-3">Belum ada mutasi kas</p>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { portalRingkasan, portalStatusPermintaan, portalKas } from '@shared/services/keuangan.js'

const loading = ref(true)
const err = ref('')
const iuranLabel = ref('Iuran')
const iuranNominal = ref('—')
const permintaan = ref([])
const saldoKas = ref(0)
const kasItems = ref([])

function rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID') }
function labelStatus(s) {
  if (s === 'menunggu') return 'Menunggu konfirmasi'
  if (s === 'dikonfirmasi') return 'Dikonfirmasi'
  if (s === 'ditolak') return 'Ditolak'
  return s || '—'
}

onMounted(async () => {
  const [ring, pm, kas] = await Promise.all([
    portalRingkasan(),
    portalStatusPermintaan(),
    portalKas(),
  ])
  loading.value = false
  if (!ring.ok && !pm.ok && !kas.ok) {
    err.value = ring.error || pm.error || kas.error || 'Gagal memuat'
    return
  }
  if (ring.ok && ring.data) {
    const total = Number(ring.data.total ?? 0)
    if (total > 0) {
      iuranLabel.value = 'Belum lunas'
      iuranNominal.value = rp(total)
    } else if (total < 0) {
      iuranLabel.value = 'Kelebihan bayar'
      iuranNominal.value = rp(-total)
    } else {
      iuranLabel.value = 'Status iuran'
      iuranNominal.value = 'Lunas'
    }
  }
  if (pm.ok) {
    permintaan.value = (Array.isArray(pm.data) ? pm.data : []).slice(0, 5)
  }
  if (kas.ok && kas.data) {
    saldoKas.value = Number(kas.data.saldo ?? 0)
    kasItems.value = kas.data.items || []
  }
})
</script>
