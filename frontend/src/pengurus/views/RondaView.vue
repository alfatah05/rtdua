<template>
  <div>
    <AppBackHeader title="Jadwal ronda" />

    <!-- Card malam ini -->
    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4">
      <p class="text-[13px] font-semibold text-[var(--mut)] m-0">Malam ini</p>
      <p class="text-[17px] font-bold mt-1 m-0">Sabtu, 11 Oktober 2026</p>
      <p class="text-[13px] text-[var(--mut)] m-0">21.00 – 24.00</p>
      <div class="mt-3 space-y-1">
        <p class="text-[14px] m-0">AB2-22 · Budi — <span class="text-[var(--g)] font-semibold">Hadir</span></p>
        <p class="text-[14px] m-0">AB1-05 · Andi — <span class="text-amber-600 font-semibold">Belum absen</span></p>
      </div>
      <button type="button" class="mt-3 text-[13px] font-semibold text-[var(--g)]" @click="$router.push('/ronda/malam/2026-10-11')">
        Kelola malam ini →
      </button>
    </div>

    <!-- Aksi -->
    <div class="grid grid-cols-3 gap-2 text-center mb-5">
      <button v-for="a in aksi" :key="a.label" type="button" class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform" @click="$router.push(a.to)">
        <span class="w-12 h-12 rounded-full grid place-items-center" :style="{ background: a.bg, color: a.color }">
          <component :is="a.icon" :size="20" />
        </span>
        <span class="text-[11px] font-semibold leading-tight">{{ a.label }}</span>
      </button>
    </div>

    <!-- Kalender sederhana -->
    <h2 class="text-[15px] font-bold mb-2">Oktober 2026</h2>
    <div class="grid grid-cols-7 gap-1 text-center text-[12px] mb-2">
      <span v-for="d in ['M','S','S','R','K','J','S']" :key="d" class="text-[var(--mut)] font-semibold py-1">{{ d }}</span>
    </div>
    <div class="grid grid-cols-7 gap-1">
      <button
        v-for="(day, i) in days"
        :key="i"
        type="button"
        class="aspect-square rounded-full text-[13px] font-semibold grid place-items-center relative"
        :class="day.ronda ? (day.khusus ? 'bg-blue-500/20 text-blue-700' : 'bg-[var(--gd)] text-[var(--gm)]') : 'text-[var(--text)]'"
        :disabled="!day.n"
        @click="day.ronda && $router.push('/ronda/malam/' + day.date)"
      >
        {{ day.n || '' }}
        <span v-if="day.ronda" class="absolute bottom-0.5 w-1 h-1 rounded-full bg-[var(--g)]"></span>
      </button>
    </div>
    <p class="text-[12px] text-[var(--mut)] mt-3">Titik = ada ronda · Biru = jadwal khusus</p>
  </div>
</template>
<script setup>
import { Calendar, List, Wand2 } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'

const aksi = [
  { label: 'Jadwal tetap', icon: Calendar, to: '/ronda/jadwal-tetap', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Jadwal khusus', icon: List, to: '/ronda/jadwal-khusus', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Isi otomatis', icon: Wand2, to: '/ronda/isi-otomatis', bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
]

// Simple Oct 2026 calendar starting Thursday
const days = []
for (let i = 0; i < 3; i++) days.push({ n: null })
for (let n = 1; n <= 31; n++) {
  const ronda = [4, 7, 11, 14, 18, 21, 25, 28].includes(n)
  const khusus = n === 11
  days.push({ n, ronda, khusus, date: `2026-10-${String(n).padStart(2,'0')}` })
}
</script>
