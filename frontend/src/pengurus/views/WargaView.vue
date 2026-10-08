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
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <div v-else class="px-1 space-y-0.5">
      <button v-for="k in filtered" :key="k.id" type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/warga/' + k.id)">
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0 relative overflow-hidden">
          <img v-if="k.fotoUrl" :src="k.fotoUrl" alt="" class="w-full h-full object-cover" @error="k.fotoUrl = ''" />
          <template v-else>{{ k.inisial }}</template>
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
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { listKeluarga } from '@shared/services/warga.js'
import { mediaUrl } from '@shared/services/upload.js'
import { Search, UserPlus, ScanLine, Download, SlidersHorizontal, ChevronRight } from 'lucide-vue-next'

const route = useRoute()
const q = ref('')
const list = ref([])
const loading = ref(true)

const aksi = [
  { label: 'Tambah', icon: UserPlus, to: '/warga/tambah', bg: 'rgba(16,185,129,.18)', color: '#059669' },
  { label: 'Scan KK', icon: ScanLine, to: '/warga/scan-kk', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Ekspor', icon: Download, to: '/warga/ekspor', bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
  { label: 'Filter', icon: SlidersHorizontal, to: '/warga/filter', bg: 'rgba(107,114,128,.18)', color: '#4B5563' },
]

function inisial(nama) {
  return String(nama || '?').split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()
}

async function load() {
  loading.value = true
  const status = route.query.status || 'aktif'
  const res = await listKeluarga({ status })
  loading.value = false
  if (!res.ok) return
  let rows = (res.data || []).map((k) => ({
    id: k.id,
    nama: k.nama,
    alamat: k.alamat,
    inisial: inisial(k.nama),
    fotoUrl: k.foto ? mediaUrl(k.foto) : '',
    belumLengkap: !!k.data_belum_lengkap,
    belumPin: !!k.belum_ganti_pin,
    penanda: k.mulai_bulan_depan ? 'Mulai bulan depan' : (k.belum_ganti_pin ? 'Belum ganti PIN' : ''),
  }))
  const blokFilter = String(route.query.blok || '').split(',').filter(Boolean)
  if (blokFilter.length) {
    rows = rows.filter((k) => blokFilter.some((b) => String(k.alamat || '').startsWith(b)))
  }
  if (route.query.lengkap === 'Belum lengkap') rows = rows.filter((k) => k.belumLengkap)
  if (route.query.lengkap === 'Belum ganti PIN') rows = rows.filter((k) => k.belumPin)
  list.value = rows
}

onMounted(load)
watch(() => route.query, load, { deep: true })

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return list.value
  return list.value.filter((k) => k.nama.toLowerCase().includes(s) || k.alamat.toLowerCase().includes(s))
})
</script>
