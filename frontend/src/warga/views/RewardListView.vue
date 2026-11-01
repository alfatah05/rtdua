<template>
  <div>
    <AppBackHeader title="Reward" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!items.length" class="text-[13px] text-[var(--mut)] text-center py-8">Belum ada undian</p>

    <div v-else class="flex flex-col gap-2.5">
      <button
        v-for="e in items"
        :key="e.id"
        type="button"
        class="menu-row w-full text-left relative overflow-hidden touch-manipulation will-change-transform"
        :class="pressedKey === e.id ? 'is-pressed' : ''"
        style="background: #F59E0B"
        @click="goTo('/reward/' + e.id, e.id)"
      >
        <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-[64px]">
          <div class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0 bg-white/20 text-white">
            <Gift :size="22" />
          </div>
          <div class="flex-1 min-w-0 py-2.5">
            <p class="text-[15px] font-bold m-0 leading-tight text-white truncate">{{ e.judul }}</p>
            <p class="text-[13px] font-semibold m-0 mt-0.5 leading-tight text-white/85 truncate">
              {{ e.jumlah_pemenang || 0 }} pemenang · lihat detail
            </p>
          </div>
          <ChevronRight :size="18" class="shrink-0 text-white/70" />
        </div>
        <div class="absolute -right-8 -bottom-10 w-28 h-28 rounded-full bg-white/10 pointer-events-none" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Gift, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { listReward } from '@shared/services/reward.js'

const router = useRouter()
const items = ref([])
const loading = ref(true)
const err = ref('')
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

onMounted(async () => {
  loading.value = true
  const res = await listReward()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    return
  }
  items.value = res.data?.items || []
})
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
}
:global(html:not([data-mode="dark"])) .menu-row {
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.12);
}
</style>
