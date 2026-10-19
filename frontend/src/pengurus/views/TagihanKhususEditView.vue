<template>
  <div>
    <AppBackHeader title="Edit tagihan khusus" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="errLoad" class="text-[13px] text-red-600 text-center py-4">{{ errLoad }}</p>
    <template v-else>
      <p class="text-[13px] text-[var(--mut)] m-0 mb-4">
        Ubah judul, nominal, atau periode. Atau hapus tagihan ini.
      </p>

      <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Judul tagihan</label>
      <input
        v-model="judul"
        type="text"
        maxlength="120"
        class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none mb-3"
      />

      <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nominal (Rp)</label>
      <input
        v-model="nominal"
        type="text"
        inputmode="numeric"
        class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none mb-3"
      />

      <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Periode</label>
      <input
        v-model="periode"
        type="month"
        class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none mb-4"
      />

      <p v-if="err" class="text-[13px] text-red-600 text-center mb-3">{{ err }}</p>

      <button
        type="button"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50 mb-3"
        :disabled="busy"
        @click="simpan"
      >{{ busy ? 'Menyimpan…' : 'Simpan perubahan' }}</button>

      <button
        type="button"
        class="w-full min-h-[48px] rounded-full bg-red-50 text-red-600 font-bold disabled:opacity-50"
        :disabled="busy || hapusBusy"
        @click="hapus"
      >{{ hapusBusy ? 'Menghapus…' : 'Hapus tagihan' }}</button>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ringkasanKeluarga, ubahTagihanKhusus, hapusTagihanKhusus } from '@shared/services/keuangan.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const keluargaId = Number(route.params.id || 0)
const tagihanId = Number(route.params.tagihanId || 0)

const loading = ref(true)
const busy = ref(false)
const hapusBusy = ref(false)
const errLoad = ref('')
const err = ref('')
const judul = ref('')
const nominal = ref('')
const periode = ref('')

onMounted(async () => {
  loading.value = true
  errLoad.value = ''
  const res = await ringkasanKeluarga(keluargaId)
  loading.value = false
  if (!res.ok) {
    errLoad.value = res.error || 'Gagal memuat tagihan'
    return
  }
  const list = res.data?.tagihan || []
  const t = list.find((x) => Number(x.id) === tagihanId)
  if (!t || t.jenis !== 'khusus') {
    errLoad.value = 'Tagihan khusus tidak ditemukan'
    return
  }
  judul.value = t.nama || ''
  nominal.value = String(t.nominal ?? '')
  periode.value = t.periode || ''
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
  const res = await ubahTagihanKhusus(tagihanId, {
    nama,
    nominal: nom,
    periode: periode.value,
  })
  busy.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal menyimpan'
    return
  }
  toast.success('Tagihan diperbarui')
  router.replace('/keuangan/tagihan/' + keluargaId)
}

async function hapus() {
  err.value = ''
  if (!confirm('Hapus tagihan khusus ini? Tagihan hilang dari rincian warga.')) return
  hapusBusy.value = true
  const res = await hapusTagihanKhusus(tagihanId, 'Dihapus pengurus')
  hapusBusy.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal menghapus'
    return
  }
  toast.success('Tagihan dihapus')
  router.replace('/keuangan/tagihan/' + keluargaId)
}
</script>
