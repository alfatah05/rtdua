<template>
  <div>
    <AppBackHeader title="Buat album" />
    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Judul album</label>
        <input v-model="judul" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal kegiatan</label>
        <input v-model="tanggal" type="date" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Banner album</label>
        <div v-if="bannerUrl" class="rounded-[12px] overflow-hidden h-36 mb-2 bg-[var(--card2)]">
          <img :src="bannerUrl" class="w-full h-full object-cover" alt=""  loading="lazy" decoding="async" />
        </div>
        <label class="inline-flex min-h-[44px] px-4 rounded-full bg-[var(--card2)] font-semibold items-center cursor-pointer">
          {{ uploading ? 'Mengunggah…' : 'Pilih foto banner' }}
          <input type="file" accept="image/*" class="hidden" :disabled="uploading" @change="onBanner" />
        </label>
        <p class="text-[12px] text-[var(--mut)] m-0 mt-1.5">Banner tampil sebagai cover card di aplikasi warga.</p>
      </div>
      <p class="text-[13px] text-[var(--mut)]">Foto isi album bisa ditambah setelah album dibuat.</p>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Buat album' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { buatAlbum } from '@shared/services/konten.js'
import { uploadGambar, mediaUrl } from '@shared/services/upload.js'

const router = useRouter()
const judul = ref('')
const tanggal = ref('')
const banner = ref('')
const bannerUrl = ref('')
const uploading = ref(false)
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)

async function onBanner(e) {
  const f = e.target.files?.[0]
  if (!f) return
  uploading.value = true
  const res = await uploadGambar(f, 'banner', { maxSide: 1600 })
  uploading.value = false
  e.target.value = ''
  if (!res.ok) {
    msgOk.value = false
    msg.value = res.error || 'Upload banner gagal'
    return
  }
  banner.value = res.data.path
  bannerUrl.value = res.data.url || mediaUrl(res.data.path)
}

async function onSave() {
  if (!judul.value.trim()) {
    msgOk.value = false
    msg.value = 'Judul wajib'
    return
  }
  saving.value = true
  const res = await buatAlbum({
    judul: judul.value.trim(),
    tanggal_kegiatan: tanggal.value || null,
    banner: banner.value || null,
  })
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Album dibuat' : (res.error || 'Gagal')
  if (res.ok) setTimeout(() => router.replace('/konten/galeri'), 500)
}
</script>
