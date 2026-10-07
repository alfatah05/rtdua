<template>
  <div>
    <AppBackHeader title="Catat pembayaran" />

    <div v-if="loading" class="text-[13px] text-[var(--mut)] py-8 text-center">Memuat…</div>
    <div v-else-if="err" class="text-[13px] text-red-600 py-4 text-center">{{ err }}</div>
    <template v-else>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 mb-4">
        <p class="text-[13px] text-[var(--mut)] m-0">Keluarga</p>
        <p class="font-bold text-[15px] m-0">{{ labelKeluarga }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-1">
          Total tagihan:
          <b :class="totalTagihan < 0 ? 'text-[var(--g)]' : ''">
            {{ totalTagihan < 0 ? 'Kelebihan ' + formatRp(-totalTagihan) : formatRp(totalTagihan) }}
          </b>
        </p>
      </div>

      <form class="space-y-4" @submit.prevent="onSubmit">
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal diterima</label>
          <input
            v-model="nominal"
            type="text"
            inputmode="numeric"
            placeholder="50000"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]"
          />
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Metode</label>
          <div class="flex gap-2">
            <button
              type="button"
              class="flex-1 min-h-[44px] rounded-full text-[13px] font-semibold border"
              :class="metode === 'tunai' ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
              @click="metode = 'tunai'"
            >Tunai</button>
            <button
              type="button"
              class="flex-1 min-h-[44px] rounded-full text-[13px] font-semibold border"
              :class="metode === 'transfer' ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
              @click="metode = 'transfer'"
            >Transfer</button>
          </div>
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal</label>
          <input v-model="tanggal" type="date" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Catatan (opsional)</label>
          <input
            v-model="catatan"
            type="text"
            placeholder="Opsional"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          />
        </div>

        <div v-if="showPreview && preview" class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
          <p class="text-[13px] font-bold mb-2">Pratinjau potongan</p>
          <div class="space-y-1 text-[14px]">
            <div v-for="(p, i) in preview.potongan" :key="i" class="flex justify-between gap-2">
              <span>{{ labelJenis[p.jenis] || p.jenis }} {{ p.periode }}</span>
              <span class="font-bold text-[var(--g)]">− {{ formatRp(p.jumlah) }}</span>
            </div>
            <div v-if="preview.sisaBayar > 0" class="flex justify-between text-[var(--mut)] pt-1">
              <span>Kelebihan bayar</span>
              <span class="font-bold">{{ formatRp(preview.sisaBayar) }}</span>
            </div>
            <p v-if="!preview.potongan?.length && !preview.sisaBayar" class="text-[var(--mut)] m-0">Tidak ada tagihan terpotong.</p>
          </div>
        </div>

        <p v-if="saveErr" class="text-[13px] text-red-600 text-center m-0">{{ saveErr }}</p>

        <button
          type="submit"
          class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-60"
          :disabled="saving"
        >
          {{ saving ? 'Menyimpan…' : (showPreview ? 'Simpan pembayaran' : 'Lihat pratinjau') }}
        </button>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { formatRp } from '@shared/utils/format.js'
import { useToast } from '@shared/composables/useToast.js'
import { ringkasanKeluarga, pratinjauAlokasi, catatPembayaran } from '@shared/services/keuangan.js'
import { listKeluarga } from '@shared/services/warga.js'

const route = useRoute()
const router = useRouter()
const { success, error: toastError } = useToast()

const keluargaId = computed(() => Number(route.params.id))
const loading = ref(true)
const err = ref('')
const labelKeluarga = ref('—')
const totalTagihan = ref(0)
const tagihan = ref([])

const nominal = ref('')
const metode = ref('tunai')
const tanggal = ref(new Date().toISOString().slice(0, 10))
const catatan = ref('')
const showPreview = ref(false)
const preview = ref(null)
const saving = ref(false)
const saveErr = ref('')

const labelJenis = { kas: 'Kas', denda_ronda: 'Denda ronda', khusus: 'Iuran khusus' }

function parseNominal() {
  return parseInt(String(nominal.value).replace(/\D/g, ''), 10) || 0
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
  totalTagihan.value = ring.data?.total ?? 0
  tagihan.value = ring.data?.tagihan || []
  const k = (list.data || []).find((x) => x.id === id)
  labelKeluarga.value = k
    ? `${k.alamat || ''} · ${k.nama || ''}`.replace(/^ · | · $/g, '')
    : `Keluarga #${id}`
  loading.value = false
}

async function onSubmit() {
  saveErr.value = ''
  const n = parseNominal()
  if (n < 1) {
    saveErr.value = 'Nominal wajib diisi.'
    return
  }
  if (!showPreview.value) {
    const res = await pratinjauAlokasi(keluargaId.value, n)
    if (!res.ok) {
      saveErr.value = res.error || 'Gagal pratinjau.'
      return
    }
    preview.value = res.data
    showPreview.value = true
    return
  }
  saving.value = true
  const res = await catatPembayaran({
    keluarga_id: keluargaId.value,
    nominal: n,
    metode: metode.value,
    tanggal: tanggal.value,
    catatan: catatan.value || null,
  })
  saving.value = false
  if (!res.ok) {
    saveErr.value = res.error || 'Gagal menyimpan.'
    toastError?.(saveErr.value)
    return
  }
  success('Pembayaran tersimpan')
  router.replace('/keuangan/tagihan/' + keluargaId.value)
}

onMounted(load)
</script>
