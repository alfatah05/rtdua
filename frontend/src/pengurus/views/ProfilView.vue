<template>
  <div>
    <header class="flex items-center gap-3 mb-6">
      <button type="button" class="min-h-[44px] px-3 -ml-2 font-semibold text-[var(--g)]" @click="$router.back()">
        ← Kembali
      </button>
      <h1 class="text-xl font-extrabold">Profil</h1>
    </header>

    <UiCard class="mb-4">
      <p class="font-bold text-lg">{{ user?.nama }}</p>
      <p class="text-[13px] text-[var(--mut)]">{{ user?.jabatan }} · {{ user?.role }}</p>
    </UiCard>

    <UiCard class="mb-3" clickable @click="toggle">
      <div class="flex items-center justify-between">
        <span class="font-semibold">Mode gelap</span>
        <span class="text-[13px] text-[var(--mut)]">{{ mode === 'dark' ? 'Aktif' : 'Nonaktif' }}</span>
      </div>
    </UiCard>

    <UiButton variant="secondary" block class="mt-6" @click="doLogout">Keluar</UiButton>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import UiCard from '@shared/components/UiCard.vue'
import UiButton from '@shared/components/UiButton.vue'
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
