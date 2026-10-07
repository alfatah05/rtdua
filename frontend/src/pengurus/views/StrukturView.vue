<template>
  <div>
    <AppBackHeader title="Struktur pengurus" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-8">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div v-if="ketua" class="flex flex-col items-center mb-6">
        <div class="w-16 h-16 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-lg mb-2">{{ inisial(ketua.nama) }}</div>
        <p class="font-bold text-[15px] m-0">{{ ketua.nama }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0">{{ ketua.jabatan || 'Ketua RT' }}</p>
      </div>
      <div v-if="pengurus.length" class="w-px h-6 bg-[var(--line)] mx-auto mb-4"></div>
      <div class="grid grid-cols-2 gap-3">
        <div v-for="p in pengurus" :key="p.id" class="flex flex-col items-center bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4">
          <div class="w-12 h-12 rounded-full bg-[var(--card2)] grid place-items-center font-bold text-sm mb-2">{{ inisial(p.nama) }}</div>
          <p class="font-bold text-[14px] m-0 text-center">{{ p.nama }}</p>
          <p class="text-[12px] text-[var(--mut)] m-0 text-center">{{ p.jabatan || 'Pengurus' }}</p>
        </div>
      </div>
      <p v-if="!ketua && !pengurus.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada data struktur</p>
      <p v-if="isKetua" class="text-[13px] text-[var(--mut)] text-center mt-6">Urutan dapat diatur di Kelola pengurus</p>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useAuth } from '@shared/composables/useAuth.js'
import { getStruktur } from '@shared/services/struktur.js'

const { isKetua } = useAuth()
const loading = ref(true)
const err = ref('')
const ketua = ref(null)
const pengurus = ref([])

function inisial(nama) {
  const p = String(nama || '').trim().split(/\s+/)
  if (!p[0]) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

onMounted(async () => {
  loading.value = true
  const res = await getStruktur()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  ketua.value = res.data?.ketua || null
  pengurus.value = Array.isArray(res.data?.pengurus) ? res.data.pengurus : []
})
</script>
