<template>
  <div>
    <AppBackHeader title="DKM" />

    <div class="flex flex-col gap-2.5">
      <button
        type="button"
        class="menu-row w-full text-left relative overflow-hidden touch-manipulation will-change-transform"
        :class="pressedKey === 'talim' ? 'is-pressed' : ''"
        style="background: #0EA5E9"
        @click="goTo('/talim/jadwal', 'talim')"
      >
        <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-[64px]">
          <div class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0 bg-white/20 text-white">
            <BookOpen :size="22" />
          </div>
          <div class="flex-1 min-w-0 py-2.5">
            <p class="text-[15px] font-bold m-0 leading-tight text-white truncate">Ta'lim</p>
            <p class="text-[13px] font-semibold m-0 mt-0.5 leading-tight text-white/85 truncate">
              Jadwal kajian & kegiatan
            </p>
          </div>
          <ChevronRight :size="18" class="shrink-0 text-white/70" />
        </div>
      </button>

      <button
        type="button"
        class="menu-row w-full text-left relative overflow-hidden touch-manipulation will-change-transform"
        :class="pressedKey === 'phbi' ? 'is-pressed' : ''"
        style="background: #8B5CF6"
        @click="goTo('/talim/phbi', 'phbi')"
      >
        <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-[64px]">
          <div class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0 bg-white/20 text-white">
            <CalendarDays :size="22" />
          </div>
          <div class="flex-1 min-w-0 py-2.5">
            <p class="text-[15px] font-bold m-0 leading-tight text-white truncate">PHBI</p>
            <p class="text-[13px] font-semibold m-0 mt-0.5 leading-tight text-white/85 truncate">
              Peringatan hari besar Islam
            </p>
          </div>
          <ChevronRight :size="18" class="shrink-0 text-white/70" />
        </div>
      </button>

      <div
        class="menu-row overflow-hidden"
        :class="pressedKey === 'bazis' ? 'is-pressed' : ''"
        style="background: #059669"
      >
        <button
          type="button"
          class="w-full min-h-[64px] p-0 text-left relative overflow-hidden touch-manipulation will-change-transform"
          :aria-expanded="bazisOpen"
          @click="toggleBazis"
        >
          <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-[64px]">
            <div class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0 bg-white/20 text-white">
              <HandCoins :size="22" />
            </div>
            <div class="flex-1 min-w-0 py-2.5">
              <p class="m-0 text-[15px] font-bold text-white leading-tight">Bazis</p>
              <p class="text-[13px] m-0 mt-0.5 flex items-center gap-1 text-white/85 leading-snug">
                <span>{{ bazisLabel }} · per RT</span>
                <ChevronDown
                  :size="16"
                  class="opacity-80 shrink-0 transition-transform duration-200"
                  :class="bazisOpen ? 'rotate-180' : ''"
                />
              </p>
            </div>
          </div>
        </button>

        <div v-if="bazisOpen" class="px-3.5 pb-3.5 relative z-10">
          <div class="rounded-[14px] bg-white/15 backdrop-blur-sm border border-white/20 overflow-hidden">
            <p class="text-[12px] font-bold text-white/90 m-0 px-3.5 pt-3 pb-1.5">
              {{ bazisLabel }}
            </p>
            <p v-if="bazisLoading" class="text-[13px] text-white/80 m-0 px-3.5 py-3">Memuat…</p>
            <p v-else-if="bazisErr" class="text-[13px] text-white/90 m-0 px-3.5 py-3">{{ bazisErr }}</p>
            <template v-else>
              <div
                v-for="(row, idx) in bazisItems"
                :key="row.rt"
                class="flex items-center justify-between gap-3 px-3.5 py-2.5"
                :class="idx < bazisItems.length - 1 ? 'border-b border-white/15' : ''"
              >
                <span class="text-[14px] font-semibold text-white">{{ row.rt }}</span>
                <span class="text-[14px] font-bold text-white tabular-nums">{{ rp(row.total) }}</span>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { BookOpen, HandCoins, ChevronDown, ChevronRight, CalendarDays } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getBazis } from '@shared/services/konten.js'

const router = useRouter()
const bazisOpen = ref(false)
const pressedKey = ref('')
let navLock = false
let bazisLock = false

const bazisLabel = ref('Bazis')
const bazisItems = ref([])
const bazisLoading = ref(false)
const bazisErr = ref('')

async function goTo(to, key) {
  if (navLock) return
  navLock = true
  pressedKey.value = key
  await delay(90)
  pressedKey.value = ''
  await delay(160)
  try {
    await router.push(to)
  } finally {
    navLock = false
  }
}

async function toggleBazis() {
  if (bazisLock) return
  bazisLock = true
  pressedKey.value = 'bazis'
  await delay(90)
  pressedKey.value = ''
  await delay(140)
  bazisOpen.value = !bazisOpen.value
  if (bazisOpen.value && !bazisItems.value.length && !bazisLoading.value) {
    await loadBazis()
  }
  bazisLock = false
}

async function loadBazis() {
  bazisLoading.value = true
  bazisErr.value = ''
  const res = await getBazis()
  bazisLoading.value = false
  if (!res.ok || !res.data) {
    bazisErr.value = res.error || 'Gagal memuat'
    return
  }
  bazisLabel.value = res.data.label_bulan || 'Bazis'
  bazisItems.value = Array.isArray(res.data.items) ? res.data.items : []
}

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}
function delay(ms) {
  return new Promise((r) => setTimeout(r, ms))
}

onMounted(() => {
  loadBazis()
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
