<template>
  <div>
    <AppBackHeader title="Komunitas" />

    <div class="flex flex-col gap-2.5">
      <button
        v-for="m in menus"
        :key="m.to"
        type="button"
        class="menu-row w-full text-left relative overflow-hidden touch-manipulation will-change-transform"
        :class="pressedKey === m.to ? 'is-pressed' : ''"
        :style="{ background: m.cardBg }"
        @click="goTo(m.to, m.to)"
      >
        <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-[64px]">
          <div class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0 bg-white/20 text-white">
            <component :is="m.icon" :size="22" />
          </div>
          <div class="flex-1 min-w-0 py-2.5">
            <p class="text-[15px] font-bold m-0 leading-tight text-white truncate">{{ m.label }}</p>
            <p class="text-[13px] font-semibold m-0 mt-0.5 leading-tight text-white/85 truncate">
              {{ m.desc }}
            </p>
          </div>
          <ChevronRight :size="18" class="shrink-0 text-white/70" />
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Trophy, Bike, Fish, Swords, PersonStanding, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'

const router = useRouter()
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

const menus = [
  {
    to: '/komunitas/bulutangkis',
    label: 'Bulutangkis',
    desc: 'Jadwal & info klub bulutangkis',
    icon: Trophy,
    cardBg: 'linear-gradient(105deg, #0EA5E9 0%, #0369A1 100%)',
  },
  {
    to: '/komunitas/gowes',
    label: 'Gowes',
    desc: 'Komunitas sepeda warga',
    icon: Bike,
    cardBg: 'linear-gradient(105deg, #22C55E 0%, #15803D 100%)',
  },
  {
    to: '/komunitas/mancing',
    label: 'Mancing',
    desc: 'Komunitas pancing RT',
    icon: Fish,
    cardBg: 'linear-gradient(105deg, #06B6D4 0%, #0E7490 100%)',
  },
  {
    to: '/komunitas/silat',
    label: 'Pencak silat',
    desc: 'Latihan & kegiatan silat',
    icon: Swords,
    cardBg: 'linear-gradient(105deg, #EF4444 0%, #B91C1C 100%)',
  },
  {
    to: '/komunitas/senam',
    label: 'Senam',
    desc: 'Jadwal senam warga',
    icon: PersonStanding,
    cardBg: 'linear-gradient(105deg, #F59E0B 0%, #B45309 100%)',
  },
]
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
