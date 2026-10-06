<template>
  <div>
    <AppBackHeader title="Iuran" />

    <div class="grid grid-cols-3 gap-2 mb-4">
      <div class="rounded-[16px] p-3 text-center bg-[var(--search)]">
        <p class="text-[18px] font-extrabold m-0">73%</p>
        <p class="text-[11px] text-[var(--mut)] m-0">Lunas</p>
      </div>
      <div class="rounded-[16px] p-3 text-center bg-[var(--search)]">
        <p class="text-[18px] font-extrabold m-0">Rp 1,5jt</p>
        <p class="text-[11px] text-[var(--mut)] m-0">Terkumpul</p>
      </div>
      <div class="rounded-[16px] p-3 text-center bg-[var(--search)]">
        <p class="text-[18px] font-extrabold m-0 text-amber-600">Rp 420rb</p>
        <p class="text-[11px] text-[var(--mut)] m-0">Tunggakan</p>
      </div>
    </div>

    <h2 class="text-[15px] font-bold mb-1 px-2">Permintaan konfirmasi</h2>
    <div class="px-1 space-y-0.5 mb-4">
      <button v-for="p in permintaan" :key="p.id" type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/keuangan/permintaan/' + p.id)">
        <div class="w-10 h-10 rounded-full bg-amber-500/15 text-amber-600 grid place-items-center shrink-0">
          <Banknote :size="18" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ p.keluarga }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ p.nominal }} · {{ p.pengirim }} · {{ p.bank }}</p>
        </div>
        <ChevronRight :size="18" class="text-[var(--mut)] shrink-0" />
      </button>
    </div>

    <div class="flex gap-2 mb-2 px-1">
      <div class="flex-1 flex items-center gap-2 bg-[var(--search)] rounded-full px-4 h-11">
        <Search :size="16" class="text-[var(--mut)]" />
        <input v-model="q" type="search" placeholder="Cari keluarga" class="flex-1 bg-transparent outline-none text-[14px]" />
      </div>
      <button type="button" class="w-11 h-11 rounded-full grid place-items-center shrink-0" style="background:rgba(107,114,128,.12);color:#4B5563" @click="$router.push('/keuangan/filter-iuran')">
        <SlidersHorizontal :size="18" />
      </button>
      <button type="button" class="w-11 h-11 rounded-full grid place-items-center shrink-0" style="background:rgba(16,185,129,.15);color:#059669" @click="$router.push('/keuangan/ekspor-iuran')">
        <Download :size="18" />
      </button>
    </div>

    <div class="px-1 space-y-0.5">
      <button v-for="k in filtered" :key="k.id" type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/keuangan/tagihan/' + k.id)">
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ k.alamat }}</p>
        </div>
        <div class="text-right shrink-0">
          <p class="font-bold text-[15px] m-0" :class="k.status === 'Lunas' ? 'text-[var(--g)]' : ''">{{ k.total }}</p>
          <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="statusClass(k.status)">{{ k.status }}</span>
        </div>
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { Banknote, ChevronRight, Search, Download, SlidersHorizontal } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const q = ref('')
const permintaan = [
  { id: 1, keluarga: 'AB2-22 · Budi Santoso', nominal: 'Rp 50.000', pengirim: 'Budi S', bank: 'BCA' },
  { id: 2, keluarga: 'AB1-05 · Andi Wijaya', nominal: 'Rp 40.000', pengirim: 'Andi W', bank: 'BRI' },
]
const daftar = [
  { id: 1, nama: 'Budi Santoso', alamat: 'AB2-22', total: 'Rp 10.000', status: 'Belum lunas' },
  { id: 2, nama: 'Siti Aminah', alamat: 'AB2-22a', total: 'Rp 0', status: 'Lunas' },
  { id: 3, nama: 'Andi Wijaya', alamat: 'AB1-05', total: 'Rp 70.000', status: 'Menunggak' },
  { id: 4, nama: 'Rina Marlina', alamat: 'AB11-12', total: 'Kelebihan Rp 5.000', status: 'Lunas' },
  { id: 5, nama: 'Joko Prasetyo', alamat: 'AB12-03', total: 'Rp 40.000', status: 'Belum lunas' },
]
const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return daftar
  return daftar.filter(k => k.nama.toLowerCase().includes(s) || k.alamat.toLowerCase().includes(s))
})
function statusClass(s) {
  if (s === 'Lunas') return 'bg-[var(--ok)] text-[var(--g)]'
  if (s === 'Menunggak') return 'bg-red-500/15 text-red-600'
  return 'bg-amber-500/15 text-amber-700'
}
</script>
