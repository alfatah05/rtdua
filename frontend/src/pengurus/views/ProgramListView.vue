<template>
  <div>
    <AppBackHeader title="Program RT" />
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4" @click="$router.push('/konten/program/tambah')">+ Tambah program</button>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada program</p>
    <div v-else class="space-y-3">
      <button
        v-for="p in daftar"
        :key="p.id"
        type="button"
        class="w-full text-left bg-[var(--card)] border border-[var(--line)] rounded-[20px] overflow-hidden shadow-[var(--sh)]"
        @click="$router.push('/konten/program/' + p.id)"
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
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listProgram } from '@shared/services/konten.js'
import { mediaUrl } from '@shared/services/upload.js'
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
onMounted(async () => {
  const res = await listProgram()
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else daftar.value = res.data || []
})
</script>
