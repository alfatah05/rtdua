<template>
  <div>
    <AppBackHeader title="Tambah anggota" />
    <p v-if="loadErr" class="text-[13px] text-red-600 text-center py-4">{{ loadErr }}</p>
    <template v-else>
      <p class="text-[13px] text-[var(--mut)] mb-4">
        Keluarga: <b class="text-[var(--text)]">{{ alamat }}</b>
      </p>
      <form class="space-y-3" @submit.prevent="onSave">
        <UiInput v-model="form.nama" label="Nama lengkap" placeholder="Nama anggota" />
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Hubungan</label>
          <select v-model="form.hubungan" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
            <option v-for="h in hubunganOpts" :key="h" :value="h">{{ h }}</option>
          </select>
        </div>
        <UiInput v-model="form.nik" label="NIK (opsional)" placeholder="16 digit" inputmode="numeric" maxlength="16" />
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Jenis kelamin</label>
          <select v-model="form.jenis_kelamin" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
            <option value="">—</option>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
        <UiInput v-model="form.tempat_lahir" label="Tempat lahir (opsional)" />
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal lahir (opsional)</label>
          <input v-model="form.tanggal_lahir" type="date" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
        </div>
        <UiInput v-model="form.agama" label="Agama (opsional)" />
        <UiInput v-model="form.pekerjaan" label="Pekerjaan (opsional)" />
        <p v-if="err" class="text-[13px] text-red-600 text-center">{{ err }}</p>
        <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">
          {{ saving ? 'Menyimpan…' : 'Simpan anggota' }}
        </button>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import UiInput from '@shared/components/UiInput.vue'
import { getKeluarga, tambahAnggota } from '@shared/services/warga.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const router = useRouter()
const { success } = useToast()
const alamat = ref('…')
const loadErr = ref('')
const err = ref('')
const saving = ref(false)
const hubunganOpts = [
  'Istri', 'Suami', 'Anak', 'Menantu', 'Cucu', 'Orang tua', 'Mertua', 'Famili lain', 'Anggota',
]
const form = ref({
  nama: '',
  hubungan: 'Anak',
  nik: '',
  jenis_kelamin: '',
  tempat_lahir: '',
  tanggal_lahir: '',
  agama: '',
  pekerjaan: '',
})

async function onSave() {
  err.value = ''
  if (!String(form.value.nama).trim()) {
    err.value = 'Nama wajib diisi.'
    return
  }
  const nik = String(form.value.nik || '').replace(/\D/g, '')
  if (nik && nik.length !== 16) {
    err.value = 'NIK harus 16 digit jika diisi.'
    return
  }
  saving.value = true
  const payload = {
    nama: String(form.value.nama).trim(),
    hubungan: form.value.hubungan || 'Anggota',
    nik: nik || null,
    jenis_kelamin: form.value.jenis_kelamin || null,
    tempat_lahir: form.value.tempat_lahir || null,
    tanggal_lahir: form.value.tanggal_lahir || null,
    agama: form.value.agama || null,
    pekerjaan: form.value.pekerjaan || null,
  }
  const res = await tambahAnggota(route.params.id, payload)
  saving.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal menyimpan.'
    return
  }
  success('Anggota ditambahkan')
  router.replace('/warga/' + route.params.id)
}

onMounted(async () => {
  const res = await getKeluarga(route.params.id)
  if (!res.ok) {
    loadErr.value = res.error || 'Gagal memuat keluarga'
    return
  }
  alamat.value = res.data?.alamat_label || ('#' + route.params.id)
})
</script>
