<template>
  <div>
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-[24px] font-extrabold text-[var(--text)] tracking-tight m-0">rtdua</h1>
      <router-link to="/notifikasi" class="w-11 h-11 grid place-items-center rounded-full" aria-label="Notifikasi">
        <Bell :size="20" />
      </router-link>
    </header>

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-[18px] mb-4">
      <p class="font-bold text-lg m-0">{{ user?.nama }}</p>
      <p class="text-[13px] text-[var(--mut)] m-0">{{ user?.username }}</p>
    </div>

    <div class="px-1 space-y-0.5">
      <button type="button" class="w-full flex flex-row items-center justify-between px-2 py-3.5 text-left active:scale-[0.99] transition-transform" @click="toggle">
        <span class="font-semibold">Mode gelap</span>
        <span class="text-[13px] text-[var(--mut)]">{{ mode === 'dark' ? 'Aktif' : 'Nonaktif' }}</span>
      </button>
      <button type="button" class="w-full flex flex-row items-center justify-between px-2 py-3.5 text-left active:scale-[0.99] transition-transform" @click="$router.push('/ganti-pin')">
        <span class="font-semibold">Ganti PIN</span>
        <ChevronRight :size="18" class="text-[var(--mut)]" />
      </button>
    </div>

    <button
      type="button"
      class="w-full min-h-[44px] mt-8 rounded-full bg-[var(--card2)] font-bold active:scale-[0.98] transition-transform"
      @click="doLogout"
    >
      Keluar
    </button>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { Bell, ChevronRight } from 'lucide-vue-next'
import { useAuth } from '@shared/composables/useAuth.js'
import { useTheme } from '@shared/composables/useTheme.js'

const router = useRouter()
const { user, logout } = useAuth()
const { mode, toggle } = useTheme()

function doLogout() {
  logout('warga')
  router.replace('/login')
}
</script>
