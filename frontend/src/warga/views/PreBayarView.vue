<template>
  <div>
    <AppBackHeader title="Bayar tagihan" />

    <p v-if="loading" class="text-[13px] text-[var(--mut)]">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600">{{ err }}</p>

    <template v-else>
      <section class="mb-4">
        <h2 class="text-[15px] font-bold m-0 mb-2">Pilih tagihan</h2>
        <div v-if="tagihanTerbuka.length" class="space-y-2">
          <label
            v-for="t in tagihanTerbuka"
            :key="t.id"
            class="flex items-start gap-3 p-3 rounded-2xl border border-[var(--line)] bg-white cursor-pointer"
          >
            <input v-model="checked" type="checkbox" class="mt-1" :value="t.id" />
            <div class="flex-1 min-w-0">
              <p class="m-0 text-[14px] font-semibold text-[var(--text)]">{{ labelTagihan(t) }}</p>
              <p class="m-0 text-[12px] text-[var(--mut)]">Sisa {{ formatRp(t.sisa ?? t.nominal) }}</p>
            </div>
          </label>
        </div>
        <p v-else class="text-[13px] text-[var(--mut)] m-0">Tidak ada tagihan terbuka</p>
      </section>

      <section class="mb-4">
        <h2 class="text-[15px] font-bold m-0 mb-2">Metode pembayaran</h2>
        <div class="space-y-2">
          <label
            v-for="r in rekening"
            :key="r.id"
            class="flex items-center gap-3 p-3 rounded-2xl border border-[var(--line)] bg-white cursor-pointer"
            :class="selectedId === r.id ? 'ring-2 ring-[var(--g)]' : ''"
          >
            <input v-model="selectedId" type="radio" class="accent-[var(--g)]" :value="r.id" />
            <div class="flex-1 min-w-0">
              <p class="m-0 text-[14px] font-semibold">{{ r.nama }}</p>
              <p class="m-0 text-[12px] text-[var(--mut)] uppercase">{{ r.jenis }}</p>
            </div>
          </label>
        </div>
      </section>

      <div class="sticky bottom-0 -mx-4 px-4 py-3 bg-[var(--bg)] border-t border-[var(--line)]">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[13px] text-[var(--mut)]">Total dipilih</span>
          <span class="text-[16px] font-extrabold text-[var(--g)]">{{ formatRp(totalChecked) }}</span>
        </div>
        <button
          type="button"
          class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50"
          :disabled="!canBayar"
          @click="lanjut"
        >
          Lanjut
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalRingkasan, portalListRekening } from '@shared/api/warga.js'

const DRAFT_KEY = 'warga_bayar_draft'
const router = useRouter()
const loading = ref(true)
const err = ref('')
const tagihan = ref([])
const checked = ref([])
const rekening = ref([])
const selectedId = ref(null)

const tagihanTerbuka = computed(() =>
  (tagihan.value || []).filter((t) => Number(t.sisa ?? t.nominal ?? 0) > 0)
)

const totalChecked = computed(() =>
  tagihanTerbuka.value
    .filter((t) => checked.value.includes(t.id))
    .reduce((s, t) => s + Number(t.sisa ?? t.nominal ?? 0), 0)
)

const canBayar = computed(() => checked.value.length > 0 && selectedId.value && totalChecked.value > 0)

function formatRp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}

function labelTagihan(t) {
  if (t.jenis === 'kas') return `Kas ${t.periode || ''}`
  if (t.jenis === 'denda_ronda') return `Denda ronda ${t.periode || ''}`
  return t.nama || t.jenis || 'Tagihan'
}

function lanjut() {
  const metode = rekening.value.find((r) => r.id === selectedId.value)
  if (!metode) return
  const items = tagihanTerbuka.value.filter((t) => checked.value.includes(t.id))
  const draft = {
    tagihanIds: items.map((t) => t.id),
    items: items.map((t) => ({ id: t.id, label: labelTagihan(t), sisa: Number(t.sisa ?? t.nominal ?? 0) })),
    total: totalChecked.value,
    rekening: metode,
  }
  sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft))
  router.replace('/bayar/detail')
}

onMounted(async () => {
  const [res, rek] = await Promise.all([portalRingkasan(), portalListRekening()])
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  tagihan.value = res.data?.tagihan || []
  if (rek.ok) {
    rekening.value = Array.isArray(rek.data) ? rek.data : []
    const q = rekening.value.find((r) => r.jenis === 'qris')
    if (q) selectedId.value = q.id
    else if (rekening.value[0]) selectedId.value = rekening.value[0].id
  }
})
</script>
