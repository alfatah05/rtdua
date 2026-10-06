<template>
  <div>
    <header class="sticky top-0 z-30 -mx-4 px-4 pt-1 pb-3 mb-3 flex items-center justify-between bg-[var(--bg)]">
      <h1 class="text-[24px] font-extrabold text-[var(--text)] tracking-tight m-0">rtdua</h1>
      <div class="flex items-center gap-0.5">
        <router-link to="/notifikasi" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Notifikasi"><Bell :size="20" /></router-link>
        <router-link to="/profil" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Profil"><UserRound :size="20" /></router-link>
      </div>
    </header>

    <div class="flex gap-2 mb-3">
      <select v-model="bulan" class="flex-1 min-h-[44px] px-4 rounded-full bg-[var(--search)] text-[var(--text)] outline-none appearance-none">
        <option v-for="b in daftarBulan" :key="b" :value="b">{{ b }}</option>
      </select>
      <select v-model="tahun" class="w-28 min-h-[44px] px-4 rounded-full bg-[var(--search)] text-[var(--text)] outline-none appearance-none">
        <option v-for="t in daftarTahun" :key="t" :value="t">{{ t }}</option>
      </select>
    </div>

    <p class="text-[13px] text-[var(--mut)] mb-1 px-2">{{ bulan }} {{ tahun }} · {{ filtered.length }} aktivitas</p>

    <div v-if="filtered.length === 0" class="px-2 py-8 text-center text-[var(--mut)] text-[14px]">Tidak ada aktivitas di bulan ini</div>
    <div v-else class="px-1 space-y-0.5">
      <button
        v-for="act in filtered"
        :key="act.id"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/aktivitas/' + act.id)"
      >
        <div class="w-10 h-10 rounded-full grid place-items-center shrink-0" :style="{ background: act.bg, color: act.color }">
          <component :is="act.icon" :size="18" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ act.text }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ act.waktu }}</p>
        </div>
        <ChevronRight :size="18" class="text-[var(--mut)] shrink-0" />
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { Bell, UserRound, Check, UserPlus, Banknote, ChevronRight, Settings, Eye } from 'lucide-vue-next'
const bulan = ref('Oktober')
const tahun = ref('2026')
const daftarBulan = ['Oktober', 'September', 'Agustus']
const daftarTahun = ['2026', '2025']
const semua = [
  { id: 1, bulan: 'Oktober', tahun: '2026', text: 'Budi mengonfirmasi pembayaran Keluarga Hartono', waktu: '2 jam lalu', icon: Check, bg: 'rgba(16,185,129,.18)', color: '#059669' },
  { id: 2, bulan: 'Oktober', tahun: '2026', text: 'Ani menambah data Keluarga Pratama', waktu: 'Kemarin', icon: UserPlus, bg: 'rgba(6,182,212,.18)', color: '#0891B2' },
  { id: 3, bulan: 'Oktober', tahun: '2026', text: 'Sistem membuat tagihan bulan baru', waktu: '1 Okt', icon: Banknote, bg: 'rgba(16,185,129,.18)', color: '#059669' },
  { id: 4, bulan: 'Oktober', tahun: '2026', text: 'Budi membuka data sensitif AB2-22', waktu: '1 Okt', icon: Eye, bg: 'rgba(107,114,128,.18)', color: '#4B5563' },
  { id: 5, bulan: 'September', tahun: '2026', text: 'Ketua mengubah nominal kas', waktu: '28 Sep', icon: Settings, bg: 'rgba(107,114,128,.18)', color: '#4B5563' },
]
const filtered = computed(() => semua.filter(a => a.bulan === bulan.value && a.tahun === tahun.value))
</script>
