<template>
  <div>
    <AppBackHeader title="DKM" />

    <div class="flex flex-col gap-2.5">
      <!-- Ta'lim -->
      <div
        class="w-full min-h-[96px] rounded-[20px] p-5 text-left relative overflow-hidden shadow-[var(--sh)]"
        style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #38BDF8, #0EA5E9 55%, #0284C7)"
      >
        <div class="relative z-10 flex items-center gap-3">
          <div class="w-11 h-11 rounded-full grid place-items-center shrink-0 bg-white/20 text-white">
            <BookOpen :size="20" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="m-0 text-[15px] font-bold text-white leading-tight">Ta'lim</p>
            <p class="text-[13px] m-0 mt-1.5 text-white/85 leading-snug">
              Jadwal kajian & kegiatan ta'lim
            </p>
          </div>
        </div>
        <div class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"></div>
      </div>

      <!-- Bazis: expand dropdown -->
      <div
        class="rounded-[20px] overflow-hidden shadow-[var(--sh)]"
        style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #34D399, #059669 55%, #047857)"
      >
        <button
          type="button"
          class="w-full min-h-[96px] p-5 text-left relative overflow-hidden"
          :aria-expanded="bazisOpen"
          @click="bazisOpen = !bazisOpen"
        >
          <div class="relative z-10 flex items-center gap-3">
            <div class="w-11 h-11 rounded-full grid place-items-center shrink-0 bg-white/20 text-white">
              <HandCoins :size="20" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="m-0 text-[15px] font-bold text-white leading-tight">Bazis</p>
              <p class="text-[13px] m-0 mt-1.5 flex items-center gap-1 text-white/85 leading-snug">
                <span>{{ bazisLabelBulan }} · total per RT</span>
                <ChevronDown
                  :size="16"
                  class="opacity-80 shrink-0 transition-transform duration-200"
                  :class="bazisOpen ? 'rotate-180' : ''"
                />
              </p>
            </div>
          </div>
          <div class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"></div>
        </button>

        <div v-if="bazisOpen" class="px-4 pb-4 relative z-10">
          <div class="rounded-[16px] bg-white/15 backdrop-blur-sm border border-white/20 overflow-hidden">
            <p class="text-[12px] font-bold text-white/90 m-0 px-3.5 pt-3 pb-1.5">
              {{ bazisLabelBulan }} (data contoh)
            </p>
            <div
              v-for="(row, idx) in bazisDummy"
              :key="row.rt"
              class="flex items-center justify-between gap-3 px-3.5 py-2.5"
              :class="idx < bazisDummy.length - 1 ? 'border-b border-white/15' : ''"
            >
              <span class="text-[14px] font-semibold text-white">{{ row.rt }}</span>
              <span class="text-[14px] font-bold text-white tabular-nums">{{ rp(row.total) }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 border-t border-white/25 bg-white/10">
              <span class="text-[13px] font-bold text-white">Total</span>
              <span class="text-[14px] font-extrabold text-white tabular-nums">{{ rp(bazisTotal) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { BookOpen, HandCoins, ChevronDown } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'

const bazisOpen = ref(false)

const bazisDummy = [
  { rt: 'RT 001', total: 1250000 },
  { rt: 'RT 002', total: 980000 },
  { rt: 'RT 003', total: 1100000 },
  { rt: 'RT 004', total: 750000 },
  { rt: 'RT 005', total: 1320000 },
  { rt: 'RT 006', total: 890000 },
  { rt: 'RT 007', total: 1040000 },
  { rt: 'RT 008', total: 960000 },
]
const bazisTotal = computed(() => bazisDummy.reduce((s, r) => s + r.total, 0))
const bazisLabelBulan = computed(() => {
  const d = new Date()
  return 'Bazis ' + d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}
</script>
