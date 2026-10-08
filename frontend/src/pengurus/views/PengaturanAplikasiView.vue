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

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Logo RT</label>
          <div class="w-full aspect-square rounded-[12px] bg-[var(--search)] overflow-hidden grid place-items-center mb-2">
            <img v-if="logoRtUrl" :src="logoRtUrl" alt="Logo RT" class="w-full h-full object-contain" />
            <span v-else class="text-[12px] text-[var(--mut)]">Belum ada</span>
          </div>
          <input type="file" accept="image/jpeg,image/png,image/webp" class="text-[12px] w-full" :disabled="uploading" @change="onLogo($event, 'rt')" />
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Logo desa</label>
          <div class="w-full aspect-square rounded-[12px] bg-[var(--search)] overflow-hidden grid place-items-center mb-2">
            <img v-if="logoDesaUrl" :src="logoDesaUrl" alt="Logo desa" class="w-full h-full object-contain" />
            <span v-else class="text-[12px] text-[var(--mut)]">Belum ada</span>
          </div>
          <input type="file" accept="image/jpeg,image/png,image/webp" class="text-[12px] w-full" :disabled="uploading" @change="onLogo($event, 'desa')" />
        </div>
      </div>
      <p v-if="uploading" class="text-[12px] text-[var(--mut)]">Mengunggah logo…</p>

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
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving || uploading">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getPengaturan, updateAplikasi, tambahBlok } from '@shared/services/pengaturan.js'
import { uploadGambar, mediaUrl } from '@shared/services/upload.js'

const loading = ref(true)
const saving = ref(false)
const savingBlok = ref(false)
const uploading = ref(false)
const err = ref('')
const msg = ref('')
const msgOk = ref(true)
const namaApp = ref('')
const namaRt = ref('')
const perumahan = ref('')
const blok = ref([])
const blokBaru = ref('')
const aksesWarga = ref(false)
const logoRt = ref(null)
const logoDesa = ref(null)
const logoRtUrl = ref('')
const logoDesaUrl = ref('')

function applyLogos(d) {
  logoRt.value = d.logo_rt || null
  logoDesa.value = d.logo_desa || null
  logoRtUrl.value = d.logo_rt ? mediaUrl(d.logo_rt) : ''
  logoDesaUrl.value = d.logo_desa ? mediaUrl(d.logo_desa) : ''
}

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
  applyLogos(d)
}

async function onLogo(ev, which) {
  const file = ev.target?.files?.[0]
  if (!file) return
  uploading.value = true
  msg.value = ''
  const res = await uploadGambar(file, 'logo', { maxSide: 800 })
  uploading.value = false
  ev.target.value = ''
  if (!res.ok) {
    msgOk.value = false
    msg.value = res.error || 'Gagal unggah logo'
    return
  }
  const path = res.data?.path
  if (which === 'rt') {
    logoRt.value = path
    logoRtUrl.value = mediaUrl(path)
  } else {
    logoDesa.value = path
    logoDesaUrl.value = mediaUrl(path)
  }
  msgOk.value = true
  msg.value = 'Logo diunggah — tekan Simpan untuk menyimpan'
}

async function onSave() {
  saving.value = true
  msg.value = ''
  const res = await updateAplikasi({
    nama_app: namaApp.value,
    nama_rt: namaRt.value,
    nama_perumahan: perumahan.value,
    akses_warga: aksesWarga.value,
    logo_rt: logoRt.value,
    logo_desa: logoDesa.value,
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
    applyLogos(d)
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
