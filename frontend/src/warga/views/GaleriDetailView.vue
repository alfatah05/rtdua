<template>
  <div>
    <AppBackHeader :title="album?.judul || 'Album'" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="album">
      <p class="text-[13px] text-[var(--mut)] mb-3">{{ album.tanggal_kegiatan || '—' }} · {{ (album.foto || []).length }} foto</p>
      <div v-if="!(album.foto || []).length" class="text-[13px] text-[var(--mut)] text-center py-8">Belum ada foto</div>

      <!-- Masonry 2 kolom: rasio asli landscape / portrait (Pinterest / bento) -->
      <div v-else class="galeri-masonry">
        <button
          v-for="f in album.foto"
          :key="f.id"
          type="button"
          class="galeri-masonry-item block w-full p-0 border-0 rounded-[12px] overflow-hidden bg-[var(--search)]"
          @click="preview = f"
        >
          <img
            :src="mediaUrl(f.thumb || f.file)"
            class="block w-full h-auto"
            alt=""
            loading="lazy"
          />
        </button>
      </div>

      <div
        v-if="preview"
        class="fixed inset-0 z-50 bg-black/90 flex flex-col"
        @click.self="preview = null"
      >
        <button type="button" class="self-end m-3 text-white font-bold px-3 py-2" @click="preview = null">Tutup</button>
        <img :src="mediaUrl(preview.file)" class="max-w-full max-h-[80vh] object-contain mx-auto" alt="" />
        <a
          class="text-center text-white text-[13px] font-semibold mt-3 underline"
          :href="mediaUrl(preview.file)"
          target="_blank"
          rel="noopener"
        >Buka / unduh</a>
      </div>
    </template>
  </div>
</template>

<style scoped>
.galeri-masonry {
  column-count: 2;
  column-gap: 8px;
}
.galeri-masonry-item {
  break-inside: avoid;
  -webkit-column-break-inside: avoid;
  page-break-inside: avoid;
  margin-bottom: 8px;
}
.galeri-masonry-item img {
  vertical-align: top;
}
</style>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { detailAlbum } from '@shared/services/konten.js'
import { mediaUrl } from '@shared/services/upload.js'

const route = useRoute()
const album = ref(null)
const loading = ref(true)
const err = ref('')
const preview = ref(null)

onMounted(async () => {
  const res = await detailAlbum(route.params.id)
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else album.value = res.data
})
</script>
