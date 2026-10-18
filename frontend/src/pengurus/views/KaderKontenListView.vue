<template>
  <div>
    <AppBackHeader :title="pageTitle" />
    <button
      type="button"
      class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4"
      @click="$router.push(basePath + '/tambah')"
    >+ Tambah {{ pageTitle }}</button>
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada konten</p>
    <div v-else class="space-y-3">
      <button
        v-for="p in daftar"
        :key="p.id"
        type="button"
        class="w-full text-left bg-[var(--card)] border border-[var(--line)] rounded-[20px] overflow-hidden shadow-[var(--sh)]"
        @click="$router.push(basePath + '/' + p.id)"
      >
        <div class="h-24 bg-[var(--search)] overflow-hidden">
          <img v-if="p.banner" :src="mediaUrl(p.banner)" class="w-full h-full object-cover" alt="" />
          <div v-else class="h-full grid place-items-center text-[var(--mut)] text-[13px]">Tanpa banner</div>
        </div>
        <div class="p-4">
          <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="statusClass(p.status)">{{ labelStatus(p.status) }}</span>
          <p class="font-bold text-[15px] m-0 mt-2">{{ p.judul }}</p>
        </div>
      </button>
    </div>
  </div>
</template>
<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listKaderKonten } from '@shared/services/konten.js'
import { mediaUrl } from '@shared/services/upload.js'
import { waitImages, takeImageUrls } from '@shared/utils/waitImages.js'

const route = useRoute()
const kategori = computed(() => String(route.meta.kategori || route.params.kategori || 'posyandu'))
const pageTitle = computed(() => {
  if (kategori.value === 'pengajian') return 'Pengajian'
  if (kategori.value === 'lingkungan') return 'Lingkungan'
  return 'Posyandu'
})
const basePath = computed(() => '/konten/' + kategori.value)

const daftar = ref([])
const loading = ref(true)
const err = ref('')

function labelStatus(s) {
  if (s === 'berjalan') return 'Berjalan'
  if (s === 'selesai') return 'Selesai'
  return 'Direncanakan'
}
function statusClass(s) {
  if (s === 'berjalan') return 'bg-[var(--ok)] text-[var(--g)]'
  if (s === 'selesai') return 'bg-[var(--card2)] text-[var(--mut)]'
  return 'bg-amber-500/15 text-amber-700'
}

async function load() {
  loading.value = true
  err.value = ''
  daftar.value = []
  const kat = kategori.value
  const res = await listKaderKonten(kat)
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    loading.value = false
    return
  }
  // filter ketat di client — hanya kategori halaman ini
  const rows = (res.data || []).filter((r) => String(r.kategori || '') === kat)
  daftar.value = rows
  const urls = []
  for (const row of rows) {
    if (row.banner) urls.push(mediaUrl(row.banner))
  }
  await waitImages(takeImageUrls(urls, 8))
  loading.value = false
}

// komponen list dipakai bersama 3 route — harus reload tiap ganti kategori
watch(kategori, () => { load() }, { immediate: true })
</script>
