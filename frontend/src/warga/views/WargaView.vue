<template>
  <div>
    <AppMainHeader />

    <div class="flex gap-2 mb-3">
      <div class="flex-1 flex items-center gap-2 bg-[var(--search)] rounded-full px-4 h-11">
        <Search :size="16" class="text-[var(--mut)] shrink-0" />
        <input
          v-model="q"
          type="search"
          placeholder="Cari nama atau blok/nomor rumah"
          class="flex-1 min-w-0 bg-transparent outline-none text-[14px] text-[var(--text)] placeholder:text-[var(--mut)]"
        />
      </div>
    </div>

    <p class="text-[13px] text-[var(--mut)] mb-1 px-2">{{ filtered.length }} keluarga</p>
    <div class="px-1 space-y-0.5">
      <div v-for="k in filtered" :key="k.id" class="w-full flex flex-row items-center gap-3 px-2 py-3">
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0">
          {{ inisial(k.nama) }}
        </div>
        <div class="min-w-0">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ k.alamat }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { listPortalWarga } from '@shared/services/warga.js'
import { Search } from 'lucide-vue-next'

const list = ref([])
const q = ref('')

function inisial(nama) {
  return String(nama || '?').split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()
}

onMounted(async () => {
  const res = await listPortalWarga()
  if (res.ok) list.value = res.data || []
})

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return list.value
  return list.value.filter((k) => (k.nama || '').toLowerCase().includes(s) || (k.alamat || '').toLowerCase().includes(s))
})
</script>
