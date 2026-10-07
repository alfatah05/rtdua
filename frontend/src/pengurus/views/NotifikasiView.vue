<template>
  <div>
    <AppBackHeader title="Notifikasi" />

    <div class="flex justify-end px-1 mb-2">
      <button
        type="button"
        class="text-[13px] font-semibold text-[var(--g)] disabled:opacity-50"
        :disabled="loading || !items.length"
        @click="onBacaSemua"
      >
        Tandai semua dibaca
      </button>
    </div>

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-8">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <EmptyState v-else-if="!items.length" title="Belum ada notifikasi" />
    <div v-else>
      <div v-for="(group, hari) in grouped" :key="hari" class="mb-4">
        <h2 class="text-[13px] font-bold text-[var(--mut)] mb-1 px-2">{{ hari }}</h2>
        <div class="px-1 space-y-0.5">
          <button
            v-for="n in group"
            :key="n.id"
            type="button"
            class="w-full flex flex-row items-start gap-3 px-2 py-3 rounded-[12px] text-left"
            :class="!n.dibaca ? 'bg-[var(--ok)]/40' : ''"
            @click="onTap(n)"
          >
            <div v-if="!n.dibaca" class="w-2 h-2 rounded-full bg-[var(--g)] mt-2 shrink-0"></div>
            <div v-else class="w-2 shrink-0"></div>
            <div class="min-w-0 flex-1">
              <p class="font-bold text-[15px] m-0 leading-tight">{{ n.judul }}</p>
              <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5">{{ n.isi }}</p>
              <p class="text-[12px] text-[var(--mut)] m-0 mt-1">{{ formatWaktu(n.dibuat_pada) }}</p>
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { listNotifikasi, bacaNotifikasi, bacaSemuaNotifikasi } from '@shared/services/notifikasi.js'

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

async function onBacaSemua() {
  await bacaSemuaNotifikasi()
  items.value = items.value.map((n) => ({ ...n, dibaca: true }))
}

onMounted(load)
</script>
