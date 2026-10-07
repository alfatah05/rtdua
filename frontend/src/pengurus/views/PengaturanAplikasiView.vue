<template>
  <div>
    <AppBackHeader title="Pengaturan aplikasi" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-8">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <form v-else class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nama aplikasi</label>
        <input v-model="namaApp" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nama RT</label>
        <input v-model="namaRt" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nama perumahan</label>
        <input v-model="perumahan" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Daftar blok</label>
        <div class="flex flex-wrap gap-2 mb-2">
          <span v-for="b in blok" :key="b.id || b.nama" class="px-3 py-1.5 rounded-full bg-[var(--card2)] text-[13px] font-semibold">{{ b.nama || b }}</span>
        </div>
        <div class="flex gap-2">
          <input v-model="blokBaru" type="text" placeholder="Nama blok" class="flex-1 min-h-[44px] px-3 rounded-[12px] bg-[var(--search)] outline-none text-[14px]" />
          <button type="button" class="px-4 rounded-full bg-[var(--card2)] text-[13px] font-semibold" :disabled="savingBlok" @click="onTambahBlok">+ Tambah</button>
        </div>
      </div>
      <label class="flex items-center gap-3 min-h-[44px]">
        <input v-model="aksesWarga" type="checkbox" class="w-5 h-5 accent-[var(--g)]" />
        <span class="text-[15px] font-semibold">Akses warga dibuka</span>
      </label>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getPengaturan, updateAplikasi, tambahBlok } from '@shared/services/pengaturan.js'

const loading = ref(true)
const saving = ref(false)
const savingBlok = ref(false)
const err = ref('')
const msg = ref('')
const msgOk = ref(true)
const namaApp = ref('')
const namaRt = ref('')
const perumahan = ref('')
const blok = ref([])
const blokBaru = ref('')
const aksesWarga = ref(false)

async function load() {
  loading.value = true
  err.value = ''
  const res = await getPengaturan()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat pengaturan'
    return
  }
  const d = res.data || {}
  namaApp.value = d.nama_app || ''
  namaRt.value = d.nama_rt || ''
  perumahan.value = d.nama_perumahan || ''
  aksesWarga.value = !!d.akses_warga
  blok.value = Array.isArray(d.blok) ? d.blok : []
}

async function onSave() {
  saving.value = true
  msg.value = ''
  const res = await updateAplikasi({
    nama_app: namaApp.value,
    nama_rt: namaRt.value,
    nama_perumahan: perumahan.value,
    akses_warga: aksesWarga.value,
  })
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Pengaturan disimpan' : (res.error || 'Gagal menyimpan')
  if (res.ok && res.data) {
    const d = res.data
    namaApp.value = d.nama_app || namaApp.value
    namaRt.value = d.nama_rt || namaRt.value
    perumahan.value = d.nama_perumahan || perumahan.value
    aksesWarga.value = !!d.akses_warga
    if (Array.isArray(d.blok)) blok.value = d.blok
  }
}

async function onTambahBlok() {
  const nama = (blokBaru.value || '').trim()
  if (!nama) return
  savingBlok.value = true
  msg.value = ''
  const res = await tambahBlok(nama)
  savingBlok.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Blok ditambah' : (res.error || 'Gagal menambah blok')
  if (res.ok) {
    blokBaru.value = ''
    await load()
  }
}

onMounted(load)
</script>
