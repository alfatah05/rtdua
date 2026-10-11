<template>
  <div>
    <AppBackHeader title="Notifikasi" :show-notif="false" />
    <button
      v-if="daftar.length"
      type="button"
      class="text-[13px] font-semibold text-[var(--g)] mb-3"
      @click="bacaSemua"
    >
      Tandai semua dibaca
    </button>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada notifikasi</p>

    <div v-else class="space-y-1">
      <button
        v-for="n in daftar"
        :key="n.id"
        type="button"
        class="w-full text-left flex items-start gap-3 px-1 py-3 rounded-[16px] active:bg-[var(--search)] transition"
        :class="n.dibaca ? 'opacity-55' : ''"
        @click="bukaDetail(n)"
      >
        <span
          class="w-11 h-11 rounded-full grid place-items-center shrink-0 mt-0.5"
          :style="{ background: meta(n.jenis).bg, color: meta(n.jenis).color }"
        >
          <component :is="meta(n.jenis).icon" :size="20" />
        </span>

        <div class="flex-1 min-w-0">
          <div class="flex items-start gap-2">
            <p class="font-bold text-[14px] m-0 leading-snug flex-1 min-w-0 truncate">{{ n.judul }}</p>
            <ChevronRight :size="18" class="text-[var(--mut)] shrink-0 mt-0.5" />
          </div>
          <div class="flex items-start gap-2 mt-0.5">
            <p class="text-[13px] text-[var(--mut)] m-0 flex-1 min-w-0 truncate">{{ preview(n.isi) }}</p>
            <span class="text-[12px] text-[var(--mut)] shrink-0 tabular-nums">{{ jam(n.dibuat_pada) }}</span>
          </div>
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import {
  Megaphone, Wallet, ShieldAlert, ClipboardList,
  Bell, ChevronRight, Images, CalendarDays,
} from 'lucide-vue-next'
import { listNotifikasi, bacaSemuaNotifikasi } from '@shared/services/notifikasi.js'
import { useNotifBadge } from '@shared/composables/useNotifBadge.js'

const router = useRouter()
const daftar = ref([])
const loading = ref(true)
const err = ref('')
const { refresh: refreshBadge } = useNotifBadge()

const JENIS = {
  pengumuman: { icon: Megaphone, bg: 'rgba(245,158,11,.18)', color: '#D97706' },
  pembayaran: { icon: Wallet, bg: 'rgba(34,197,94,.18)', color: '#16A34A' },
  denda: { icon: ShieldAlert, bg: 'rgba(239,68,68,.16)', color: '#DC2626' },
  denda_ronda: { icon: ShieldAlert, bg: 'rgba(239,68,68,.16)', color: '#DC2626' },
  ronda: { icon: CalendarDays, bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  program: { icon: ClipboardList, bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
  galeri: { icon: Images, bg: 'rgba(236,72,153,.18)', color: '#DB2777' },
  umum: { icon: Bell, bg: 'rgba(99,102,241,.16)', color: '#4F46E5' },
}

function meta(jenis) {
  return JENIS[jenis] || JENIS.umum
}

function preview(isi) {
  const t = String(isi || '').replace(/\s+/g, ' ').trim()
  return t.length > 80 ? t.slice(0, 79) + '…' : t
}

function jam(s) {
  if (!s) return ''
  const d = new Date(String(s).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) {
    const m = String(s).match(/(\d{1,2}):(\d{2})/)
    return m ? `${m[1].padStart(2, '0')}.${m[2]}` : ''
  }
  const h = String(d.getHours()).padStart(2, '0')
  const min = String(d.getMinutes()).padStart(2, '0')
  return `${h}.${min}`
}

async function load() {
  loading.value = true
  const res = await listNotifikasi()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    return
  }
  const d = res.data
  daftar.value = Array.isArray(d) ? d : (d?.items || [])
}

function bukaDetail(n) {
  router.push('/notifikasi/' + n.id)
}

async function bacaSemua() {
  await bacaSemuaNotifikasi()
  await refreshBadge()
  await load()
}

onMounted(load)
</script>
