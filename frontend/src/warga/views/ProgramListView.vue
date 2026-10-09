<template>
  <div>
    <AppBackHeader title="Program RT" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada program</p>
    <div v-else class="space-y-3">
      <button
        v-for="p in daftar"
        :key="p.id"
        type="button"
        class="w-full text-left bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 shadow-[var(--sh)]"
        @click="$router.push('/program/' + p.id)"
      >
        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[var(--search)] text-[var(--mut)]">{{ p.status || '—' }}</span>
        <p class="font-bold text-[15px] m-0 mt-2">{{ p.judul }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-1 line-clamp-3">{{ strip(p.isi_html || p.isi) }}</p>
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listProgram } from '@shared/services/konten.js'
const daftar = ref([])
const loading = ref(true)
const err = ref('')
function strip(h) {
  const t = String(h || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  return t.length > 140 ? t.slice(0, 140) + '…' : t
}
onMounted(async () => {
  const res = await listProgram()
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else daftar.value = res.data || []
})
</script>
