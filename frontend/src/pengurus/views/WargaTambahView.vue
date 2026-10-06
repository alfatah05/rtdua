<template>
  <div>
    <AppBackHeader :title="stepTitle" />

    <!-- Step 1: data keluarga -->
    <div v-if="step === 1" class="space-y-4">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Blok</label>
        <select v-model="form.blok" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
          <option value="">Pilih blok</option>
          <option v-for="b in blokList" :key="b" :value="b">{{ b }}</option>
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
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mt-4" @click="step = 2">
        Lanjut — isi anggota
      </button>
    </div>

    <!-- Step 2: anggota -->
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
          <option>Istri</option>
          <option>Anak</option>
          <option>Lainnya</option>
        </select>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">NIK (opsional di Stage ini)</label>
        <input v-model="form.nik" type="text" inputmode="numeric" maxlength="16" placeholder="16 digit"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mt-4" @click="step = 3">
        Lanjut — selesai
      </button>
    </div>

    <!-- Step 3: selesai -->
    <div v-else class="text-center py-6">
      <div class="w-16 h-16 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center mx-auto mb-4 text-2xl font-bold">✓</div>
      <h2 class="text-lg font-bold mb-2">Keluarga ditambahkan</h2>
      <p class="text-[13px] text-[var(--mut)] mb-1">Username: <b>{{ username }}</b></p>
      <p class="text-[13px] text-[var(--mut)] mb-6">PIN awal: <b>123456</b> (wajib diganti saat login pertama)</p>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="$router.replace('/warga')">
        Kembali ke daftar
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'

const step = ref(1)
const blokList = ['AB1', 'AB2', 'AB11', 'AB12']
const form = ref({ blok: '', nomor: '', akhiran: '', nama: '', hubungan: 'Kepala keluarga', nik: '' })

const stepTitle = computed(() => {
  if (step.value === 1) return 'Tambah keluarga'
  if (step.value === 2) return 'Anggota'
  return 'Selesai'
})

const username = computed(() => {
  const a = form.value.akhiran ? form.value.akhiran : ''
  return `${form.value.blok}-${form.value.nomor}${a}`.toUpperCase()
})
</script>
