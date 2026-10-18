<template>
  <div>
    <AppBackHeader title="DKM" />

    <div class="flex flex-col gap-2.5">
      <!-- Ta'lim → halaman ta'lim -->
      <button
        type="button"
        class="menu-row w-full text-left relative overflow-hidden touch-manipulation will-change-transform"
        :class="pressedKey === 'talim' ? 'is-pressed' : ''"
        style="background: linear-gradient(105deg, #0EA5E9 0%, #0369A1 100%)"
        @click="goTo('/talim/jadwal', 'talim')"
      >
        <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-[64px]">
          <div class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0 bg-white/20 text-white">
            <BookOpen :size="22" />
          </div>
          <div class="flex-1 min-w-0 py-2.5">
            <p class="text-[15px] font-bold m-0 leading-tight text-white truncate">Ta'lim</p>
            <p class="text-[13px] font-semibold m-0 mt-0.5 leading-tight text-white/85 truncate">
              Jadwal kajian &amp; kegiatan
            </p>
          </div>
          <ChevronRight :size="18" class="shrink-0 text-white/70" />
        </div>
      </button>

      <!-- Bazis: expand -->
      <div
        class="menu-row overflow-hidden"
        style="background: linear-gradient(105deg, #059669 0%, #047857 100%)"
      >
        <button
          type="button"
          class="w-full min-h-[64px] p-0 text-left relative overflow-hidden touch-manipulation"
          :aria-expanded="bazisOpen"
          @click="bazisOpen = !bazisOpen"
        >
          <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-[64px]">
            <div class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0 bg-white/20 text-white">
              <HandCoins :size="22" />
            </div>
            <div class="flex-1 min-w-0 py-2.5">
              <p class="m-0 text-[15px] font-bold text-white leading-tight">Bazis</p>
              <p class="text-[13px] m-0 mt-0.5 flex items-center gap-1 text-white/85 leading-snug">
                <span>{{ bazisLabelBulan }} · total per RT</span>
                <ChevronDown
                  :size="16"
                  class="opacity-80 shrink-0 transition-transform duration-200"
                  :class="bazisOpen ? 'rotate-180' : ''"
                />
              </p>
            </div>
          </div>
        </button>

        <div v-if="bazisOpen" class="px-3.5 pb-3.5 relative z-10">
          <div class="rounded-[14px] bg-white/15 backdrop-blur-sm border border-white/20 overflow-hidden">
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
import { useRouter } from 'vue-router'
import { BookOpen, HandCoins, ChevronDown, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'

const router = useRouter()
const bazisOpen = ref(false)
const pressedKey = ref('')
let navLock = false

async function goTo(to, key) {
  if (navLock) return
  navLock = true
  pressedKey.value = key
  await new Promise((r) => setTimeout(r, 90))
  pressedKey.value = ''
  await new Promise((r) => setTimeout(r, 160))
  try {
    await router.push(to)
  } finally {
    navLock = false
  }
}

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

<style scoped>
.menu-row {
  border-radius: 18px;
  min-height: 64px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  transform: scale(1);
  transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.menu-row.is-pressed {
  transform: scale(0.96);
  transition: transform 0.08s ease-out;
}
:global(html[data-mode="dark"]) .menu-row {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
  filter: brightness(0.82) saturate(0.9);
}
:global(html:not([data-mode="dark"])) .menu-row {
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.12);
}
</style>
