<template>
  <div>
    <AppBackHeader title="Notifikasi" />

    <!-- Kartu aktifkan push: hilang setelah diizinkan (tanpa opsi matikan) -->
    <div
      v-if="pushStatus !== 'unsupported' && pushStatus !== 'active'"
      class="mb-3 rounded-[14px] border border-[var(--line)] bg-[var(--card)] px-4 py-3"
    >
      <p class="font-bold text-[14px] m-0">Notifikasi perangkat</p>
      <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5 leading-snug">
        <template v-if="pushStatus === 'denied'">Izin ditolak. Aktifkan notifikasi di pengaturan browser/HP.</template>
        <template v-else>Aktifkan agar notifikasi di halaman ini juga muncul sebagai push PWA.</template>
      </p>
      <div class="flex gap-2 mt-2.5">
        <button
          type="button"
          class="flex-1 min-h-[40px] rounded-full bg-[var(--g)] text-white text-[13px] font-bold disabled:opacity-50"
          :disabled="pushStatus === 'loading' || pushStatus === 'denied'"
          @click="onAktifkanPush"
        >
          {{ pushStatus === 'loading' ? 'Mengaktifkan…' : 'Aktifkan push' }}
        </button>
      </div>
      <p v-if="pushMsg" class="text-[12px] m-0 mt-2" :class="pushMsgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ pushMsg }}</p>
    </div>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <EmptyState v-else-if="!items.length" title="Belum ada notifikasi" />
    <div v-else>
      <div v-for="(group, hari) in grouped" :key="hari" class="mb-4">
        <h2 class="text-[13px] font-bold text-[var(--mut)] mb-1 px-2">{{ hari }}</h2>
        <div class="overflow-hidden">
          <button
            v-for="n in group"
            :key="n.id"
            type="button"
            class="w-full flex flex-row items-center gap-3 px-3 py-3.5 text-left transition active:bg-[var(--search)]"
            @click="onTap(n)"
          >
            <div
              class="w-11 h-11 rounded-full grid place-items-center shrink-0"
              :class="n.dibaca ? 'bg-[var(--card2)] text-[var(--text)]' : 'bg-[var(--gd)] text-[var(--gm)]'"
            >
              <Bell :size="20" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-bold text-[15px] m-0 leading-tight truncate">{{ n.judul }}</p>
              <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5 truncate">{{ previewIsi(n.isi) }}</p>
              <p class="text-[12px] text-[var(--mut)] m-0 mt-1">{{ formatWaktu(n.dibuat_pada) }}</p>
            </div>
            <ChevronRight :size="16" class="text-[var(--mut)] shrink-0 opacity-50" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'

import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { Bell, ChevronRight } from 'lucide-vue-next'
import { listNotifikasi, bacaNotifikasi } from '@shared/services/notifikasi.js'
import { usePush } from '@shared/composables/usePush.js'

const router = useRouter()
const loading = ref(true)
const err = ref('')
const items = ref([])

const grouped = computed(() => {
  const map = {}
  for (const n of items.value) {
    const h = labelHari(n.dibuat_pada)
    if (!map[h]) map[h] = []
    map[h].push(n)
  }
  return map
})

function labelHari(ts) {
  if (!ts) return 'Lainnya'
  const d = new Date(String(ts).replace(' ', 'T'))
  const today = new Date()
  const yday = new Date()
  yday.setDate(today.getDate() - 1)
  const same = (a, b) =>
    a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
  if (same(d, today)) return 'Hari ini'
  if (same(d, yday)) return 'Kemarin'
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

function previewIsi(isi) {
  const t = String(isi || '').replace(/\s+/g, ' ').trim()
  return t.length > 80 ? t.slice(0, 79) + '…' : t
}

function formatWaktu(ts) {
  if (!ts) return ''
  const d = new Date(String(ts).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return ts
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await listNotifikasi({ limit: 80 })
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  items.value = res.data?.items || []
}

async function onTap(n) {
  if (!n.dibaca) {
    await bacaNotifikasi(n.id)
    n.dibaca = true
  }
  if (n.tautan) {
    router.push(n.tautan)
  }
}

const { status: pushStatus, aktifkan: aktifkanPush } = usePush()
const pushMsg = ref('')
const pushMsgOk = ref(true)

async function onAktifkanPush() {
  pushMsg.value = ''
  const res = await aktifkanPush()
  pushMsgOk.value = !!res.ok
  pushMsg.value = res.ok ? 'Push berhasil diaktifkan.' : (res.error || 'Gagal')
}

onMounted(load)
</script>
