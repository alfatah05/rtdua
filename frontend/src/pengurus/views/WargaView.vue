<template>
  <div>
    <AppMainHeader show-profil />

    <div class="mb-4">
      <div class="flex items-center gap-2.5 bg-[var(--search)] rounded-full px-[18px] h-12">
        <Search :size="18" class="text-[var(--mut)] shrink-0" />
        <input v-model="q" type="search" placeholder="Cari nama atau blok/nomor rumah"
          class="flex-1 min-w-0 bg-transparent outline-none text-[15px] text-[var(--text)] placeholder:text-[var(--mut)]" />
      </div>
    </div>

    <div class="grid grid-cols-4 gap-2 text-center mb-5">
      <button v-for="a in aksi" :key="a.label" type="button" class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform" @click="$router.push(a.to)">
        <span class="w-12 h-12 rounded-full grid place-items-center" :style="{ background: a.bg, color: a.color }">
          <component :is="a.icon" :size="20" />
        </span>
        <span class="text-[11px] font-semibold leading-tight">{{ a.label }}</span>
      </button>
    </div>

    <p class="text-[13px] text-[var(--mut)] mb-1 px-2">{{ filtered.length }} keluarga</p>

    <div class="px-1 space-y-0.5">
      <button
        v-for="k in filtered"
        :key="k.id"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/warga/' + k.id)"
      >
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0 relative">
          {{ k.inisial }}
          <span v-if="k.belumLengkap" class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-amber-500"></span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ k.alamat }}</p>
          <span v-if="k.penanda" class="inline-block mt-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-[var(--search)] text-[var(--mut)]">{{ k.penanda }}</span>
        </div>
        <ChevronRight :size="18" class="text-[var(--mut)] shrink-0" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { listKeluarga } from '@shared/services/warga.js'
import { Search, UserPlus, ScanLine, Download, SlidersHorizontal, ChevronRight } from 'lucide-vue-next'

const q = ref('')
const list = ref([])

const aksi = [
  { label: 'Tambah', icon: UserPlus, to: '/warga/tambah', bg: 'rgba(16,185,129,.18)', color: '#059669' },
  { label: 'Scan KK', icon: ScanLine, to: '/warga/scan-kk', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Ekspor', icon: Download, to: '/warga/ekspor', bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
  { label: 'Filter', icon: SlidersHorizontal, to: '/warga/filter', bg: 'rgba(107,114,128,.18)', color: '#4B5563' },
]

function inisial(nama) {
  return String(nama || '?').split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()
}

onMounted(async () => {
  const res = await listKeluarga({ status: 'aktif' })
  if (res.ok) {
    list.value = (res.data || []).map((k) => ({
      id: k.id,
      nama: k.nama,
      alamat: k.alamat,
      inisial: inisial(k.nama),
      belumLengkap: !!k.data_belum_lengkap,
      penanda: k.mulai_bulan_depan ? 'Mulai bulan depan' : (k.belum_ganti_pin ? 'Belum ganti PIN' : ''),
    }))
  }
})

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return list.value
  return list.value.filter((k) => k.nama.toLowerCase().includes(s) || k.alamat.toLowerCase().includes(s))
})
</script>
