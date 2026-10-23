<template>
  <div>
    <AppBackHeader title="Notifikasi" :show-notif="false" />

    <!-- Kartu aktifkan push PWA -->
    <div
      v-if="pushStatus !== 'unsupported'"
      class="mb-4 rounded-[14px] border border-[var(--line)] bg-[var(--card)] px-4 py-3"
    >
      <p class="font-bold text-[14px] m-0">Notifikasi perangkat</p>
      <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5 leading-snug">
        <template v-if="pushStatus === 'active'">Push aktif — pemberitahuan masuk ke HP/browser.</template>
        <template v-else-if="pushStatus === 'denied'">Izin ditolak. Buka pengaturan browser/HP untuk mengizinkan notifikasi.</template>
        <template v-else>Aktifkan agar isi notifikasi di halaman ini juga muncul sebagai push PWA.</template>
      </p>
      <div class="flex gap-2 mt-2.5">
        <button
          v-if="pushStatus !== 'active'"
          type="button"
          class="flex-1 min-h-[40px] rounded-full bg-[var(--g)] text-white text-[13px] font-bold disabled:opacity-50"
          :disabled="pushStatus === 'loading' || pushStatus === 'denied'"
          @click="onAktifkanPush"
        >
          {{ pushStatus === 'loading' ? 'Mengaktifkan…' : 'Aktifkan push' }}
        </button>
        <button
          v-else
          type="button"
          class="flex-1 min-h-[40px] rounded-full bg-[var(--card2)] text-[13px] font-semibold"
          @click="onMatikanPush"
        >
          Matikan push
        </button>
      </div>
      <p v-if="pushMsg" class="text-[12px] m-0 mt-2" :class="pushMsgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ pushMsg }}</p>
    </div>
    <p
      v-else
      class="text-[12px] text-[var(--mut)] mb-4 m-0 px-1"
    >
      Browser ini tidak mendukung push. Di iPhone, pasang ke Layar Utama (Share → Add to Home Screen) dulu.
    </p>

    <button
      v-if="daftar.length"
      type="button"
      class="text-[13px] font-semibold text-[var(--g)] mb-3"
      @click="bacaSemua"
    >
      Tandai semua dibaca
    </button>
    <AppPageSpinner v-if="loading" />
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
            <p class="font-bold text-[15px] m-0 leading-tight flex-1">{{ n.judul }}</p>
            <span v-if="!n.dibaca" class="w-2 h-2 rounded-full bg-[var(--g)] shrink-0 mt-1.5" />
          </div>
          <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5 leading-snug">{{ preview(n.isi) }}</p>
          <p class="text-[11px] text-[var(--mut)] m-0 mt-1">{{ jam(n.dibuat_pada || n.created_at) }}</p>
        </div>
        <ChevronRight :size="16" class="text-[var(--mut)] shrink-0 mt-2 opacity-50" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Megaphone, Wallet, ShieldAlert, ClipboardList,
  Bell, ChevronRight, Images, CalendarDays,
} from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { listNotifikasi, bacaSemuaNotifikasi } from '@shared/services/notifikasi.js'
import { useNotifBadge } from '@shared/composables/useNotifBadge.js'
import { usePush } from '@shared/composables/usePush.js'

const router = useRouter()
const daftar = ref([])
const loading = ref(true)
const err = ref('')
const pushMsg = ref('')
const pushMsgOk = ref(true)
const { refresh: refreshBadge } = useNotifBadge()
const { status: pushStatus, aktifkan: aktifkanPush, matikan: matikanPush } = usePush()

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

async function onAktifkanPush() {
  pushMsg.value = ''
  const res = await aktifkanPush()
  pushMsgOk.value = !!res.ok
  pushMsg.value = res.ok ? 'Push berhasil diaktifkan.' : (res.error || 'Gagal')
}

async function onMatikanPush() {
  await matikanPush()
  pushMsgOk.value = true
  pushMsg.value = 'Push dimatikan di perangkat ini.'
}

onMounted(load)
</script>
