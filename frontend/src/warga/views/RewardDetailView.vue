<template>
  <div>
    <AppBackHeader :title="event?.judul || 'Reward'" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="event">
      <!-- Hero klaim: style menu-row warga, hanya pemenang -->
      <div
        v-if="event.klaim_saya"
        class="menu-row relative overflow-hidden mb-4"
        style="background: #F59E0B"
      >
        <div class="relative z-10 px-3.5 py-3.5">
          <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0 bg-white/20 text-white">
              <Gift :size="22" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[12px] font-semibold m-0 text-white/90">Keluarga kamu terpilih</p>
              <p class="text-[15px] font-bold m-0 mt-0.5 leading-tight text-white">{{ event.judul }}</p>
              <p class="text-[12px] m-0 mt-1 text-white/85">
                Klaim sebelum {{ formatWaktu(event.klaim_saya.expired_at) }}
              </p>
            </div>
          </div>
          <button
            type="button"
            class="w-full mt-3 min-h-[44px] rounded-full bg-white text-[#B45309] text-[14px] font-bold disabled:opacity-60 active:scale-[0.98] transition"
            :disabled="klaimLoading"
            @click="onKlaim"
          >
            {{ klaimLoading ? 'Mengklaim…' : 'Klaim sekarang' }}
          </button>
          <p v-if="klaimMsg" class="text-[12px] m-0 mt-2 text-white/90 text-center">{{ klaimMsg }}</p>
        </div>
        <div class="absolute -right-8 -bottom-10 w-28 h-28 rounded-full bg-white/10 pointer-events-none" />
      </div>

      <!-- Konten MD -->
      <div
        v-if="event.isi_html"
        class="prose-reward text-[14px] leading-relaxed mb-4 rounded-[18px] border border-[var(--line)] bg-[var(--card)] px-4 py-3.5"
        v-html="htmlIsi"
      />

      <!-- List pemenang: pola list warga (icon + teks + pill) -->
      <h2 class="text-[13px] font-bold text-[var(--mut)] m-0 mb-2 px-0.5">Pemenang</h2>
      <p v-if="!(event.pemenang || []).length" class="text-[13px] text-[var(--mut)] text-center py-4">
        Belum ada pemenang
      </p>
      <div v-else class="overflow-hidden rounded-[16px] border border-[var(--line)] bg-[var(--card)]">
        <div
          v-for="(p, idx) in event.pemenang"
          :key="p.id"
          class="flex items-center gap-3 px-3.5 py-3"
          :class="idx > 0 ? 'border-t border-[var(--line)]' : ''"
        >
          <span
            class="w-11 h-11 rounded-full grid place-items-center shrink-0"
            :style="iconStyle(p.status)"
          >
            <MapPin :size="20" />
          </span>
          <div class="flex-1 min-w-0">
            <p class="font-bold text-[15px] m-0 leading-tight truncate">{{ p.alamat }}</p>
            <p v-if="p.diundi_pada" class="text-[12px] text-[var(--mut)] m-0 mt-0.5">
              {{ formatWaktu(p.diundi_pada) }}
            </p>
          </div>
          <span
            class="text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0"
            :class="badgeClass(p.status)"
          >{{ badgeLabel(p.status) }}</span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Gift, MapPin } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { detailReward, klaimReward } from '@shared/services/reward.js'
import { renderMarkdown } from '@shared/utils/markdown.js'

const route = useRoute()
const event = ref(null)
const loading = ref(true)
const err = ref('')
const klaimLoading = ref(false)
const klaimMsg = ref('')

const htmlIsi = computed(() => renderMarkdown(event.value?.isi_html || ''))

function badgeLabel(s) {
  if (s === 'diklaim') return 'Sudah klaim'
  if (s === 'hangus') return 'Hangus'
  return 'Menunggu'
}
function badgeClass(s) {
  if (s === 'diklaim') return 'bg-[var(--gd)] text-[var(--gm)]'
  if (s === 'hangus') return 'bg-red-100 text-red-700'
  return 'bg-amber-100 text-amber-800'
}
function iconStyle(s) {
  if (s === 'diklaim') return { background: 'rgba(34,197,94,.18)', color: '#16A34A' }
  if (s === 'hangus') return { background: 'rgba(239,68,68,.14)', color: '#DC2626' }
  return { background: 'rgba(245,158,11,.18)', color: '#D97706' }
}
function formatWaktu(ts) {
  if (!ts) return ''
  const d = new Date(String(ts).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return ts
  return d.toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await detailReward(route.params.id)
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    return
  }
  event.value = res.data
}

async function onKlaim() {
  if (!event.value?.klaim_saya?.id) return
  if (!confirm('Klaim hadiah ini? Ambil di rumah RT setelah klaim.')) return
  klaimMsg.value = ''
  klaimLoading.value = true
  const res = await klaimReward(event.value.klaim_saya.id)
  klaimLoading.value = false
  if (!res.ok) {
    klaimMsg.value = res.error || 'Gagal klaim'
    return
  }
  event.value = res.data
  klaimMsg.value = 'Berhasil diklaim. Ambil hadiah di rumah RT.'
}

onMounted(load)
</script>

<style scoped>
.menu-row {
  border-radius: 18px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}
:global(html[data-mode="dark"]) .menu-row {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
}
:global(html:not([data-mode="dark"])) .menu-row {
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.12);
}
.prose-reward :deep(p) { margin: 0 0 0.5em; }
.prose-reward :deep(ul) { margin: 0.25em 0; padding-left: 1.25em; }
.prose-reward :deep(h1),
.prose-reward :deep(h2),
.prose-reward :deep(h3) { font-size: 1em; margin: 0.5em 0 0.25em; }
</style>
