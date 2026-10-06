<template>
  <div class="min-h-screen bg-[var(--bg)] text-[var(--text)] flex">
    <!-- Desktop sidebar -->
    <aside class="hidden lg:flex flex-col w-56 shrink-0 border-r border-[var(--line)] bg-[var(--card)] sticky top-0 h-screen p-4">
      <p class="text-[20px] font-extrabold mb-6 px-2">rtdua</p>
      <nav class="flex flex-col gap-1 flex-1">
        <router-link
          v-for="item in sideItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-[14px] font-semibold no-underline text-[var(--text)]"
          :class="isActive(item.to) ? 'bg-[var(--gd)] text-[var(--gm)]' : 'hover:bg-[var(--search)]'"
        >
          <component :is="item.icon" :size="18" />
          {{ item.label }}
        </router-link>
      </nav>
      <router-link to="/profil" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-[14px] font-semibold no-underline text-[var(--text)] hover:bg-[var(--search)] mt-auto">
        <UserRound :size="18" />
        Profil
      </router-link>
    </aside>

    <div class="flex-1 min-w-0">
      <main class="max-w-[1000px] mx-auto px-4 pt-4 pb-[calc(96px+env(safe-area-inset-bottom,0px))] lg:pb-8">
        <router-view />
      </main>
      <div class="lg:hidden">
        <AppBottomNav side="pengurus" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { Home, Users, Wallet, Activity, UserRound } from 'lucide-vue-next'
import AppBottomNav from '@shared/components/AppBottomNav.vue'

const route = useRoute()
const sideItems = [
  { to: '/', label: 'Beranda', icon: Home },
  { to: '/warga', label: 'Warga', icon: Users },
  { to: '/keuangan', label: 'Keuangan', icon: Wallet },
  { to: '/aktivitas', label: 'Aktivitas', icon: Activity },
]
function isActive(path) {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>
