<template>
  <div class="pb-[calc(72px+env(safe-area-inset-bottom,0px))]">
    <AppBackHeader title="Detail tagihan" />

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div
        class="w-full rounded-[20px] p-5 mb-4 relative overflow-hidden shadow-[var(--sh)]"
        :style="heroStyle"
      >
        <div class="relative z-10 flex items-start gap-3">
          <div class="w-11 h-11 rounded-full grid place-items-center shrink-0 bg-white/20 text-white">
            <Wallet :size="20" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-[13px] font-semibold m-0 text-white/90">{{ labelKeluarga }}</p>
            <p class="text-[28px] font-extrabold tracking-tight m-0 mt-1 leading-none text-white">
              {{ total <= 0 ? 'Lunas' : formatRp(total) }}
            </p>
            <p class="text-[13px] m-0 mt-2 text-white/85">
              {{ total <= 0 ? 'Tidak ada tagihan terbuka' : 'Total tagihan belum lunas' }}
            </p>
          </div>
        </div>
        <div class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none" />
      </div>

      <h2 class="text-[15px] font-bold m-0 mb-2">Rincian tagihan</h2>
      <div
        class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] shadow-[var(--sh)] mb-5"
        style="padding: 5px"
      >
        <div
          class="flex items-center justify-between px-3"
          style="height: 30px; border-radius: 15px; background: rgba(34, 184, 99, 0.14)"
        >
          <span class="text-[12px] font-semibold text-[var(--gm)]">Jenis</span>
          <span class="text-[12px] font-semibold text-[var(--gm)]">Sisa</span>
        </div>
        <EmptyState v-if="!tagihan.length" title="Tidak ada tagihan" />
        <div
          v-for="t in tagihan"
          :key="t.id"
          class="flex items-center gap-3 py-2.5"
          style="padding-left: 10px; padding-right: 10px"
        >
          <div
            class="w-9 h-9 rounded-full grid place-items-center shrink-0"
            :style="{ background: iconMeta(t.jenis).bg, color: iconMeta(t.jenis).color }"
          >
            <component :is="iconMeta(t.jenis).icon" :size="16" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-[14px] font-semibold m-0 truncate">{{ labelTagihan(t) }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">
              {{ t.periode || '—' }}
              <span v-if="Number(t.sisa ?? t.nominal) <= 0" class="text-[var(--g)] font-semibold"> · Lunas</span>
            </p>
            <div class="flex gap-2 mt-1">
              <button
                v-if="t.jenis === 'denda_ronda' && Number(t.sisa ?? t.nominal) > 0"
                type="button"
                class="text-[12px] font-semibold text-red-600"
                @click="$router.push('/keuangan/tagihan/' + keluargaId + '/batal-denda')"
              >Batalkan denda</button>
              <button
                v-if="t.jenis === 'khusus'"
                type="button"
                class="text-[12px] font-semibold text-[var(--g)]"
                @click="$router.push('/keuangan/tagihan/' + keluargaId + '/khusus/' + t.id)"
              >Edit</button>
            </div>
          </div>
          <span
            class="text-[14px] font-bold tabular-nums shrink-0"
            :class="Number(t.sisa ?? t.nominal) <= 0 ? 'text-[var(--g)]' : ''"
          >
            {{ Number(t.sisa ?? t.nominal) <= 0 ? 'Lunas' : formatRp(t.sisa ?? t.nominal) }}
          </span>
        </div>
      </div>

      <h2 class="text-[15px] font-bold m-0 mb-2">Riwayat pembayaran</h2>
      <EmptyState v-if="!bayar.length" title="Belum ada pembayaran" />
      <div v-else class="space-y-0 mb-6 px-1">
        <div
          v-for="p in bayar"
          :key="p.id"
          class="flex items-center gap-3 py-3"
        >
          <div class="w-10 h-10 rounded-full grid place-items-center shrink-0 bg-emerald-500/15 text-emerald-600">
            <Check :size="18" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-bold text-[15px] m-0 leading-tight">{{ formatRp(p.nominal) }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">
              {{ formatTanggal(p.tanggal_bayar) }} · {{ p.metode || 'tunai' }}
            </p>
          </div>
          <button
            type="button"
            class="text-[12px] font-semibold text-red-600 shrink-0"
            :disabled="busyId === p.id"
            @click="onBatalBayar(p)"
          >{{ busyId === p.id ? '…' : 'Batal' }}</button>
        </div>
      </div>

      <p v-if="msg" class="text-[13px] text-center mb-3" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </template>

    <div
      v-if="!loading && !err && total > 0"
      class="fixed bottom-0 left-0 right-0 z-30 pointer-events-none"
    >
      <div class="max-w-[560px] mx-auto px-4 pt-2 pb-[max(12px,env(safe-area-inset-bottom,0px))]">
        <button
          type="button"
          class="pointer-events-auto w-full min-h-[48px] rounded-full font-bold transition bg-[var(--btn)] text-[var(--btn-fg)] active:scale-[0.98]"
          @click="$router.push('/keuangan/tagihan/' + keluargaId + '/bayar')"
        >
          Konfirmasi pembayaran
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Wallet, Check, Banknote, Shield, Sparkles } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { ringkasanKeluarga, batalkanPembayaran } from '@shared/services/keuangan.js'
import { listKeluarga } from '@shared/services/warga.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const { success, error: toastError } = useToast()
const keluargaId = computed(() => Number(route.params.id))
const loading = ref(true)
const err = ref('')
const labelKeluarga = ref('—')
const total = ref(0)
const tagihan = ref([])
const bayar = ref([])
const busyId = ref(0)
const msg = ref('')
const msgOk = ref(true)

