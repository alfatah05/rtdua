<template>
  <div>
    <AppBackHeader :title="keluarga.alamat" />

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-14 h-14 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-lg">{{ keluarga.inisial }}</div>
        <div>
          <p class="font-bold text-lg m-0">{{ keluarga.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ keluarga.alamat }}</p>
        </div>
      </div>
      <p v-if="keluarga.penanda" class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[var(--search)] text-[var(--mut)] inline-block m-0">{{ keluarga.penanda }}</p>
    </div>

    <h2 class="text-[15px] font-bold mb-2">Anggota</h2>
    <div class="space-y-2 mb-5">
      <div v-for="a in anggota" :key="a.id" class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5">
        <div class="w-10 h-10 rounded-full bg-[var(--card2)] grid place-items-center font-bold text-xs">{{ a.inisial }}</div>
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0">{{ a.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ a.hubungan }}</p>
        </div>
        <button type="button" class="text-[13px] font-semibold text-[var(--mut)]" @click="showNik = !showNik">
          {{ showNik ? a.nik : '••• NIK' }}
        </button>
      </div>
    </div>

    <h2 class="text-[15px] font-bold mb-2">Aksi</h2>
    <div class="space-y-2">
      <button v-for="act in aksi" :key="act.label" type="button"
        class="w-full flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 text-left active:scale-[0.98] transition"
        @click="$router.push(act.to)">
        <component :is="act.icon" :size="20" class="text-[var(--mut)]" />
        <span class="font-semibold text-[15px]">{{ act.label }}</span>
        <ChevronRight :size="18" class="text-[var(--mut)] ml-auto" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { Pencil, UserPlus, UserX, Home, KeyRound, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'

const route = useRoute()
const showNik = ref(false)

const keluarga = {
  id: route.params.id,
  nama: 'Budi Santoso',
  alamat: 'AB2-22',
  inisial: 'BS',
  penanda: null,
}

const anggota = [
  { id: 1, nama: 'Budi Santoso', hubungan: 'Kepala keluarga', inisial: 'BS', nik: '3201••••7890' },
  { id: 2, nama: 'Siti Rahayu', hubungan: 'Istri', inisial: 'SR', nik: '3201••••7891' },
  { id: 3, nama: 'Rafi Santoso', hubungan: 'Anak', inisial: 'RS', nik: '3201••••7892' },
]

const aksi = [
  { label: 'Edit keluarga', icon: Pencil, to: '/warga/' + route.params.id + '/edit' },
  { label: 'Tambah anggota', icon: UserPlus, to: '/warga/' + route.params.id + '/tambah-anggota' },
  { label: 'Tandai meninggal', icon: UserX, to: '/warga/' + route.params.id + '/meninggal' },
  { label: 'Pindah keluarga', icon: Home, to: '/warga/' + route.params.id + '/pindah' },
  { label: 'Reset PIN', icon: KeyRound, to: '/warga/' + route.params.id + '/reset-pin' },
]
</script>
