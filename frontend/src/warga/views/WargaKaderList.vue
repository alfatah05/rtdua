<template>
  <div>
    <AppBackHeader :title="pageTitle" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada informasi</p>
    <div v-else class="space-y-3">
      <div
        v-for="p in daftar"
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
              {{ pageTitle }}
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
            @click="$router.push(detailBase + '/' + p.id)"
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
import { ref, computed, watch } from 'vue'
import { ClipboardList, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listKaderKonten } from '@shared/services/konten.js'
import { plainFromMarkdown } from '@shared/utils/markdown.js'
import { mediaUrl } from '@shared/services/upload.js'
import { waitImages, takeImageUrls } from '@shared/utils/waitImages.js'

const props = defineProps({
  kategori: { type: String, required: true },
})

const pageTitle = computed(() => {
  if (props.kategori === 'pengajian') return 'Pengajian'
  if (props.kategori === 'lingkungan') return 'Lingkungan'
  if (props.kategori === 'bulutangkis') return 'Bulutangkis'
  if (props.kategori === 'talim') return "Ta'lim"
  return 'Posyandu'
})
const detailBase = computed(() => {
  if (props.kategori === 'pengajian') return '/posyandu/pengajian'
  if (props.kategori === 'lingkungan') return '/posyandu/lingkungan'
  if (props.kategori === 'bulutangkis') return '/komunitas/bulutangkis'
  if (props.kategori === 'talim') return '/talim/jadwal'
  return '/posyandu/detail'
})

const daftar = ref([])
const loading = ref(true)
const err = ref('')

function labelStatus(s) {
  if (s === 'berjalan') return 'Berjalan'
  if (s === 'selesai') return 'Selesai'
  return 'Direncanakan'
}
function ringkas(isi) {
  return plainFromMarkdown(isi, 120)
}
function bannerUrl(b) {
  return mediaUrl(b)
}
function cardStyle(p) {
  if (p.banner) return {}
  const grads = {
    posyandu: 'linear-gradient(145deg, #F97316, #C2410C)',
    pengajian: 'linear-gradient(145deg, #A855F7, #6D28D9)',
    lingkungan: 'linear-gradient(145deg, #22B863, #0B8442)',
    bulutangkis: 'linear-gradient(145deg, #0EA5E9, #0369A1)',
    talim: 'linear-gradient(145deg, #0EA5E9, #0369A1)',
  }
  return { background: grads[props.kategori] || grads.posyandu }
}

async function load() {
  loading.value = true
  err.value = ''
  daftar.value = []
  const kat = props.kategori
  const res = await listKaderKonten(kat)
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    loading.value = false
    return
  }
  const rows = (res.data || []).filter((r) => String(r.kategori || '') === kat)
  daftar.value = rows
  const urls = []
  for (const row of rows) {
    if (row.banner) urls.push(mediaUrl(row.banner))
  }
  await waitImages(takeImageUrls(urls, 8))
  loading.value = false
}

watch(() => props.kategori, () => { load() }, { immediate: true })
</script>
