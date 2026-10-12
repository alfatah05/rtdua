<template>
  <div>
    <AppBackHeader title="Galeri RT" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada album</p>
    <div v-else class="space-y-3">
      <div
        v-for="a in daftar"
        :key="a.id"
        class="rounded-[20px] overflow-hidden relative text-white min-h-[168px]"
        :style="cardStyle(a)"
      >
        <div
          v-if="a.banner"
          class="absolute inset-0 bg-cover bg-center"
          :style="{ backgroundImage: `url(${bannerUrl(a.banner)})` }"
        />
        <div
          class="absolute inset-0 pointer-events-none"
          :class="a.banner ? 'bg-gradient-to-br from-black/70 via-black/50 to-black/35' : ''"
        />

        <div class="p-5 relative z-10">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="flex items-center gap-2 text-[13px] font-semibold text-white/90">
              <Images :size="16" />
              Galeri RT
            </span>
            <span class="text-[12px] font-semibold px-3 py-1 rounded-full bg-white/20">
              {{ a.jumlah_foto ?? 0 }} foto
            </span>
          </div>
          <h2 class="text-[17px] font-bold leading-snug mt-3 mb-1 text-white">{{ a.judul }}</h2>
          <p v-if="a.tanggal_kegiatan" class="text-[13px] m-0 text-white/80">{{ formatTgl(a.tanggal_kegiatan) }}</p>
          <button
            type="button"
            class="inline-flex items-center gap-1 mt-3.5 min-h-[44px] px-4 rounded-full font-bold text-[15px] bg-white/25 text-white border border-white/35 backdrop-blur-sm"
            @click="$router.push('/galeri/' + a.id)"
          >
            Lihat album <ChevronRight :size="18" />
          </button>
        </div>
        <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { Images, ChevronRight } from 'lucide-vue-next'
import { listAlbum } from '@shared/services/konten.js'
import { mediaUrl } from '@shared/services/upload.js'
import { waitImages, takeImageUrls } from '@shared/utils/waitImages.js'

const daftar = ref([])
const loading = ref(true)
const err = ref('')

const fallback =
  'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.22), transparent 55%), linear-gradient(145deg, #F472B6, #DB2777 55%, #9D174D)'

function cardStyle(a) {
  if (a.banner) return { background: '#1a1d1f' }
  return { background: fallback }
}

function bannerUrl(path) {
  return mediaUrl(path)
}

function formatTgl(s) {
  if (!s) return ''
  const d = new Date(String(s).length === 10 ? s + 'T00:00:00' : String(s).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return String(s)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

onMounted(async () => {
  loading.value = true
  const res = await listAlbum()
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    loading.value = false
    return
  }
  daftar.value = res.data || []
  await waitImages(
    takeImageUrls(
      daftar.value.map((a) => (a.banner ? bannerUrl(a.banner) : '')),
      8
    )
  )
  loading.value = false
})
</script>
