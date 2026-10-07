<template>
  <div>
    <AppBackHeader title="Detail pengumuman" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="item">
      <p class="text-[12px] text-[var(--mut)] m-0 mb-1">{{ formatTgl(item.diterbitkan_pada) }}</p>
      <h1 class="text-[20px] font-extrabold m-0 mb-3">{{ item.judul }}</h1>
      <p class="text-[15px] leading-relaxed whitespace-pre-wrap m-0">{{ item.isi }}</p>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { detailPengumuman } from '@shared/services/konten.js'
const route = useRoute()
const item = ref(null)
const loading = ref(true)
const err = ref('')
function formatTgl(s) {
  if (!s) return ''
  const d = new Date(String(s).replace(' ', 'T'))
  return isNaN(d) ? s : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}
onMounted(async () => {
  const res = await detailPengumuman(route.params.id)
  loading.value = false
  if (!res.ok) err.value = res.error || 'Tidak ditemukan'
  else item.value = res.data
})
</script>
