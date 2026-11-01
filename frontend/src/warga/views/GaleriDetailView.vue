<template>
  <div>
    <AppBackHeader :title="album?.judul || 'Album'" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="album">
      <div v-if="!(album.foto || []).length" class="text-[13px] text-[var(--mut)] text-center py-8">
        Belum ada foto
      </div>

      <template v-else>
        <!-- Expanded: langsung di bawah header, padding sejajar grid, rasio asli -->
        <div v-if="expanded" class="mb-6">
          <div class="rounded-[16px] overflow-hidden bg-[var(--search)]">
            <img
              :src="mediaUrl(expanded.file)"
              class="block w-full h-auto"
              alt=""
            />
          </div>
          <div class="flex items-center gap-2 mt-3">
            <button
              type="button"
              class="flex-1 min-h-[44px] rounded-full bg-[var(--btn)] text-[var(--btn-fg)] text-[14px] font-bold active:scale-[0.98] transition flex items-center justify-center gap-2"
              @click="simpanFoto(expanded)"
            >
              <Download :size="18" />
              Simpan
            </button>
            <button
              type="button"
              class="flex-1 min-h-[44px] rounded-full border border-[var(--line)] bg-[var(--card)] text-[var(--text)] text-[14px] font-bold active:scale-[0.98] transition flex items-center justify-center gap-2"
              @click="bagikanFoto(expanded)"
            >
              <Share2 :size="18" />
              Bagikan
            </button>
          </div>
          <p v-if="aksiMsg" class="text-[12px] text-[var(--mut)] text-center m-0 mt-2">{{ aksiMsg }}</p>
        </div>

        <!-- Grid foto lain (atau semua jika belum expand) -->
        <div class="galeri-masonry">
          <button
            v-for="(f, idx) in fotoGrid"
            :key="f.id"
            type="button"
            class="galeri-masonry-item block w-full p-0 border-0 rounded-[12px] overflow-hidden bg-[var(--search)] active:opacity-90 transition-opacity"
            @click="expandFoto(f)"
          >
            <AppLazyImg
              :src="mediaUrl(f.thumb || f.file)"
              :eager="idx < 4"
              img-class="block w-full h-auto"
              alt=""
            />
          </button>
        </div>
      </template>
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
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { Download, Share2 } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { detailAlbum } from '@shared/services/konten.js'
import { mediaUrl } from '@shared/services/upload.js'
import { waitImages, takeImageUrls } from '@shared/utils/waitImages.js'

const route = useRoute()
const album = ref(null)
const loading = ref(true)
const err = ref('')
const expanded = ref(null)
const aksiMsg = ref('')

const fotoGrid = computed(() => {
  const list = album.value?.foto || []
  if (!expanded.value) return list
  const eid = expanded.value.id
  return list.filter((f) => f.id !== eid)
})

function expandFoto(f) {
  expanded.value = f
  aksiMsg.value = ''
  nextTick(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  })
}

async function simpanFoto(f) {
  aksiMsg.value = ''
  const url = mediaUrl(f.file)
  try {
    const res = await fetch(url)
    const blob = await res.blob()
    const ext = (blob.type || '').includes('png') ? 'png' : 'jpg'
    const name = `foto-album-${f.id || 'rt'}.${ext}`

    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = name
    a.rel = 'noopener'
    document.body.appendChild(a)
    a.click()
    a.remove()
    setTimeout(() => URL.revokeObjectURL(a.href), 2000)
    aksiMsg.value = 'Foto disimpan / diunduh.'
  } catch {
    window.open(url, '_blank', 'noopener')
    aksiMsg.value = 'Buka foto di tab baru untuk menyimpan.'
  }
}

async function bagikanFoto(f) {
  aksiMsg.value = ''
  const url = mediaUrl(f.file)
  const title = album.value?.judul || 'Foto album'
  try {
    if (navigator.share) {
      try {
        const res = await fetch(url)
        const blob = await res.blob()
        const ext = (blob.type || '').includes('png') ? 'png' : 'jpg'
        const file = new File([blob], `foto-${f.id || 'album'}.${ext}`, { type: blob.type || 'image/jpeg' })
        if (navigator.canShare && navigator.canShare({ files: [file] })) {
          await navigator.share({ title, files: [file] })
          aksiMsg.value = 'Berhasil dibagikan.'
          return
        }
      } catch {
        /* fall through to share URL */
      }
      await navigator.share({ title, url })
      aksiMsg.value = 'Berhasil dibagikan.'
      return
    }
    await navigator.clipboard.writeText(url)
    aksiMsg.value = 'Tautan foto disalin.'
  } catch {
    aksiMsg.value = 'Tidak bisa membagikan di perangkat ini.'
  }
}

onMounted(async () => {
  loading.value = true
  const res = await detailAlbum(route.params.id)
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    loading.value = false
    return
  }
  album.value = res.data
  const fotos = album.value?.foto || []
  await waitImages(
    takeImageUrls(
      fotos.slice(0, 4).map((f) => mediaUrl(f.thumb || f.file)),
      4
    )
  )
  loading.value = false
})
</script>
