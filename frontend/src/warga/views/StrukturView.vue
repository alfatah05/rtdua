<template>
  <div>
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <div v-if="ketua" class="flex flex-col items-center text-center mb-6 pt-2">
        <div
          class="w-24 h-24 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center text-3xl font-bold overflow-hidden mb-3"
        >
          <img
            v-if="ketua.foto"
            :src="mediaUrl(ketua.foto)"
            alt=""
            class="w-full h-full object-cover"
          />
          <span v-else>{{ inisial(ketua.nama) }}</span>
        </div>
        <p class="text-xl font-extrabold m-0 leading-tight">{{ ketua.nama }}</p>
        <span
          class="mt-1.5 inline-flex items-center min-h-[24px] px-3 rounded-full text-[12px] font-bold bg-[var(--gd)] text-[var(--gm)]"
        >{{ ketua.jabatan || 'Ketua RT' }}</span>
      </div>

      <div v-if="pengurus.length" class="grid grid-cols-2 gap-3">
        <div
          v-for="p in pengurus"
          :key="p.id"
          class="flex flex-col items-center text-center bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 shadow-[var(--sh)]"
        >
          <div
            class="w-14 h-14 rounded-full bg-[var(--card2)] text-[var(--text)] grid place-items-center text-[15px] font-bold overflow-hidden mb-2.5"
          >
            <img
              v-if="p.foto"
              :src="mediaUrl(p.foto)"
              alt=""
              class="w-full h-full object-cover"
            />
            <span v-else>{{ inisial(p.nama) }}</span>
          </div>
          <p class="font-bold text-[15px] m-0 leading-snug line-clamp-2">{{ p.nama }}</p>
          <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5 line-clamp-2">{{ p.jabatan || 'Pengurus' }}</p>
        </div>
      </div>

      <p
        v-if="!ketua && !pengurus.length"
        class="text-[13px] text-[var(--mut)] text-center py-8"
      >Belum ada data struktur</p>
    </template>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'

import { ref, onMounted } from 'vue'
import { getStruktur } from '@shared/services/struktur.js'
import { mediaUrl } from '@shared/services/upload.js'
import { waitImages, takeImageUrls } from '@shared/utils/waitImages.js'

const loading = ref(true)
const err = ref('')
const ketua = ref(null)
const pengurus = ref([])

function inisial(nama) {
  const p = String(nama || '').trim().split(/\s+/).filter(Boolean)
  if (!p.length) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

onMounted(async () => {
  loading.value = true
  const res = await getStruktur()
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    loading.value = false
    return
  }
  ketua.value = res.data?.ketua || null
  pengurus.value = Array.isArray(res.data?.pengurus) ? res.data.pengurus : []
  const urls = []
  if (ketua.value?.foto) urls.push(mediaUrl(ketua.value.foto))
  for (const x of pengurus.value) {
    if (x.foto) urls.push(mediaUrl(x.foto))
  }
  await waitImages(takeImageUrls(urls, 12))
  loading.value = false
})
</script>
