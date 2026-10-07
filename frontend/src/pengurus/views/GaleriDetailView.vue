<template>
  <div>
    <AppBackHeader :title="album?.judul || 'Album'" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <p class="text-[13px] text-[var(--mut)] mb-3">{{ album?.tanggal_kegiatan || '—' }} · {{ (album?.foto || []).length }} foto</p>
      <label class="block w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold grid place-items-center cursor-pointer mb-4">
        {{ uploading ? 'Mengunggah…' : 'Tambah foto' }}
        <input type="file" accept="image/*" multiple class="hidden" :disabled="uploading" @change="onFiles" />
      </label>
      <p v-if="progress" class="text-[13px] text-[var(--mut)] mb-2">{{ progress }}</p>
      <div class="grid grid-cols-3 gap-2">
        <div v-for="f in album?.foto || []" :key="f.id" class="relative aspect-square rounded-[12px] overflow-hidden bg-[var(--search)]">
          <img :src="mediaUrl(f.thumb || f.file)" class="w-full h-full object-cover" alt="" />
          <button type="button" class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/50 text-white text-xs" @click="hapus(f.id)">×</button>
        </div>
      </div>
      <button type="button" class="w-full min-h-[44px] mt-6 rounded-full bg-red-50 text-red-600 font-semibold" @click="hapusAlbum">Hapus album</button>
      <p v-if="msg" class="text-[13px] text-center mt-2" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { detailAlbum, tambahFoto, hapusFoto, hapusAlbum as delAlbum } from '@shared/services/konten.js'
import { uploadGambar, mediaUrl } from '@shared/services/upload.js'

const route = useRoute()
const router = useRouter()
const album = ref(null)
const loading = ref(true)
const err = ref('')
const uploading = ref(false)
const progress = ref('')
const msg = ref('')
const msgOk = ref(true)

async function load() {
  loading.value = true
  const res = await detailAlbum(route.params.id)
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else album.value = res.data
}

async function onFiles(e) {
  const files = Array.from(e.target.files || [])
  e.target.value = ''
  if (!files.length) return
  uploading.value = true
  let ok = 0
  for (let i = 0; i < files.length; i++) {
    progress.value = `Upload ${i + 1}/${files.length}`
    const up = await uploadGambar(files[i], 'galeri', { maxSide: 2000, withThumb: true })
    if (!up.ok) continue
    const res = await tambahFoto(route.params.id, {
      file: up.data.path,
      thumb: up.data.thumb || null,
    })
    if (res.ok) ok++
  }
  uploading.value = false
  progress.value = ''
  msgOk.value = true
  msg.value = `${ok} foto ditambah`
  await load()
}

async function hapus(id) {
  if (!confirm('Hapus foto ini?')) return
  await hapusFoto([id])
  await load()
}

async function hapusAlbum() {
  if (!confirm('Hapus album beserta semua foto?')) return
  const res = await delAlbum(route.params.id)
  if (res.ok) router.replace('/konten/galeri')
  else { msgOk.value = false; msg.value = res.error || 'Gagal' }
}

onMounted(load)
</script>
