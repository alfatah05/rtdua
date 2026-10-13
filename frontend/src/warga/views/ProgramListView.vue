<template>
  <div>
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada program</p>
    <div v-else class="space-y-3">
      <div
        v-for="(p, idx) in daftar"
        :key="p.id"
        class="rounded-[20px] overflow-hidden relative text-white min-h-[168px]"
        :style="cardStyle(p)"
      >
        <div
          v-if="p.banner"
          class="absolute inset-0 bg-cover bg-center"
          :style="{ backgroundImage: `url(${bannerUrl(p.banner)})` }"
        />
        <div
          class="absolute inset-0 pointer-events-none"
          :class="p.banner ? 'bg-gradient-to-br from-black/70 via-black/50 to-black/35' : ''"
        />

        <div class="p-5 relative z-10">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="flex items-center gap-2 text-[13px] font-semibold text-white/90">
              <ClipboardList :size="16" />
              Program RT
            </span>
            <span class="text-[12px] font-semibold px-3 py-1 rounded-full bg-white/20">
              {{ labelStatus(p.status) }}
            </span>
          </div>
          <h2 class="text-[17px] font-bold leading-snug mt-3 mb-1 text-white">{{ p.judul }}</h2>
          <p class="text-[13px] m-0 text-white/90 line-clamp-3">{{ ringkas(p.isi_html || p.isi) }}</p>
          <button
            type="button"
            class="inline-flex items-center gap-1 mt-3.5 min-h-[44px] px-4 rounded-full font-bold text-[15px] bg-white/25 text-white border border-white/35 backdrop-blur-sm"
            @click="$router.push('/program/' + p.id)"
          >
            Lihat detail <ChevronRight :size="18" />
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
import { ClipboardList, ChevronRight } from 'lucide-vue-next'
import { listProgram } from '@shared/services/konten.js'
import { plainFromMarkdown } from '@shared/utils/markdown.js'
import { mediaUrl } from '@shared/services/upload.js'
import { waitImages, takeImageUrls } from '@shared/utils/waitImages.js'

const daftar = ref([])
const loading = ref(true)
const err = ref('')

const fallback =
  'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.22), transparent 55%), linear-gradient(145deg, #A78BFA, #7C3AED 55%, #5B21B6)'

function bannerUrl(path) {
  return mediaUrl(path)
}

function cardStyle(p) {
  if (p.banner) return { background: '#1a1d1f' }
  return { background: fallback }
}

function labelStatus(s) {
  const map = { aktif: 'Aktif', selesai: 'Selesai', rencan: 'Rencana', rencana: 'Rencana' }
  return map[s] || s || '—'
}

function ringkas(isi) {
  return plainFromMarkdown(isi, 120)
}

onMounted(async () => {
  loading.value = true
  const res = await listProgram()
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    loading.value = false
    return
  }
  daftar.value = res.data || []
  const banners = takeImageUrls(
    daftar.value.map((p) => (p.banner ? bannerUrl(p.banner) : '')),
    8
  )
  await waitImages(banners)
  loading.value = false
})
</script>
