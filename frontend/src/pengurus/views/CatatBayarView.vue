<template>
  <div class="pb-[calc(72px+env(safe-area-inset-bottom,0px))]">
    <AppBackHeader title="Konfirmasi pembayaran" />

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <p class="text-[13px] text-[var(--mut)] m-0 mb-3 px-0.5">{{ labelKeluarga }}</p>

      <h2 class="text-[15px] font-bold m-0 mb-2">Pilih tagihan</h2>
      <div
        class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] shadow-[var(--sh)] mb-4"
        style="padding: 5px"
      >
        <div
          class="flex items-center justify-between px-3"
          style="height: 30px; border-radius: 15px; background: rgba(34, 184, 99, 0.14)"
        >
          <span class="text-[12px] font-semibold text-[var(--gm)]">Jenis Pembayaran</span>
          <span class="text-[12px] font-semibold text-[var(--gm)]">Biaya</span>
        </div>

        <EmptyState v-if="!tagihanTerbuka.length" title="Tidak ada tagihan terbuka" />
        <label
          v-for="t in tagihanTerbuka"
          :key="t.id"
          class="flex items-center gap-3 py-2.5 cursor-pointer"
          style="padding-left: 10px; padding-right: 10px"
        >
          <input
            v-model="checked"
            type="checkbox"
            class="w-5 h-5 rounded accent-[var(--g)] shrink-0"
            :value="t.id"
          />
          <div
            class="w-9 h-9 rounded-full grid place-items-center shrink-0"
            :style="{ background: iconMeta(t.jenis).bg, color: iconMeta(t.jenis).color }"
          >
            <component :is="iconMeta(t.jenis).icon" :size="16" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-[14px] font-semibold m-0 truncate">{{ labelTagihan(t) }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">{{ t.periode || '—' }}</p>
          </div>
          <span class="text-[14px] font-bold tabular-nums shrink-0">{{ formatRp(t.sisa ?? t.nominal) }}</span>
        </label>

        <div v-if="tagihanTerbuka.length" class="flex justify-end" style="padding: 2px 0 4px">
          <div
            class="inline-flex items-center justify-between gap-4 px-3 min-w-[48%]"
            style="height: 30px; border-radius: 15px; background: rgba(245, 158, 11, 0.16)"
          >
            <span class="text-[12px] font-bold" style="color: #B45309">Total</span>
            <span class="text-[13px] font-extrabold tabular-nums" style="color: #B45309">
              {{ formatRp(totalPilih) }}
            </span>
          </div>
        </div>
      </div>

      <p class="text-[12px] text-[var(--mut)] m-0 mb-2 px-0.5">
        Pembayaran dicatat sebagai <b class="text-[var(--text)]">tunai</b> (konfirmasi pengurus).
      </p>

      <p v-if="saveErr" class="text-[13px] text-red-600 text-center py-2">{{ saveErr }}</p>
    </template>

    <div
      v-if="!loading && !err && tagihanTerbuka.length"
      class="fixed bottom-0 left-0 right-0 z-30 pointer-events-none"
    >
      <div class="max-w-[560px] mx-auto px-4 pt-2 pb-[max(12px,env(safe-area-inset-bottom,0px))]">
        <button
          type="button"
          class="pointer-events-auto w-full min-h-[48px] rounded-full font-bold transition active:scale-[0.98] disabled:opacity-50"
          :class="totalPilih > 0 ? 'bg-[var(--btn)] text-[var(--btn-fg)]' : 'bg-[var(--card2)] text-[var(--mut)]'"
          :disabled="saving || totalPilih < 1"
          @click="onKonfirmasi"
        >
          {{ saving ? 'Menyimpan…' : 'Konfirmasi' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Banknote, Shield, Sparkles } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { useToast } from '@shared/composables/useToast.js'
import { ringkasanKeluarga, catatPembayaran } from '@shared/services/keuangan.js'
import { listKeluarga } from '@shared/services/warga.js'

const route = useRoute()
const router = useRouter()
const { success, error: toastError } = useToast()

const keluargaId = computed(() => Number(route.params.id))
const loading = ref(true)
const err = ref('')
const labelKeluarga = ref('—')
const tagihan = ref([])
const checked = ref([])
const saving = ref(false)
const saveErr = ref('')

const tagihanTerbuka = computed(() =>
  (tagihan.value || []).filter((t) => Number(t.sisa ?? t.nominal) > 0)
)

const totalPilih = computed(() => {
  const set = new Set(checked.value.map(Number))
  return tagihanTerbuka.value
    .filter((t) => set.has(Number(t.id)))
    .reduce((s, t) => s + (Number(t.sisa ?? t.nominal) || 0), 0)
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

async function load() {
  loading.value = true
  err.value = ''
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
  tagihan.value = ring.data?.tagihan || []
  checked.value = tagihanTerbuka.value.map((t) => t.id)
  const k = (list.data || []).find((x) => x.id === id)
  labelKeluarga.value = k
    ? `${k.alamat || ''} · ${k.nama || ''}`.replace(/^ · | · $/g, '')
    : `Keluarga #${id}`
  loading.value = false
}

async function onKonfirmasi() {
  saveErr.value = ''
  const n = totalPilih.value
  if (n < 1) {
    saveErr.value = 'Pilih minimal satu tagihan.'
    return
  }
  saving.value = true
  const res = await catatPembayaran({
    keluarga_id: keluargaId.value,
    nominal: n,
    metode: 'tunai',
    tanggal: new Date().toISOString().slice(0, 10),
    tagihan_ids: checked.value.map(Number),
  })
  saving.value = false
  if (!res.ok) {
    saveErr.value = res.error || 'Gagal menyimpan.'
    toastError?.(saveErr.value)
    return
  }
  success?.('Pembayaran dikonfirmasi')
  router.replace('/keuangan/tagihan/' + keluargaId.value)
}

onMounted(load)
</script>
