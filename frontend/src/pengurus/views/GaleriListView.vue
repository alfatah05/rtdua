<template>
  <div>
    <AppBackHeader title="Galeri" />
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4" @click="$router.push('/konten/galeri/tambah')">+ Buat album</button>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!album.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada album</p>
    <div v-else class="px-1 space-y-0.5">
      <button v-for="a in album" :key="a.id" type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition"
        @click="$router.push('/konten/galeri/' + a.id)">
        <div class="w-14 h-14 rounded-[12px] bg-[var(--search)] grid place-items-center text-[var(--mut)] text-xs shrink-0">{{ a.jumlah_foto || 0 }} foto</div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ a.judul }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ a.tanggal_kegiatan || '—' }}</p>
        </div>
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listAlbum } from '@shared/services/konten.js'
const album = ref([])
const loading = ref(true)
const err = ref('')
onMounted(async () => {
  const res = await listAlbum()
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else album.value = res.data || []
})
</script>
