<template>
  <div>
    <!-- Profil adalah halaman dalam, boleh ada Kembali -->
    <header class="flex items-center gap-3 mb-6">
      <button type="button" class="min-h-[44px] px-3 -ml-2 font-semibold text-[var(--g)]" @click="$router.back()">
        ← Kembali
      </button>
      <h1 class="text-xl font-extrabold m-0">Profil</h1>
    </header>

    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-[18px] shadow-[var(--sh)] mb-4">
      <p class="font-bold text-lg m-0">{{ user?.nama }}</p>
      <p class="text-[13px] text-[var(--mut)] m-0">{{ user?.jabatan }} · {{ user?.role }}</p>
    </div>

    <div class="space-y-2">
      <button type="button" class="w-full flex items-center justify-between bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 text-left" @click="toggle">
        <span class="font-semibold">Mode gelap</span>
        <span class="text-[13px] text-[var(--mut)]">{{ mode === 'dark' ? 'Aktif' : 'Nonaktif' }}</span>
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
import { useAuth } from '@shared/composables/useAuth.js'
import { useTheme } from '@shared/composables/useTheme.js'

const router = useRouter()
const { user, logout } = useAuth()
const { mode, toggle } = useTheme()

function doLogout() {
  logout('pengurus')
  router.replace('/login')
}
</script>
