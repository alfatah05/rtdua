<template>
  <div>
    <AppBackHeader :title="pageTitle" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="item">
      <div v-if="item.banner" class="rounded-[16px] overflow-hidden mb-4">
        <img :src="mediaUrl(item.banner)" class="w-full max-h-56 object-cover" alt="" />
      </div>
      <div class="flex items-center gap-2 mb-2">
        <span class="text-[12px] font-semibold px-2.5 py-1 rounded-full bg-[var(--card2)] text-[var(--mut)]">
          {{ labelStatus(item.status) }}
        </span>
      </div>
      <h1 class="text-[20px] font-extrabold m-0 mb-3 text-[var(--text)]">{{ item.judul }}</h1>
      <div class="prose-rt text-[15px] text-[var(--text)] leading-relaxed" v-html="rendered"></div>
    </template>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { detailKaderKonten } from '@shared/services/konten.js'
import { mediaUrl } from '@shared/services/upload.js'
import { renderMarkdown } from '@shared/utils/markdown.js'

const props = defineProps({
  kategori: { type: String, default: '' },
})
const route = useRoute()
const item = ref(null)
const loading = ref(true)
const err = ref('')

const TITLE = {
  pengajian: 'Pengajian',
  lingkungan: 'Lingkungan',
  bulutangkis: 'Bulutangkis',
  gowes: 'Gowes',
  mancing: 'Mancing',
  silat: 'Pencak silat',
  senam: 'Senam',
  talim: "Ta'lim",
  phbi: 'PHBI',
  rw: 'RW',
  ambulance: 'Ambulance',
  posyandu: 'Posyandu',
}

const pageTitle = computed(() => {
  const k = props.kategori || item.value?.kategori || ''
  return TITLE[k] || 'Posyandu'
})
const rendered = computed(() => renderMarkdown(item.value?.isi_html || ''))

function labelStatus(s) {
  if (s === 'berjalan') return 'Berjalan'
  if (s === 'selesai') return 'Selesai'
  return 'Direncanakan'
}

onMounted(async () => {
  loading.value = true
  const res = await detailKaderKonten(route.params.id)
  loading.value = false
  if (!res.ok || !res.data) {
    err.value = res.error || 'Tidak ditemukan'
    return
  }
  item.value = res.data
})
</script>
