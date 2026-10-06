<template>
  <div>
    <h1 class="text-[22px] font-extrabold mb-4 text-[var(--text)]">Profil</h1>

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-[18px] shadow-[var(--sh)] mb-4">
      <p class="font-bold text-lg">{{ user?.nama }}</p>
      <p class="text-[13px] text-[var(--mut)]">{{ user?.username }}</p>
    </div>

    <div class="space-y-2">
      <button type="button" class="w-full flex items-center justify-between bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 text-left" @click="toggle">
        <span class="font-semibold">Mode gelap</span>
        <span class="text-[13px] text-[var(--mut)]">{{ mode === 'dark' ? 'Aktif' : 'Nonaktif' }}</span>
      </button>
      <button type="button" class="w-full flex items-center justify-between bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 text-left" @click="$router.push('/ganti-pin')">
        <span class="font-semibold">Ganti PIN</span>
        <ChevronRight :size="18" class="text-[var(--mut)]" />
      </button>
    </div>

    <button
      type="button"
      class="w-full min-h-[44px] mt-8 rounded-full bg-[var(--card2)] font-bold"
      @click="doLogout"
    >
      Keluar
    </button>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { ChevronRight } from 'lucide-vue-next'
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
