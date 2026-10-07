<template>
  <div>
    <AppBackHeader title="Kelola pengurus" />
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4 active:scale-[0.98] transition-transform" @click="$router.push('/kelola-pengurus/angkat')">
      + Angkat pengurus
    </button>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada pengurus</p>
    <div v-else class="px-1 space-y-0.5">
      <div v-for="p in daftar" :key="p.id" class="w-full flex flex-row items-center gap-3 px-2 py-3">
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0">{{ inisial(p.nama) }}</div>
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ p.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ p.jabatan || p.role }}{{ p.role === 'ketua' ? ' · Ketua' : '' }}{{ p.aktif ? '' : ' · nonaktif' }}</p>
        </div>
        <button v-if="p.role !== 'ketua'" type="button" class="text-[13px] font-semibold text-[var(--g)] shrink-0" @click="$router.push('/kelola-pengurus/' + p.id)">Kelola</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listPengurus } from '@shared/services/pengurus.js'

const daftar = ref([])
const loading = ref(true)
const err = ref('')

function inisial(nama) {
  const p = String(nama || '').trim().split(/\s+/)
  if (!p[0]) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

onMounted(async () => {
  loading.value = true
  const res = await listPengurus()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  daftar.value = Array.isArray(res.data) ? res.data : []
})
</script>
