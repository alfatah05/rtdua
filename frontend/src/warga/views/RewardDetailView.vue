<template>
  <div>
    <AppBackHeader :title="event?.judul || 'Reward'" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="event">
      <!-- Hero klaim: hanya pemenang dengan kupon tersedia -->
      <div
        v-if="event.klaim_saya"
        class="mb-4 rounded-[16px] overflow-hidden"
        style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%)"
      >
        <div class="px-4 py-4 text-white">
          <p class="text-[12px] font-semibold m-0 opacity-90">Keluarga kamu terpilih</p>
          <p class="text-[18px] font-extrabold m-0 mt-0.5 leading-tight">{{ event.judul }}</p>
          <p class="text-[12px] m-0 mt-2 opacity-90">
            Klaim sebelum {{ formatWaktu(event.klaim_saya.expired_at) }}
          </p>
          <button
            type="button"
            class="w-full mt-3 min-h-[44px] rounded-full bg-white text-[#B45309] text-[14px] font-bold disabled:opacity-60"
            :disabled="klaimLoading"
            @click="onKlaim"
          >
            {{ klaimLoading ? 'Mengklaim…' : 'Klaim sekarang' }}
          </button>
          <p v-if="klaimMsg" class="text-[12px] m-0 mt-2 text-white/90">{{ klaimMsg }}</p>
        </div>
      </div>

      <div
        v-if="event.isi_html"
        class="prose-reward text-[14px] leading-relaxed mb-4 rounded-[16px] border border-[var(--line)] bg-[var(--card)] px-4 py-3"
        v-html="htmlIsi"
      />

      <h2 class="text-[14px] font-bold m-0 mb-2">Pemenang</h2>
      <p v-if="!(event.pemenang || []).length" class="text-[13px] text-[var(--mut)]">Belum ada pemenang</p>
      <div v-else class="space-y-2">
        <div
          v-for="p in event.pemenang"
          :key="p.id"
          class="rounded-[14px] border border-[var(--line)] bg-[var(--card)] px-3.5 py-3 flex items-center justify-between gap-2"
        >
          <div class="min-w-0">
            <p class="font-bold text-[14px] m-0 truncate">{{ p.alamat }}</p>
            <p v-if="p.diundi_pada" class="text-[11px] text-[var(--mut)] m-0 mt-0.5">{{ formatWaktu(p.diundi_pada) }}</p>
          </div>
          <span
            class="text-[11px] font-bold px-2 py-0.5 rounded-full shrink-0"
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
function formatWaktu(ts) {
  if (!ts) return ''
  const d = new Date(String(ts).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return ts
  return d.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
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
.prose-reward :deep(p) { margin: 0 0 0.5em; }
.prose-reward :deep(ul) { margin: 0.25em 0; padding-left: 1.25em; }
.prose-reward :deep(h1),
.prose-reward :deep(h2),
.prose-reward :deep(h3) { font-size: 1em; margin: 0.5em 0 0.25em; }
</style>
