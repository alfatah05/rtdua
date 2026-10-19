<template>
  <div>
    <AppBackHeader title="Tagihan khusus" />
    <AppPageSpinner v-if="loading" />
    <template v-else>
      <p class="text-[13px] text-[var(--mut)] m-0 mb-4">
        Buat tagihan khusus hanya untuk keluarga ini. Akan langsung muncul di rincian tagihan warga.
      </p>
      <p v-if="namaKeluarga" class="text-[14px] font-semibold m-0 mb-4">{{ namaKeluarga }}</p>

      <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Judul tagihan</label>
      <input
        v-model="judul"
        type="text"
        maxlength="120"
        placeholder="Contoh: Ganti lampu gang"
        class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none mb-3"
      />

      <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nominal (Rp)</label>
      <input
        v-model="nominal"
        type="text"
        inputmode="numeric"
        placeholder="50000"
        class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none mb-3"
      />

      <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Periode tagihan</label>
      <input
        v-model="periode"
        type="month"
        class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none mb-4"
      />

      <p v-if="err" class="text-[13px] text-red-600 text-center mb-3">{{ err }}</p>

      <button
        type="button"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50"
        :disabled="busy"
        @click="simpan"
      >{{ busy ? 'Menyimpan…' : 'Buat tagihan' }}</button>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { getKeluarga } from '@shared/services/warga.js'
import { buatTagihanKhususKeluarga } from '@shared/services/keuangan.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const id = Number(route.params.id || 0)

const loading = ref(true)
const busy = ref(false)
const err = ref('')
const namaKeluarga = ref('')
const judul = ref('')
const nominal = ref('')
const periode = ref(new Date().toISOString().slice(0, 7))

onMounted(async () => {
  loading.value = true
  const res = await getKeluarga(id)
  loading.value = false
  if (res.ok && res.data) {
    const d = res.data
    const kepala = d.kepala?.nama || d.nama_kepala || d.nama || ''
    const alamat = d.alamat || [d.blok, d.nomor].filter(Boolean).join('-')
    namaKeluarga.value = [kepala, alamat].filter(Boolean).join(' · ') || ('Keluarga #' + id)
  }
})

async function simpan() {
  err.value = ''
  const nama = judul.value.trim()
  const nom = Number(String(nominal.value).replace(/[^\d]/g, ''))
  if (!nama) {
    err.value = 'Judul wajib diisi'
    return
  }
  if (!nom || nom < 1) {
    err.value = 'Nominal tidak valid'
    return
  }
  if (!/^\d{4}-\d{2}$/.test(periode.value)) {
    err.value = 'Periode tidak valid'
    return
  }
  busy.value = true
  const res = await buatTagihanKhususKeluarga(id, {
    nama,
    nominal: nom,
    periode: periode.value,
  })
  busy.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal membuat tagihan'
    toast.error(err.value)
    return
  }
  toast.success('Tagihan khusus dibuat')
  router.replace('/keuangan/tagihan/' + id)
}
</script>
