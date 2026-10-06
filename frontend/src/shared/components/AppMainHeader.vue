<template>
  <header
    class="sticky top-0 z-30 -mx-4 flex items-center justify-between bg-[var(--bg)]"
    :style="{
      paddingTop: 'max(8px, env(safe-area-inset-top, 0px))',
      paddingBottom: '10px',
      paddingLeft: '16px',
      paddingRight: '16px',
    }"
  >
    <h1 class="text-[24px] font-extrabold text-[var(--text)] tracking-tight m-0 leading-none">rtdua</h1>
    <div class="flex items-center" style="margin-right: -6px">
      <router-link
        to="/notifikasi"
        class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]"
        aria-label="Notifikasi"
      >
        <Bell :size="20" />
      </router-link>
      <router-link
        v-if="showProfil"
        to="/profil"
        class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]"
        aria-label="Profil"
      >
        <UserRound :size="20" />
      </router-link>
    </div>
  </header>
</template>

<script setup>
import { Bell, UserRound } from 'lucide-vue-next'
defineProps({
  showProfil: { type: Boolean, default: false },
})
</script>
