<template>
  <div>
    <AppBackHeader title="Struktur Pengurus" />
    <div v-if="ketua" class="flex flex-col items-center mb-2">
      <div class="w-16 h-16 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-lg mb-2">{{ inisial(ketua.nama) }}</div>
      <p class="font-bold text-[16px] m-0">{{ ketua.nama }}</p>
      <p class="text-[13px] text-[var(--mut)] m-0">{{ ketua.jabatan || 'Ketua RT' }}</p>
    </div>
    <div class="w-px h-6 bg-[var(--line)] mx-auto mb-4"></div>
    <div class="grid grid-cols-2 gap-3">
      <div v-for="p in pengurus" :key="p.id" class="flex flex-col items-center bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4">
        <div class="w-12 h-12 rounded-full bg-[var(--card2)] grid place-items-center font-bold text-sm mb-2">{{ inisial(p.nama) }}</div>
        <p class="font-bold text-[14px] m-0 text-center">{{ p.nama }}</p>
        <p class="text-[12px] text-[var(--mut)] m-0 text-center">{{ p.jabatan }}</p>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getStruktur } from '@shared/services/struktur.js'
const ketua = ref(null)
const pengurus = ref([])
function inisial(nama) {
  return String(nama || '?').split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()
}
onMounted(async () => {
  const res = await getStruktur()
  if (res.ok && res.data) {
    ketua.value = res.data.ketua
    pengurus.value = res.data.pengurus || []
  }
})
</script>
