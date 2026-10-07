<template>
  <div>
    <AppBackHeader title="Pengumuman" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada pengumuman</p>
    <div v-else class="space-y-2">
      <button v-for="p in daftar" :key="p.id" type="button"
        class="w-full text-left bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 active:scale-[0.99] transition"
        @click="$router.push('/pengumuman/' + p.id)">
        <div class="flex items-center gap-2 mb-1">
          <span v-if="p.pin" class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[var(--ok)] text-[var(--g)]">Pin</span>
          <span class="text-[12px] text-[var(--mut)] ml-auto">{{ formatTgl(p.diterbitkan_pada) }}</span>
        </div>
        <p class="font-bold text-[15px] m-0">{{ p.judul }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-1 line-clamp-2">{{ ringkas(p.isi) }}</p>
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listPengumuman } from '@shared/services/konten.js'
const daftar = ref([])
const loading = ref(true)
const err = ref('')
function formatTgl(s) {
  if (!s) return ''
  const d = new Date(String(s).replace(' ', 'T'))
  return isNaN(d) ? String(s).slice(0, 10) : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}
function ringkas(isi) {
  const t = String(isi || '').replace(/\s+/g, ' ').trim()
  return t.length > 120 ? t.slice(0, 120) + '…' : t
}
onMounted(async () => {
  const res = await listPengumuman()
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else daftar.value = res.data || []
})
</script>
