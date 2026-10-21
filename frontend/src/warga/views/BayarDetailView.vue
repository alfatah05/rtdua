<template>
  <div>
    <AppBackHeader title="Pembayaran" />

    <template v-if="!draft">
      <p class="text-[13px] text-[var(--mut)] text-center py-8">Data pembayaran tidak ditemukan.</p>
      <button type="button" class="w-full min-h-12 rounded-full bg-[var(--g)] text-white font-bold" @click="$router.replace('/bayar')">
        Kembali pilih tagihan
      </button>
    </template>

    <template v-else>
      <div
        class="w-full rounded-[20px] p-5 mb-4 relative overflow-hidden shadow-[var(--sh)]"
        :style="heroStyle"
      >
        <div class="relative z-10">
          <p class="text-[13px] font-semibold m-0 text-white/90">Total pembayaran</p>
          <p class="text-[28px] font-extrabold tracking-tight m-0 mt-1 leading-none text-white">{{ rp(draft.total) }}</p>
          <p class="text-[13px] m-0 mt-2 text-white/85">{{ draft.rekening?.nama || '—' }}</p>
        </div>
        <div class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"></div>
      </div>

      <!-- QRIS -->
      <div v-if="draft.rekening?.jenis === 'qris'" class="mb-4">
        <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] p-4 shadow-[var(--sh)]">
          <p class="text-[13px] font-semibold text-[var(--mut)] m-0 mb-3 text-center">Scan QRIS di bawah</p>
          <div class="aspect-square max-w-[280px] mx-auto rounded-[16px] bg-[var(--search)] overflow-hidden grid place-items-center">
            <img v-if="qrisUrl" :src="qrisUrl" alt="QRIS" class="w-full h-full object-contain" />
            <p v-else class="text-[13px] text-[var(--mut)] text-center px-4">Gambar QRIS belum diunggah pengurus</p>
          </div>
          <p v-if="draft.rekening.atas_nama" class="text-[13px] text-center m-0 mt-3 text-[var(--mut)]">
            a.n. {{ draft.rekening.atas_nama }}
          </p>
        </div>
      </div>

      <!-- Bank / e-wallet -->
      <div v-else class="mb-4 rounded-[20px] border border-[var(--line)] bg-[var(--card)] p-4 shadow-[var(--sh)]">
        <p class="text-[13px] font-semibold text-[var(--mut)] m-0 mb-1">{{ draft.rekening?.jenis === 'ewallet' ? 'E-wallet' : 'Rekening tujuan' }}</p>
        <p class="text-[17px] font-extrabold m-0">{{ draft.rekening?.nama }}</p>
        <div class="flex items-center gap-2 mt-3">
          <p class="flex-1 text-[18px] font-bold m-0 tracking-wide">{{ draft.rekening?.nomor || '—' }}</p>
          <button
            type="button"
            class="min-h-10 px-4 rounded-full bg-[var(--card2)] text-[13px] font-bold"
            @click="salin(draft.rekening?.nomor)"
          >
            Salin
          </button>
        </div>
        <p v-if="draft.rekening?.atas_nama" class="text-[13px] m-0 mt-2 text-[var(--mut)]">
          a.n. {{ draft.rekening.atas_nama }}
        </p>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Atas nama pengirim</label>
          <input
            v-model="namaPengirim"
            type="text"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
            placeholder="Nama di rekening / e-wallet"
            autocomplete="name"
          />
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Bukti pembayaran</label>
          <div v-if="preview" class="h-40 rounded-[16px] overflow-hidden mb-2 bg-[var(--search)]">
            <img :src="preview" class="w-full h-full object-contain" alt="" />
          </div>
          <label class="inline-flex min-h-[44px] px-4 rounded-full bg-[var(--card2)] font-semibold items-center cursor-pointer">
            {{ uploading ? 'Mengunggah…' : 'Pilih foto bukti' }}
            <input type="file" accept="image/*" class="hidden" :disabled="uploading" @change="onBukti" />
          </label>
        </div>
        <button
          type="submit"
          class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50"
          :disabled="!canSubmit || saving || uploading"
        >
          {{ saving ? 'Mengirim…' : 'Konfirmasi pembayaran' }}
        </button>
        <p v-if="msg" class="text-[13px] text-center text-red-600">{{ msg }}</p>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalAjukanTransfer } from '@shared/services/keuangan.js'
import { uploadGambar, mediaUrl } from '@shared/services/upload.js'
import { useToast } from '@shared/composables/useToast.js'

const DRAFT_KEY = 'rtdua_bayar_draft'
const RECEIPT_KEY = 'rtdua_bayar_bukti'
const router = useRouter()
const { show } = useToast()

const draft = ref(null)
const namaPengirim = ref('')
const buktiPath = ref('')
const preview = ref('')
const uploading = ref(false)
const saving = ref(false)
const msg = ref('')

const heroStyle = {
  background:
    'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)',
}

const qrisUrl = computed(() => {
  const f = draft.value?.rekening?.qris_file
  return f ? mediaUrl(f) : ''
})

const canSubmit = computed(() => namaPengirim.value.trim() && buktiPath.value)

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}

function salin(text) {
  if (!text) return
  navigator.clipboard?.writeText(String(text)).then(
    () => show('Nomor disalin'),
    () => show('Gagal menyalin')
  )
}

async function onBukti(e) {
  const file = e.target.files?.[0]
  e.target.value = ''
  if (!file) return
  uploading.value = true
  msg.value = ''
  const res = await uploadGambar(file, 'bukti')
  uploading.value = false
  if (!res.ok) {
    msg.value = res.error || 'Gagal unggah'
    return
  }
  buktiPath.value = res.data?.path || res.data?.file || ''
  preview.value = res.data?.url || mediaUrl(buktiPath.value)
}

async function submit() {
  if (!canSubmit.value || !draft.value) return
  saving.value = true
  msg.value = ''
  const r = draft.value.rekening
  const payload = {
    nominal: draft.value.total,
    nominal_diajukan: draft.value.total,
    nama_pengirim: namaPengirim.value.trim(),
    bank_pengirim: r?.nama || null,
    bukti_file: buktiPath.value,
    metode_rekening: r ? `${r.jenis}:${r.nama}` : null,
    rekening_id: r?.id || null,
    tagihan_ids: draft.value.tagihanIds || [],
  }
  const res = await portalAjukanTransfer(payload)
  saving.value = false
  if (!res.ok) {
    msg.value = res.error || 'Gagal mengirim'
    return
  }

  const apiData = res.data || {}
  const receipt = {
    id: apiData.id || null,
    nominal: draft.value.total,
    nama_pengirim: namaPengirim.value.trim(),
    rekening: draft.value.rekening || null,
    items: draft.value.items || [],
    diajukan_pada: apiData.diajukan_pada || new Date().toISOString(),
  }
  try {
    sessionStorage.setItem(RECEIPT_KEY, JSON.stringify(receipt))
  } catch (e) {}
  sessionStorage.removeItem(DRAFT_KEY)
  router.replace('/bayar/bukti')
}

onMounted(() => {
  try {
    const raw = sessionStorage.getItem(DRAFT_KEY)
    draft.value = raw ? JSON.parse(raw) : null
  } catch {
    draft.value = null
  }
})
</script>
