<template>
  <div>
    <AppMainHeader />
    <p class="text-[13px] text-[var(--mut)] mb-3">Daftar warga RT (publik, tanpa data sensitif).</p>
    <div class="mb-3">
      <input v-model="q" type="search" placeholder="Cari nama / alamat"
        class="w-full min-h-[48px] px-4 rounded-full bg-[var(--search)] outline-none" />
    </div>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <div v-else class="space-y-1">
      <div v-for="k in filtered" :key="k.id" class="flex items-center gap-3 px-2 py-3 border-b border-[var(--line)]">
        <div class="min-w-0 flex-1">
          <p class="font-bold m-0 text-[15px]">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ k.alamat }}</p>
        </div>
      </div>
      <p v-if="!filtered.length" class="text-[13px] text-[var(--mut)] text-center py-4">Tidak ada data</p>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { listPortalWarga } from '@shared/services/warga.js'
const list = ref([])
const q = ref('')
const loading = ref(true)
const err = ref('')
const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return list.value
  return list.value.filter((k) => (k.nama || '').toLowerCase().includes(s) || (k.alamat || '').toLowerCase().includes(s))
})
onMounted(async () => {
  const res = await listPortalWarga()
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else list.value = res.data || []
})
</script>