const heroStyle = computed(() => {
  if (total.value <= 0) {
    return {
      background:
        'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)',
    }
  }
  return {
    background:
      'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #F59E0B, #D97706 55%, #B45309)',
  }
})

function iconMeta(jenis) {
  if (jenis === 'denda_ronda') return { icon: Shield, bg: 'rgba(239,68,68,.15)', color: '#DC2626' }
  if (jenis === 'khusus') return { icon: Sparkles, bg: 'rgba(168,85,247,.15)', color: '#7C3AED' }
  return { icon: Banknote, bg: 'rgba(34,184,99,.15)', color: '#0B8442' }
}

function labelTagihan(t) {
  if (t.jenis === 'kas') return 'Iuran kas' + (t.periode ? ` ${t.periode}` : '')
  if (t.jenis === 'denda_ronda') return 'Denda ronda'
  if (t.jenis === 'khusus') return t.nama || t.nama_khusus || 'Iuran khusus'
  return t.jenis || 'Tagihan'
}

function formatTanggal(t) {
  if (!t) return ''
  const d = new Date(t + (String(t).length === 10 ? 'T00:00:00' : ''))
  if (Number.isNaN(d.getTime())) return t
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function load() {
  loading.value = true
  err.value = ''
  msg.value = ''
  const id = keluargaId.value
  if (!id) {
    err.value = 'Keluarga tidak valid.'
    loading.value = false
    return
  }
  const [ring, list] = await Promise.all([
    ringkasanKeluarga(id),
    listKeluarga({ status: 'aktif' }),
  ])
  if (!ring.ok) {
    err.value = ring.error || 'Gagal memuat tagihan.'
    loading.value = false
    return
  }
  total.value = ring.data?.total ?? 0
  tagihan.value = ring.data?.tagihan || []
  bayar.value = (ring.data?.pembayaran || []).slice().reverse()
  const k = (list.data || []).find((x) => x.id === id)
  labelKeluarga.value = k
    ? `${k.alamat || ''} · ${k.nama || ''}`.replace(/^ · | · $/g, '')
    : `Keluarga #${id}`
  loading.value = false
}

async function onBatalBayar(p) {
  if (!confirm('Batalkan pembayaran ini?')) return
  busyId.value = p.id
  msg.value = ''
  const res = await batalkanPembayaran(p.id)
  busyId.value = 0
  if (!res.ok) {
    msg.value = res.error || 'Gagal membatalkan.'
    msgOk.value = false
    toastError?.(msg.value)
    return
  }
  msg.value = 'Pembayaran dibatalkan.'
  msgOk.value = true
  success?.('Pembayaran dibatalkan')
  await load()
}

onMounted(load)
</script>
