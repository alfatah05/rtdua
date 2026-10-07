<template>
  <div>
    <AppBackHeader title="Galeri RT" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada album</p>
    <div v-else class="space-y-2">
      <div v-for="a in daftar" :key="a.id" class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
        <p class="font-bold m-0">{{ a.judul }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0">{{ a.tanggal_kegiatan || '—' }} · {{ a.jumlah_foto ?? 0 }} foto</p>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listAlbum } from '@shared/services/konten.js'
const daftar = ref([])
const loading = ref(true)
const err = ref('')
onMounted(async () => {
  const res = await listAlbum()
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else daftar.value = res.data || []
})
</script>
