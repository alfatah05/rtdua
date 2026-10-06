<template>
  <nav
    class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[560px] bg-[var(--bg)] flex pt-2.5 pb-[calc(14px+env(safe-area-inset-bottom,0px))] z-40"
    aria-label="Navigasi utama"
  >
    <router-link
      v-for="item in items"
      :key="item.to"
      :to="item.to"
      class="flex-1 flex flex-col items-center gap-1.5 text-[12px] font-medium text-[var(--text)] no-underline"
      :class="{ 'font-bold': isActive(item.to) }"
    >
      <span
        class="w-[60px] h-[30px] rounded-full grid place-items-center"
        :class="isActive(item.to) ? 'bg-[var(--gd)] text-[var(--gm)]' : ''"
      >
        <component :is="item.icon" :size="20" />
      </span>
      {{ item.label }}
    </router-link>
  </nav>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { Home, Users, Wallet, UserRound, Activity } from 'lucide-vue-next'

const props = defineProps({
  side: { type: String, required: true }, // 'warga' | 'pengurus'
})

const route = useRoute()

const wargaItems = [
  { to: '/', label: 'Beranda', icon: Home },
  { to: '/warga', label: 'Warga', icon: Users },
  { to: '/keuangan', label: 'Keuangan', icon: Wallet },
  { to: '/profil', label: 'Profil', icon: UserRound },
]

const pengurusItems = [
  { to: '/', label: 'Beranda', icon: Home },
  { to: '/warga', label: 'Warga', icon: Users },
  { to: '/keuangan', label: 'Keuangan', icon: Wallet },
  { to: '/aktivitas', label: 'Aktivitas', icon: Activity },
]

const items = props.side === 'pengurus' ? pengurusItems : wargaItems

function isActive(path) {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>
