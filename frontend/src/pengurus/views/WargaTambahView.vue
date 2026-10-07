<template>
  <div>
    <AppBackHeader :title="stepTitle" />

    <p v-if="loadErr" class="text-[13px] text-red-600 text-center mb-3">{{ loadErr }}</p>

    <!-- Step 1: data keluarga -->
    <div v-if="step === 1" class="space-y-4">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Blok</label>
        <select v-model="form.blok_id" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
          <option value="">Pilih blok</option>
          <option v-for="b in blokList" :key="b.id" :value="String(b.id)">{{ b.nama }}</option>
        </select>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nomor rumah</label>
        <input v-model="form.nomor" type="text" inputmode="numeric" placeholder="Contoh: 22"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Akhiran (opsional)</label>
        <input v-model="form.akhiran" type="text" placeholder="Contoh: a"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Telepon (opsional)</label>
        <input v-model="form.telepon" type="text" inputmode="tel" placeholder="08..."
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <p v-if="err" class="text-[13px] text-red-600 text-center m-0">{{ err }}</p>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mt-2" @click="goStep2">
        Lanjut — isi anggota
      </button>
    </div>

    <!-- Step 2: anggota (kepala) -->
    <div v-else-if="step === 2" class="space-y-4">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nama lengkap</label>
        <input v-model="form.nama" type="text" placeholder="Nama kepala keluarga"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Hubungan</label>
        <select v-model="form.hubungan" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
          <option>Kepala keluarga</option>
        </select>
        <p class="text-[12px] text-[var(--mut)] mt-1 m-0">Anggota lain bisa ditambah dari halaman detail.</p>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">NIK (opsional)</label>
        <input v-model="form.nik" type="text" inputmode="numeric" maxlength="16" placeholder="16 digit"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <p v-if="err" class="text-[13px] text-red-600 text-center m-0">{{ err }}</p>
      <button
        type="button"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mt-2 disabled:opacity-60"
        :disabled="saving"
        @click="onSubmit"
      >
        {{ saving ? 'Menyimpan…' : 'Simpan keluarga' }}
      </button>
      <button type="button" class="w-full min-h-[44px] rounded-full bg-[var(--card2)] font-semibold" @click="step = 1">
        Kembali
      </button>
    </div>

    <!-- Step 3: selesai -->
    <div v-else class="text-center py-6">
      <div class="w-16 h-16 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center mx-auto mb-4 text-2xl font-bold">✓</div>
      <h2 class="text-lg font-bold mb-2">Keluarga ditambahkan</h2>
      <p class="text-[13px] text-[var(--mut)] mb-1">Username: <b>{{ hasil.username }}</b></p>
      <p class="text-[13px] text-[var(--mut)] mb-1">PIN awal: <b>{{ hasil.pin_awal || '123456' }}</b></p>
      <p class="text-[12px] text-[var(--mut)] mb-6">{{ hasil.catatan || 'Tagihan mulai bulan depan.' }}</p>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="$router.replace('/warga/' + hasil.id)">
        Lihat detail
      </button>
      <button type="button" class="w-full min-h-[44px] mt-2 rounded-full bg-[var(--card2)] font-semibold" @click="$router.replace('/warga')">
        Kembali ke daftar
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { createKeluarga } from '@shared/services/warga.js'
import { getBlok } from '@shared/services/pengaturan.js'
import { useToast } from '@shared/composables/useToast.js'

const { success } = useToast()
const step = ref(1)
const blokList = ref([])
const loadErr = ref('')
const err = ref('')
const saving = ref(false)
const form = ref({
  blok_id: '',
  nomor: '',
  akhiran: '',
  telepon: '',
  nama: '',
  hubungan: 'Kepala keluarga',
  nik: '',
})
const hasil = ref({ id: null, username: '', pin_awal: '123456', catatan: '' })

const stepTitle = computed(() => {
  if (step.value === 1) return 'Tambah keluarga'
  if (step.value === 2) return 'Kepala keluarga'
  return 'Selesai'
})

function goStep2() {
  err.value = ''
  if (!form.value.blok_id || !String(form.value.nomor).trim()) {
    err.value = 'Blok dan nomor rumah wajib.'
    return
  }
  step.value = 2
}

async function onSubmit() {
  err.value = ''
  if (!String(form.value.nama).trim()) {
    err.value = 'Nama kepala keluarga wajib.'
    return
  }
  saving.value = true
  const payload = {
    blok_id: Number(form.value.blok_id),
    nomor: String(form.value.nomor).trim(),
    akhiran: String(form.value.akhiran || '').trim(),
    telepon: form.value.telepon || null,
    anggota: [
      {
        nama: String(form.value.nama).trim(),
        hubungan: 'Kepala keluarga',
        nik: form.value.nik || null,
      },
    ],
  }
  const res = await createKeluarga(payload)
  saving.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal menyimpan.'
    return
  }
  hasil.value = {
    id: res.data?.id,
    username: res.data?.username || '—',
    pin_awal: res.data?.pin_awal || '123456',
    catatan: res.data?.catatan || '',
  }
  success('Keluarga disimpan')
  step.value = 3
}

onMounted(async () => {
  const res = await getBlok()
  if (!res.ok) {
    loadErr.value = res.error || 'Gagal memuat daftar blok.'
    return
  }
  // API bisa array langsung atau { items }
  const raw = res.data
  blokList.value = Array.isArray(raw) ? raw : (raw?.items || raw?.blok || [])
  if (!blokList.value.length) {
    loadErr.value = 'Belum ada blok. Isi dulu di Pengaturan aplikasi.'
  }
})
</script>
