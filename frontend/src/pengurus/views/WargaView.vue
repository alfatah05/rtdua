<template>
  <div>
    <!-- Header tetap rtdua (halaman utama) -->
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-[24px] font-extrabold text-[var(--text)] tracking-tight m-0">rtdua</h1>
      <div class="flex items-center gap-0.5">
        <router-link to="/notifikasi" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Notifikasi">
          <Bell :size="20" />
        </router-link>
        <router-link to="/profil" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Profil">
          <UserRound :size="20" />
        </router-link>
      </div>
    </header>

    <!-- Search -->
    <div class="mb-4">
      <div class="flex items-center gap-2.5 bg-[var(--search)] rounded-full px-[18px] h-12">
        <Search :size="18" class="text-[var(--mut)] shrink-0" />
        <input
          v-model="q"
          type="search"
          placeholder="Cari nama atau blok/nomor rumah"
          class="flex-1 min-w-0 bg-transparent outline-none text-[15px] text-[var(--text)] placeholder:text-[var(--mut)]"
        />
      </div>
    </div>

    <!-- 4 tombol lingkaran -->
    <div class="grid grid-cols-4 gap-2 text-center mb-5">
      <button v-for="a in aksi" :key="a.label" type="button" class="flex flex-col items-center gap-1.5" @click="$router.push(a.to)">
        <span class="w-12 h-12 rounded-full bg-[var(--card)] border border-[var(--line)] grid place-items-center shadow-[var(--sh)]">
          <component :is="a.icon" :size="20" />
        </span>
        <span class="text-[11px] font-semibold leading-tight">{{ a.label }}</span>
      </button>
    </div>

    <p class="text-[13px] text-[var(--mut)] mb-2">{{ filtered.length }} keluarga</p>

    <div class="space-y-2">
      <button
        v-for="k in filtered"
        :key="k.id"
        type="button"
        class="w-full flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 shadow-[var(--sh)] text-left active:scale-[0.98] transition"
        @click="$router.push('/warga/' + k.id)"
      >
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm flex-none relative">
          {{ k.inisial }}
          <span v-if="k.belumLengkap" class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-amber-500"></span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ k.alamat }}</p>
          <span v-if="k.penanda" class="inline-block mt-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-[var(--search)] text-[var(--mut)]">{{ k.penanda }}</span>
        </div>
        <ChevronRight :size="18" class="text-[var(--mut)] shrink-0" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Bell, UserRound, Search, UserPlus, ScanLine, Download, SlidersHorizontal, ChevronRight } from 'lucide-vue-next'

const q = ref('')

const aksi = [
  { label: 'Tambah', icon: UserPlus, to: '/warga/tambah' },
  { label: 'Scan KK', icon: ScanLine, to: '/warga/scan-kk' },
  { label: 'Ekspor', icon: Download, to: '/warga/ekspor' },
  { label: 'Filter', icon: SlidersHorizontal, to: '/warga/filter' },
]

const keluarga = [
  { id: 1, nama: 'Budi Santoso', alamat: 'AB2-22', inisial: 'BS', belumLengkap: false, penanda: null },
  { id: 2, nama: 'Siti Aminah', alamat: 'AB2-22a', inisial: 'SA', belumLengkap: true, penanda: 'Belum ganti PIN' },
  { id: 3, nama: 'Andi Wijaya', alamat: 'AB1-05', inisial: 'AW', belumLengkap: false, penanda: 'Mulai bulan depan' },
  { id: 4, nama: 'Rina Marlina', alamat: 'AB11-12', inisial: 'RM', belumLengkap: false, penanda: null },
  { id: 5, nama: 'Joko Prasetyo', alamat: 'AB12-03', inisial: 'JP', belumLengkap: true, penanda: null },
  { id: 6, nama: 'Dewi Lestari', alamat: 'AB1-08', inisial: 'DL', belumLengkap: false, penanda: null },
]

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return keluarga
  return keluarga.filter(k =>
    k.nama.toLowerCase().includes(s) || k.alamat.toLowerCase().includes(s)
  )
})
</script>
