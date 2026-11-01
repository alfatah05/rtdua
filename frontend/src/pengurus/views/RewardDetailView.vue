<template>
  <div>
    <AppBackHeader :title="event?.judul || 'Detail undian'" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="event">
      <div class="flex gap-2 mb-3">
        <button
          type="button"
          class="flex-1 min-h-[40px] rounded-full border border-[var(--line)] text-[13px] font-bold"
          @click="$router.push('/reward/' + event.id + '/edit')"
        >
          Edit konten
        </button>
      </div>

      <div
        v-if="event.isi_html"
        class="prose-reward text-[14px] leading-relaxed mb-4 rounded-[16px] border border-[var(--line)] bg-[var(--card)] px-4 py-3"
        v-html="htmlIsi"
      />

      <!-- Card undi -->
      <div class="rounded-[16px] border border-[var(--line)] bg-[var(--card)] px-4 py-4 mb-4">
        <p class="font-bold text-[15px] m-0">Undi pemenang</p>
        <p class="text-[12px] text-[var(--mut)] m-0 mt-1">
          Sisa pool: <b>{{ event.pool_sisa ?? 0 }}</b> keluarga.
          Sistem mengambil {{ (event.pool_sisa || 0) >= 2 ? '2' : '1' }} secara acak.
        </p>
        <button
          type="button"
          class="w-full mt-3 min-h-[44px] rounded-full bg-[var(--g)] text-white text-[14px] font-bold disabled:opacity-50"
          :disabled="undiLoading || !(event.pool_sisa >= 1)"
          @click="onUndi"
        >
          {{ undiLoading ? 'Mengundi…' : (event.pool_sisa >= 1 ? 'Undi sekarang' : 'Pool kosong') }}
        </button>
        <button
          type="button"
          class="w-full mt-2 min-h-[40px] rounded-full border border-[var(--line)] text-[13px] font-bold disabled:opacity-50"
          :disabled="resetLoading"
          @click="onReset"
        >
          {{ resetLoading ? 'Mereset…' : 'Reset siklus pool' }}
        </button>
        <p v-if="aksiMsg" class="text-[12px] m-0 mt-2" :class="aksiOk ? 'text-[var(--g)]' : 'text-red-600'">{{ aksiMsg }}</p>
      </div>

      <!-- Riwayat pemenang -->
      <h2 class="text-[14px] font-bold m-0 mb-2">Riwayat pemenang</h2>
      <p v-if="!(event.pemenang || []).length" class="text-[13px] text-[var(--mut)]">Belum ada pemenang</p>
      <div v-else class="space-y-2">
        <div
          v-for="p in event.pemenang"
          :key="p.id"
          class="rounded-[14px] border border-[var(--line)] bg-[var(--card)] px-3.5 py-3"
        >
          <div class="flex items-center justify-between gap-2">
            <p class="font-bold text-[14px] m-0">{{ p.alamat }}</p>
            <span
              class="text-[11px] font-bold px-2 py-0.5 rounded-full shrink-0"
              :class="badgeClass(p.status)"
            >{{ badgeLabel(p.status) }}</span>
          </div>
          <p class="text-[11px] text-[var(--mut)] m-0 mt-1">
            Batch #{{ p.batch_id }}
            <template v-if="p.diundi_pada"> · {{ formatWaktu(p.diundi_pada) }}</template>
          </p>
          <button
            v-if="p.bisa_batal"
            type="button"
            class="mt-2 text-[12px] font-bold text-red-600"
            @click="onBatal(p.batch_id)"
          >
            Batalkan batch ini
          </button>
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
import { detailReward, undiReward, resetSiklusReward, batalBatchReward } from '@shared/services/reward.js'
import { renderMarkdown } from '@shared/utils/markdown.js'

const route = useRoute()
const event = ref(null)
const loading = ref(true)
const err = ref('')
const undiLoading = ref(false)
const resetLoading = ref(false)
const aksiMsg = ref('')
const aksiOk = ref(true)

const htmlIsi = computed(() => renderMarkdown(event.value?.isi_html || ''))

function badgeLabel(s) {
  if (s === 'diklaim') return 'Sudah klaim'
  if (s === 'hangus') return 'Hangus'
  return 'Menunggu klaim'
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

async function onUndi() {
  if (!confirm('Undi pemenang dari pool sekarang?')) return
  aksiMsg.value = ''
  undiLoading.value = true
  const res = await undiReward(route.params.id)
  undiLoading.value = false
  aksiOk.value = !!res.ok
  if (!res.ok) {
    aksiMsg.value = res.error || 'Gagal undi'
    return
  }
  event.value = res.data
  aksiMsg.value = 'Undian selesai.'
}

async function onReset() {
  if (!confirm('Reset siklus? Semua keluarga kembali eligible di event ini.')) return
  aksiMsg.value = ''
  resetLoading.value = true
  const res = await resetSiklusReward(route.params.id)
  resetLoading.value = false
  aksiOk.value = !!res.ok
  if (!res.ok) {
    aksiMsg.value = res.error || 'Gagal reset'
    return
  }
  event.value = res.data
  aksiMsg.value = 'Siklus direset.'
}

async function onBatal(batchId) {
  if (!confirm('Batalkan batch #' + batchId + '? Kedua pemenang dihapus dari riwayat & pool.')) return
  aksiMsg.value = ''
  const res = await batalBatchReward(batchId)
  aksiOk.value = !!res.ok
  if (!res.ok) {
    aksiMsg.value = res.error || 'Gagal batal'
    return
  }
  event.value = res.data
  aksiMsg.value = 'Batch dibatalkan.'
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
