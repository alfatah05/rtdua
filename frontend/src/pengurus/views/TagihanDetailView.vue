<template>
  <div>
    <AppBackHeader title="Rincian tagihan" />

    <div v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-8">Memuat…</div>
    <div v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</div>
    <template v-else>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4 text-center">
        <p class="text-[13px] text-[var(--mut)] m-0">{{ labelKeluarga }}</p>
        <p class="text-[28px] font-extrabold mt-1 m-0" :class="total <= 0 ? 'text-[var(--g)]' : ''">
          {{ total < 0 ? 'Kelebihan ' + formatRp(-total) : formatRp(total) }}
        </p>
        <span class="inline-block mt-2 text-[12px] font-bold px-2.5 py-1 rounded-full" :class="statusClass">
          {{ labelStatus }}
        </span>
      </div>

      <h2 class="text-[15px] font-bold mb-2">Tagihan</h2>
      <EmptyState v-if="!tagihan.length" title="Tidak ada tagihan" class="mb-5" />
      <div v-else class="space-y-2 mb-5">
        <div
          v-for="t in tagihan"
          :key="t.id"
          class="flex justify-between items-start gap-2 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4"
        >
          <div class="min-w-0">
            <p class="font-bold text-[15px] m-0">{{ labelJenis[t.jenis] || t.jenis }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0">{{ t.periode }}</p>
          </div>
          <div class="text-right flex-none">
            <span class="font-bold block">{{ formatRp(t.sisa) }}</span>
            <button
              v-if="t.jenis === 'denda_ronda' && Number(t.sisa) > 0"
              type="button"
              class="text-[12px] font-semibold text-red-600 mt-1"
              @click="$router.push('/keuangan/tagihan/' + t.id + '/batal-denda')"
            >Batalkan denda</button>
          </div>
        </div>
      </div>

      <h2 class="text-[15px] font-bold mb-2">Riwayat pembayaran</h2>
      <EmptyState v-if="!bayar.length" title="Belum ada pembayaran" class="mb-6" />
      <div v-else class="space-y-2 mb-6">
        <div
          v-for="p in bayar"
          :key="p.id"
          class="flex justify-between items-start gap-2 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4"
        >
          <div class="min-w-0">
            <p class="font-bold text-[15px] m-0 capitalize">{{ p.metode }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0">{{ p.tanggal_bayar }}</p>
            <p v-if="p.dibatalkan" class="text-[12px] text-red-600 m-0">Dibatalkan</p>
          </div>
          <div class="text-right flex-none">
            <span class="font-bold text-[var(--g)] block">{{ formatRp(p.nominal) }}</span>
            <button
              v-if="!p.dibatalkan"
              type="button"
              class="text-[12px] font-semibold text-red-600 mt-1"
              :disabled="busyId === p.id"
              @click="onBatalBayar(p)"
            >{{ busyId === p.id ? '…' : 'Batalkan' }}</button>
          </div>
        </div>
      </div>

      <div class="space-y-2">
        <button
          type="button"
          class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold"
          @click="$router.push('/keuangan/tagihan/' + keluargaId + '/bayar')"
        >
          Catat pembayaran
        </button>
        <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { ringkasanKeluarga, batalkanPembayaran } from '@shared/services/keuangan.js'
import { listKeluarga } from '@shared/services/warga.js'

const route = useRoute()
const keluargaId = computed(() => Number(route.params.id))
const loading = ref(true)
const err = ref('')
const labelKeluarga = ref('—')
const total = ref(0)
const status = ref('lunas')
const tagihan = ref([])
const bayar = ref([])
const busyId = ref(null)
const msg = ref('')
const msgOk = ref(true)

const labelJenis = { kas: 'Kas', denda_ronda: 'Denda ronda', khusus: 'Iuran khusus' }

const labelStatus = computed(() => {
  if (total.value < 0) return 'Kelebihan bayar'
  if (status.value === 'lunas' || total.value <= 0) return 'Lunas'
  if (status.value === 'menunggak') return 'Menunggak'
  return 'Belum lunas'
})

const statusClass = computed(() => {
  if (total.value < 0 || status.value === 'lunas' || total.value <= 0) return 'bg-[var(--ok)] text-[var(--g)]'
  if (status.value === 'menunggak') return 'bg-red-500/15 text-red-600'
  return 'bg-amber-500/15 text-amber-700'
})

async function load() {
  loading.value = true
  err.value = ''
  const id = keluargaId.value
  const [ring, list] = await Promise.all([
    ringkasanKeluarga(id),
    listKeluarga({ status: 'aktif' }),
  ])
  loading.value = false
  if (!ring.ok) {
    err.value = ring.error || 'Gagal memuat.'
    return
  }
  total.value = ring.data?.total ?? 0
  status.value = ring.data?.status || 'lunas'
  tagihan.value = ring.data?.tagihan || []
  bayar.value = ring.data?.pembayaran || []
  const k = (list.data || []).find((x) => x.id === id)
  labelKeluarga.value = k
    ? `${k.alamat || ''} · ${k.nama || ''}`.replace(/^ · | · $/g, '')
    : `Keluarga #${id}`
}

async function onBatalBayar(p) {
  const alasan = prompt('Alasan batalkan pembayaran?')
  if (alasan === null) return
  if (!String(alasan).trim()) {
    msgOk.value = false
    msg.value = 'Alasan wajib'
    return
  }
  busyId.value = p.id
  msg.value = ''
  const res = await batalkanPembayaran(p.id, String(alasan).trim())
  busyId.value = null
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Pembayaran dibatalkan' : (res.error || 'Gagal')
  if (res.ok) await load()
}

onMounted(load)
</script>
