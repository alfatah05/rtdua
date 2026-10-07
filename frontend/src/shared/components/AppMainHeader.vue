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
        class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)] relative"
        aria-label="Notifikasi"
      >
        <Bell :size="20" />
        <span
          v-if="badge > 0"
          class="absolute top-1 right-1 min-w-[16px] h-[16px] px-0.5 rounded-full bg-[var(--g)] text-white text-[10px] font-bold grid place-items-center"
        >{{ badge > 9 ? '9+' : badge }}</span>
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
import { ref, onMounted, onUnmounted } from 'vue'
import { Bell, UserRound } from 'lucide-vue-next'
import { badgeNotifikasi } from '@shared/services/notifikasi.js'

defineProps({
  showProfil: { type: Boolean, default: false },
})

const badge = ref(0)
let timer = null

async function refresh() {
  const res = await badgeNotifikasi()
  if (res.ok) badge.value = res.data?.belum_dibaca || 0
}

function onVis() {
  if (document.visibilityState === 'visible') refresh()
}

onMounted(() => {
  refresh()
  timer = setInterval(refresh, 60000)
  document.addEventListener('visibilitychange', onVis)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  document.removeEventListener('visibilitychange', onVis)
})
</script>
