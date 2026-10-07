<template>
  <div>
    <AppBackHeader title="Filter" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-4">Memuat blok…</p>
    <div class="space-y-5 pb-28">
      <div>
        <p class="text-[13px] font-bold text-[var(--mut)] mb-2">Blok</p>
        <div class="flex flex-wrap gap-2">
          <button v-for="b in blok" :key="b.id || b" type="button"
            class="min-h-[40px] px-4 rounded-full text-[13px] font-semibold border"
            :class="selectedBlok.includes(b.nama || b) ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="toggleBlok(b.nama || b)">{{ b.nama || b }}</button>
        </div>
      </div>
      <div>
        <p class="text-[13px] font-bold text-[var(--mut)] mb-2">Kelengkapan data</p>
        <div class="flex flex-wrap gap-2">
          <button v-for="k in kelengkapan" :key="k" type="button"
            class="min-h-[40px] px-4 rounded-full text-[13px] font-semibold border"
            :class="selectedLengkap === k ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="selectedLengkap = k">{{ k }}</button>
        </div>
      </div>
      <div>
        <p class="text-[13px] font-bold text-[var(--mut)] mb-2">Status keluarga</p>
        <div class="flex flex-wrap gap-2">
          <button v-for="s in statusOpts" :key="s" type="button"
            class="min-h-[40px] px-4 rounded-full text-[13px] font-semibold border"
            :class="selectedStatus === s ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="selectedStatus = s">{{ s }}</button>
        </div>
      </div>
    </div>
    <div class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--bg)] pb-[calc(16px+env(safe-area-inset-bottom))]">
      <div class="max-w-[1000px] mx-auto flex gap-3">
        <button type="button" class="flex-1 min-h-[48px] rounded-full bg-[var(--card2)] font-bold" @click="reset">Reset</button>
        <button type="button" class="flex-1 min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="terapkan">Terapkan</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getBlok } from '@shared/services/pengaturan.js'

const router = useRouter()
const route = useRoute()
const blok = ref([])
const loading = ref(true)
const kelengkapan = ['Semua', 'Belum lengkap', 'Belum ganti PIN']
const statusOpts = ['Aktif', 'Pindah']
const selectedBlok = ref([])
const selectedLengkap = ref('Semua')
const selectedStatus = ref('Aktif')

function toggleBlok(b) {
  const i = selectedBlok.value.indexOf(b)
  if (i >= 0) selectedBlok.value.splice(i, 1)
  else selectedBlok.value.push(b)
}
function reset() {
  selectedBlok.value = []
  selectedLengkap.value = 'Semua'
  selectedStatus.value = 'Aktif'
}
function terapkan() {
  router.push({
    path: '/warga',
    query: {
      blok: selectedBlok.value.join(',') || undefined,
      lengkap: selectedLengkap.value !== 'Semua' ? selectedLengkap.value : undefined,
      status: selectedStatus.value === 'Pindah' ? 'pindah' : 'aktif',
    },
  })
}

onMounted(async () => {
  if (route.query.blok) selectedBlok.value = String(route.query.blok).split(',').filter(Boolean)
  if (route.query.lengkap) selectedLengkap.value = String(route.query.lengkap)
  if (route.query.status === 'pindah') selectedStatus.value = 'Pindah'
  const res = await getBlok()
  loading.value = false
  if (res.ok) blok.value = res.data || []
})
</script>
