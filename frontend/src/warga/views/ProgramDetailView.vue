<template>
  <div>
    <AppBackHeader :title="program?.judul || 'Program'" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="program">
      <div v-if="program.banner" class="rounded-[20px] overflow-hidden mb-4 h-40 bg-[var(--search)]">
        <img :src="mediaUrl(program.banner)" class="w-full h-full object-cover" alt="" />
      </div>
      <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[var(--search)] text-[var(--mut)]">{{ labelStatus(program.status) }}</span>
      <h1 class="text-[18px] font-extrabold mt-2 mb-3">{{ program.judul }}</h1>
      <div class="prose-rt text-[14px] leading-relaxed text-[var(--text)]" v-html="program.isi_html || ''"></div>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { detailProgram } from '@shared/services/konten.js'
import { mediaUrl } from '@shared/services/upload.js'

const route = useRoute()
const program = ref(null)
const loading = ref(true)
const err = ref('')

function labelStatus(s) {
  if (s === 'berjalan') return 'Berjalan'
  if (s === 'selesai') return 'Selesai'
  return 'Direncanakan'
}

onMounted(async () => {
  const res = await detailProgram(route.params.id)
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else program.value = res.data
})
</script>
