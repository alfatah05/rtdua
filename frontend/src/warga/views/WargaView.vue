<template>
  <div>
    <header class="sticky top-0 z-30 -mx-4 px-4 pt-1 pb-3 mb-3 flex items-center justify-between bg-[var(--bg)]">
      <h1 class="text-[24px] font-extrabold text-[var(--text)] tracking-tight m-0">rtdua</h1>
      <router-link to="/notifikasi" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Notifikasi">
        <Bell :size="20" />
      </router-link>
    </header>

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
      <button
        type="button"
        class="w-11 h-11 rounded-full grid place-items-center shrink-0 active:scale-95 transition-transform"
        style="background: rgba(107,114,128,.12); color: #4B5563"
        aria-label="Filter"
        @click="showFilter = !showFilter"
      >
        <SlidersHorizontal :size="18" />
      </button>
    </div>

    <!-- Filter sederhana -->
    <div v-if="showFilter" class="flex flex-wrap gap-2 mb-3 px-1">
      <button
        v-for="b in blokList"
        :key="b"
        type="button"
        class="min-h-[36px] px-3 rounded-full text-[13px] font-semibold border"
        :class="selectedBlok === b ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)] text-[var(--text)]'"
        @click="selectedBlok = selectedBlok === b ? '' : b"
      >{{ b }}</button>
    </div>

    <p class="text-[13px] text-[var(--mut)] mb-1 px-2">{{ filtered.length }} keluarga</p>
    <div class="px-1 space-y-0.5">
      <div v-for="k in filtered" :key="k.id" class="w-full flex flex-row items-center gap-3 px-2 py-3">
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0 overflow-hidden">
          <img v-if="k.foto" :src="k.foto" alt="" class="w-full h-full object-cover" />
          <span v-else>{{ k.inisial }}</span>
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
import { ref, computed } from 'vue'
import { Bell, Search, SlidersHorizontal } from 'lucide-vue-next'

const q = ref('')
const showFilter = ref(false)
const selectedBlok = ref('')
const blokList = ['AB1', 'AB2', 'AB11', 'AB12']

const keluarga = [
  { id: 1, nama: 'Budi Santoso', alamat: 'AB2-22', inisial: 'BS', foto: null },
  { id: 2, nama: 'Siti Aminah', alamat: 'AB2-22a', inisial: 'SA', foto: null },
  { id: 3, nama: 'Andi Wijaya', alamat: 'AB1-05', inisial: 'AW', foto: null },
  { id: 4, nama: 'Rina Marlina', alamat: 'AB11-12', inisial: 'RM', foto: null },
  { id: 5, nama: 'Joko Prasetyo', alamat: 'AB12-03', inisial: 'JP', foto: null },
]

const filtered = computed(() => {
  let list = keluarga
  const s = q.value.trim().toLowerCase()
  if (s) list = list.filter(k => k.nama.toLowerCase().includes(s) || k.alamat.toLowerCase().includes(s))
  if (selectedBlok.value) list = list.filter(k => k.alamat.startsWith(selectedBlok.value))
  return list
})
</script>
