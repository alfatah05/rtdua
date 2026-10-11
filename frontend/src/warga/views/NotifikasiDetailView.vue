<template>
  <div>
    <AppBackHeader title="Detail notifikasi" :show-notif="false" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="item">
      <div class="flex items-start gap-3 mb-4">
        <span
          class="w-12 h-12 rounded-full grid place-items-center shrink-0"
          :style="{ background: meta.bg, color: meta.color }"
        >
          <component :is="meta.icon" :size="22" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-[12px] font-semibold text-[var(--mut)] m-0">{{ labelJenis }} · {{ waktuLengkap }}</p>
          <h1 class="text-[18px] font-extrabold m-0 mt-1 leading-snug">{{ item.judul }}</h1>
        </div>
      </div>

      <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] p-4 shadow-[var(--sh)]">
        <p class="text-[15px] leading-relaxed m-0 whitespace-pre-wrap text-[var(--text)]">{{ item.isi }}</p>
      </div>

      <button
        v-if="item.tautan"
        type="button"
        class="w-full mt-5 min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold text-[15px]"
        @click="bukaTautan"
      >
        {{ labelTautan }}
      </button>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import {
  Megaphone, Wallet, ShieldAlert, ClipboardList,
  Bell, Images, CalendarDays,
} from 'lucide-vue-next'
import { detailNotifikasi, bacaNotifikasi } from '@shared/services/notifikasi.js'
import { useNotifBadge } from '@shared/composables/useNotifBadge.js'

const route = useRoute()
const router = useRouter()
const item = ref(null)
const loading = ref(true)
const err = ref('')
const { refresh: refreshBadge } = useNotifBadge()

const JENIS = {
  pengumuman: { icon: Megaphone, bg: 'rgba(245,158,11,.18)', color: '#D97706', label: 'Pengumuman' },
  pembayaran: { icon: Wallet, bg: 'rgba(34,197,94,.18)', color: '#16A34A', label: 'Pembayaran' },
  denda: { icon: ShieldAlert, bg: 'rgba(239,68,68,.16)', color: '#DC2626', label: 'Denda' },
  denda_ronda: { icon: ShieldAlert, bg: 'rgba(239,68,68,.16)', color: '#DC2626', label: 'Denda ronda' },
  ronda: { icon: CalendarDays, bg: 'rgba(59,130,246,.18)', color: '#2563EB', label: 'Ronda' },
  program: { icon: ClipboardList, bg: 'rgba(168,85,247,.18)', color: '#7C3AED', label: 'Program RT' },
  galeri: { icon: Images, bg: 'rgba(236,72,153,.18)', color: '#DB2777', label: 'Galeri' },
  umum: { icon: Bell, bg: 'rgba(99,102,241,.16)', color: '#4F46E5', label: 'Umum' },
}

const meta = computed(() => JENIS[item.value?.jenis] || JENIS.umum)
const labelJenis = computed(() => meta.value.label)

const waktuLengkap = computed(() => {
  const s = item.value?.dibuat_pada
  if (!s) return ''
  const d = new Date(String(s).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return String(s)
  return d.toLocaleString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
})

const labelTautan = computed(() => {
  const path = String(item.value?.tautan || '')
  if (path.includes('pengumuman')) return 'Buka pengumuman'
  if (path.includes('keuangan') || path.includes('rincian') || path.includes('transfer')) return 'Buka keuangan'
  if (path.includes('ronda')) return 'Buka jadwal ronda'
  if (path.includes('program')) return 'Buka program'
  if (path.includes('galeri')) return 'Buka galeri'
  return 'Buka terkait'
})

function bukaTautan() {
  const path = String(item.value?.tautan || '').trim()
  if (!path) return
  if (path.startsWith('/')) router.push(path)
  else if (/^https?:\/\//i.test(path)) window.open(path, '_blank', 'noopener')
}

onMounted(async () => {
  const id = route.params.id
  const res = await detailNotifikasi(id)
  loading.value = false
  if (!res.ok || !res.data) {
    err.value = res.error || 'Notifikasi tidak ditemukan'
    return
  }
  item.value = res.data
  if (!res.data.dibaca) {
    try {
      await bacaNotifikasi(id)
      await refreshBadge()
    } catch (_) {}
  }
})
</script>
