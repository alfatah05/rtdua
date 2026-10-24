<template>
  <div
    ref="homeRoot"
    class="home-root"
    :style="{ paddingTop: 'max(1.3125rem, calc(env(safe-area-inset-top, 0px) + 1.0625rem))' }"
  >
    <!-- Custom pull-to-refresh: spinner fixed di atas, ikut jarak tarik + resistance -->
    <div
      class="ptr-indicator"
      :class="{ 'is-animating': ptrAnimating }"
      :style="ptrIndicatorStyle"
      aria-hidden="true"
    >
      <div class="ptr-circle" :style="ptrCircleInnerStyle">
        <div
          class="ptr-spinner"
          :class="{ 'is-spin': ptrRefreshing }"
          :style="ptrSpinnerStyle"
        />
      </div>
    </div>

    <div v-if="loading" class="animate-pulse" aria-hidden="true">
      <div class="h-14 rounded-xl bg-[var(--card2)] mb-3" />
      <div class="h-36 rounded-2xl bg-[var(--card2)] mb-2.5" />
      <div class="grid grid-cols-2 gap-2.5 mb-2.5">
        <div v-for="n in 4" :key="n" class="h-[6.75rem] rounded-[1.125rem] bg-[var(--card2)]" />
      </div>
      <div v-for="n in 6" :key="'m'+n" class="h-16 rounded-[1.125rem] bg-[var(--card2)] mb-2" />
    </div>

    <template v-else>
      <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3 text-center mb-3">
        <div class="w-14 h-14 grid place-items-center shrink-0 rounded-full bg-[var(--card)] border border-[var(--line)] overflow-hidden">
          <img v-if="logoRtUrl" :src="logoRtUrl" alt="Logo RT" class="max-w-full max-h-full object-contain" @error="logoRtUrl = ''" />
          <Landmark v-else :size="22" class="text-[var(--mut)]" />
        </div>
        <div class="min-w-0">
          <p class="text-xl font-extrabold leading-tight m-0">{{ namaRt || 'RT' }}</p>
          <small class="block text-xs text-[var(--mut)]">{{ namaPerumahan }}</small>
        </div>
        <div class="w-14 h-14 grid place-items-center shrink-0 rounded-full bg-[var(--card)] border border-[var(--line)] overflow-hidden">
          <img v-if="logoDesaUrl" :src="logoDesaUrl" alt="Logo desa" class="max-w-full max-h-full object-contain" @error="logoDesaUrl = ''" />
          <Handshake v-else :size="22" class="text-[var(--mut)]" />
        </div>
      </div>
      <div class="h-px bg-[var(--line)] mb-2" />

      <div class="flex items-center justify-between gap-3 py-3.5">
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
          <div class="w-11 h-11 rounded-full shrink-0 overflow-hidden bg-[var(--gd)] text-[var(--gm)] grid place-items-center text-xs font-bold">
            <img v-if="fotoUrl" :src="fotoUrl" class="w-full h-full object-cover" @error="fotoBroken = true" />
            <span v-else>{{ inisial }}</span>
          </div>
          <div class="min-w-0 flex-1 text-left">
            <p class="text-xs m-0 text-[var(--mut)]">Hallo,</p>
            <p class="text-sm font-bold m-0 truncate">{{ namaWarga }}</p>
          </div>
        </div>
        <div class="flex items-center shrink-0" style="margin-right: -6px">
          <router-link to="/pengumuman" class="w-10 h-10 grid place-items-center rounded-full" aria-label="Pengumuman"><Megaphone :size="18" /></router-link>
          <router-link to="/notifikasi" class="w-10 h-10 grid place-items-center rounded-full relative" aria-label="Notifikasi">
            <Bell :size="18" />
            <span v-if="badge > 0" class="absolute top-1 right-1 min-w-[16px] h-[16px] px-0.5 rounded-full bg-[var(--g)] text-white text-[10px] font-bold grid place-items-center">{{ badge > 9 ? '9+' : badge }}</span>
          </router-link>
        </div>
      </div>

      <div class="flex flex-col gap-2.5 mt-2">
        <div v-for="p in pengumuman" :key="p.id" class="relative overflow-hidden rounded-2xl p-4" :style="{ background: '#22B863' }">
          <p class="text-xs font-semibold m-0 text-white/90">{{ p.tanggal }}</p>
          <h2 class="text-base font-bold leading-snug mt-3 mb-1 text-white">{{ p.judul }}</h2>
          <p class="text-xs m-0 text-white/90">{{ p.ringkas }}</p>
          <button type="button" class="inline-flex items-center gap-1 mt-3.5 min-h-11 px-4 rounded-full font-bold text-sm bg-white text-[#0B6B34]" @click="goTo('/pengumuman/' + p.id, 'peng-' + p.id)">
            Lihat detail <ChevronRight :size="16" />
          </button>
        </div>

        <div class="grid grid-cols-2 gap-2.5">
          <button
            v-for="tile in quickTiles"
            :key="tile.key"
            type="button"
            class="quick-tile text-left relative overflow-hidden"
            :class="[tile.muted ? 'quick-tile--muted' : '', pressedKey === tile.key ? 'is-pressed' : '']"
            :style="tile.muted ? undefined : { background: tile.bg }"
            @click="goTo(tile.to, tile.key)"
          >
            <div class="relative z-10 flex flex-col h-full min-h-[6.75rem] p-3.5">
              <div class="flex items-start gap-2.5">
                <div class="w-10 h-10 rounded-xl grid place-items-center shrink-0" :class="tile.muted ? 'bg-[var(--card2)] text-[var(--gm)]' : 'bg-white/20 text-white'">
                  <component :is="tile.icon" :size="18" />
                </div>
                <p class="text-xs font-bold m-0 leading-snug line-clamp-2 flex-1" :class="tile.muted ? 'text-[var(--text)]' : 'text-white'">{{ tile.label }}</p>
                <ChevronRight :size="16" class="shrink-0 mt-1" :class="tile.muted ? 'text-[var(--mut)]' : 'text-white'" />
              </div>
              <p class="text-base font-extrabold m-0 mt-auto pt-3 tabular-nums truncate" :class="tile.muted ? 'text-[var(--mut)]' : 'text-white'">{{ tile.value }}</p>
            </div>
          </button>
        </div>

        <button
          v-for="m in menus"
          :key="m.to"
          type="button"
          class="menu-row w-full text-left relative overflow-hidden"
          :class="pressedKey === m.to ? 'is-pressed' : ''"
          :style="{ background: m.cardBg }"
          @click="goTo(m.to, m.to)"
        >
          <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-16">
            <div class="w-11 h-11 rounded-[0.875rem] grid place-items-center shrink-0 bg-white/20 text-white">
              <component :is="m.icon" :size="20" />
            </div>
            <p class="flex-1 text-sm font-bold m-0 text-white truncate">{{ m.label }}</p>
            <ChevronRight :size="16" class="shrink-0 text-white/70" />
          </div>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { getPengaturan } from '@shared/services/pengaturan.js'
import { listPengumuman } from '@shared/services/konten.js'
import { portalRingkasan, portalKas } from '@shared/services/keuangan.js'
import { malamTerdekat } from '@shared/services/ronda.js'
import { mediaUrl } from '@shared/services/upload.js'
import { plainFromMarkdown } from '@shared/utils/markdown.js'
import { waitImages } from '@shared/utils/waitImages.js'
import { useAuth } from '@shared/composables/useAuth.js'
import { useNotifBadge } from '@shared/composables/useNotifBadge.js'
import {
  Landmark, Handshake, Megaphone, Bell, ChevronRight, Wallet,
  ShieldCheck, Images, Users, HeartHandshake, BookOpen, Trophy,
  Building2, Ambulance, Gift,
} from 'lucide-vue-next'

const { user } = useAuth()
const { badge, ensure } = useNotifBadge()
const router = useRouter()

const namaWarga = computed(() => user.value?.nama || 'Warga')
const fotoBroken = ref(false)
const fotoUrl = computed(() => {
  if (fotoBroken.value) return ''
  const f = user.value?.foto
  return f ? mediaUrl(f) : ''
})
const inisial = computed(() => {
  const n = namaWarga.value || 'W'
  return n.split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase()
})

const namaRt = ref('')
const namaPerumahan = ref('')
const logoRtUrl = ref('')
const logoDesaUrl = ref('')
const pengumuman = ref([])
const loading = ref(true)
const pressedKey = ref('')
let navLock = false

const iuranStatus = ref('loading')
const iuranTitle = ref('Iuran yang belum di bayar')
const iuranNominal = ref('…')
const rondaHeadline = ref('…')
const rondaDesc = ref('')
const rondaGroupName = ref('')
const kasHeadline = ref('…')

function rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID') }
function ringkas(isi) {
  const t = plainFromMarkdown(isi || '').replace(/\s+/g, ' ').trim()
  return t.length > 80 ? t.slice(0, 80) + '…' : t
}
function formatTgl(s) {
  if (!s) return ''
  const d = new Date(String(s).length === 10 ? s + 'T00:00:00' : String(s).replace(' ', 'T'))
  return Number.isNaN(d.getTime()) ? String(s).slice(0, 10) : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}
function isAktifTampil(p) {
  const now = Date.now()
  if (!p.tampil_selesai) return false
  const t1 = new Date(String(p.tampil_selesai).replace(' ', 'T')).getTime()
  if (isNaN(t1) || now > t1) return false
  if (p.tampil_mulai) {
    const t0 = new Date(String(p.tampil_mulai).replace(' ', 'T')).getTime()
    if (!isNaN(t0) && now < t0) return false
  }
  return true
}
function formatHariPendek(s) {
  if (!s) return ''
  const d = new Date(String(s).length === 10 ? s + 'T00:00:00' : String(s).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return String(s).slice(0, 10)
  return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' })
}
function labelKapanTgl(s) {
  if (!s) return '—'
  const d = new Date(String(s).length === 10 ? s + 'T00:00:00' : String(s).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return String(s).slice(0, 10)
  const now = new Date()
  const a = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const b = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const diff = Math.round((b - a) / 86400000)
  if (diff === 0) return 'Hari ini'
  if (diff === 1) return 'Besok'
  if (diff === -1) return 'Kemarin'
  return formatHariPendek(s)
}

async function goTo(to, key) {
  if (navLock) return
  navLock = true
  pressedKey.value = key
  await new Promise((r) => setTimeout(r, 90))
  pressedKey.value = ''
  await new Promise((r) => setTimeout(r, 160))
  try { await router.push(to) } finally { navLock = false }
}

const iuranBg = computed(() => {
  if (iuranStatus.value === 'pending') return '#F59E0B'
  if (iuranStatus.value === 'lunas') return '#22B863'
  if (iuranStatus.value === 'lebih') return '#3B82F6'
  return null
})

const quickTiles = computed(() => {
  const rt = namaRt.value || 'RT'
  return [
    { key: 'iuran', to: '/rincian-iuran', label: iuranTitle.value, value: iuranNominal.value, icon: Wallet, muted: !iuranBg.value, bg: iuranBg.value || undefined },
    { key: 'kas', to: '/keuangan', label: 'Total Kas ' + rt, value: kasHeadline.value, icon: Wallet, bg: '#22B863' },
    { key: 'ronda', to: '/ronda', label: rondaGroupName.value ? 'Jadwal Ronda (' + rondaGroupName.value + ')' : 'Jadwal Ronda', value: rondaHeadline.value, icon: ShieldCheck, bg: '#8B5CF6' },
    { key: 'warga', to: '/warga', label: 'Daftar warga ' + rt, value: 'Lihat daftar', icon: Users, bg: '#0EA5E9' },
  ]
})

const menus = computed(() => [
  { to: '/galeri', label: 'Galeri RT', icon: Images, cardBg: '#EC4899' },
  { to: '/komunitas', label: 'Komunitas', icon: Trophy, cardBg: '#14B8A6' },
  { to: '/posyandu', label: 'Kader RT 002', icon: HeartHandshake, cardBg: '#F97316' },
  { to: '/talim', label: 'DKM', icon: BookOpen, cardBg: '#0EA5E9' },
  { to: '/rw', label: 'RW', icon: Building2, cardBg: '#64748B' },
  { to: '/ambulance', label: 'Ambulance', icon: Ambulance, cardBg: '#DC2626' },
  { to: '/reward', label: 'Reward', icon: Gift, cardBg: '#F59E0B' },
])

async function loadHome({ showSkeleton = true } = {}) {
  ensure()
  if (showSkeleton) loading.value = true
  const [pg, pn, iu, rn, kas] = await Promise.all([
    getPengaturan(), listPengumuman({ beranda: true }), portalRingkasan(), malamTerdekat(), portalKas(),
  ])
  const imgs = []
  if (pg.ok && pg.data) {
    namaRt.value = pg.data.nama_rt || 'RT'
    namaPerumahan.value = pg.data.nama_perumahan || ''
    logoRtUrl.value = pg.data.logo_rt ? mediaUrl(pg.data.logo_rt) : ''
    logoDesaUrl.value = pg.data.logo_desa ? mediaUrl(pg.data.logo_desa) : ''
    if (logoRtUrl.value) imgs.push(logoRtUrl.value)
    if (logoDesaUrl.value) imgs.push(logoDesaUrl.value)
  }
  if (pn.ok) {
    pengumuman.value = (pn.data || []).filter(isAktifTampil).slice(0, 3).map((p) => ({
      id: p.id, judul: p.judul, ringkas: ringkas(p.isi),
      tanggal: formatTgl(p.diterbitkan_pada || p.created_at),
    }))
  }
  if (iu.ok && iu.data) {
    const total = Number(iu.data.total ?? iu.data.total_tagihan ?? 0)
    if (total > 0) { iuranStatus.value = 'pending'; iuranTitle.value = 'Iuran yang belum di bayar'; iuranNominal.value = rp(total) }
    else if (total < 0) { iuranStatus.value = 'lebih'; iuranTitle.value = 'Kelebihan bayar'; iuranNominal.value = rp(-total) }
    else { iuranStatus.value = 'lunas'; iuranTitle.value = 'Iuran yang belum di bayar'; iuranNominal.value = 'Lunas' }
  } else {
    iuranStatus.value = 'loading'; iuranNominal.value = '—'
  }
  if (rn.ok && rn.data?.tanggal) {
    rondaHeadline.value = labelKapanTgl(rn.data.tanggal)
    const ng = String(rn.data.nama_group || '').trim()
    rondaGroupName.value = ng
    rondaDesc.value = formatHariPendek(rn.data.tanggal) + (ng ? ' · ' + ng : '')
  } else {
    rondaHeadline.value = 'Belum ada'; rondaGroupName.value = ''
  }
  kasHeadline.value = (kas.ok && kas.data) ? rp(kas.data.saldo ?? 0) : '—'
  const foto = user.value?.foto ? mediaUrl(user.value.foto) : ''
  if (foto) imgs.push(foto)
  await waitImages(imgs)
  if (showSkeleton) loading.value = false
}

/* Custom pull-to-refresh (overscroll resistance)
 * Spinner fixed top-center — bukan ikut koordinat jari.
 * Jarak tarik → resistance → posisi Y + opacity + rotasi progres.
 */
const PTR_THRESHOLD = 70
const PTR_MAX = 120
const PTR_HIDDEN_Y = -52
const PTR_LOADING_OFFSET = 108

const homeRoot = ref(null)
const ptrOffset = ref(0)
const ptrRefreshing = ref(false)
const ptrAnimating = ref(false)

let ptrStartY = 0
let ptrStartX = 0
let ptrTracking = false
let ptrArmed = false

function scrollTop() {
  return window.scrollY || document.documentElement.scrollTop || 0
}

function applyResistance(dy) {
  if (dy <= 0) return 0
  return PTR_MAX * (1 - 1 / (dy / PTR_MAX + 1))
}

const ptrProgress = computed(() => Math.min(1, ptrOffset.value / PTR_THRESHOLD))

const ptrIndicatorStyle = computed(() => {
  const y = PTR_HIDDEN_Y + ptrOffset.value
  const op = ptrRefreshing.value ? 1 : Math.min(1, ptrOffset.value / 24)
  return {
    transform: `translate3d(-50%, ${y}px, 0)`,
    opacity: op,
  }
})

const ptrCircleInnerStyle = computed(() => {
  const s = ptrRefreshing.value ? 1 : (0.72 + 0.28 * ptrProgress.value)
  return { transform: `scale(${s})` }
})

const ptrSpinnerStyle = computed(() => {
  if (ptrRefreshing.value) return {}
  const deg = Math.round(ptrProgress.value * 270)
  return { transform: `rotate(${deg}deg)` }
})

function onPtrStart(e) {
  if (ptrRefreshing.value || ptrAnimating.value) return
  if (scrollTop() > 1) { ptrArmed = false; return }
  const t = e.touches?.[0]
  if (!t) return
  ptrArmed = true
  ptrTracking = false
  ptrStartY = t.clientY
  ptrStartX = t.clientX
  ptrAnimating.value = false
}

function onPtrMove(e) {
  if (!ptrArmed || ptrRefreshing.value) return
  const t = e.touches?.[0]
  if (!t) return
  const dy = t.clientY - ptrStartY
  const dx = t.clientX - ptrStartX
  if (!ptrTracking) {
    if (dy < 6) return
    if (Math.abs(dx) > Math.abs(dy) * 0.85) { ptrArmed = false; return }
    if (scrollTop() > 1) { ptrArmed = false; return }
    ptrTracking = true
  }
  if (dy > 0) {
    e.preventDefault()
    ptrOffset.value = applyResistance(dy)
  } else {
    ptrOffset.value = 0
  }
}

function animateOffsetTo(target, ms = 280) {
  return new Promise((resolve) => {
    ptrAnimating.value = true
    ptrOffset.value = target
    window.setTimeout(() => {
      ptrAnimating.value = false
      resolve()
    }, ms)
  })
}

async function onPtrEnd() {
  if (!ptrTracking && !ptrArmed) return
  const shouldRefresh =
    ptrTracking && ptrOffset.value >= PTR_THRESHOLD && !ptrRefreshing.value
  ptrArmed = false
  ptrTracking = false

  if (shouldRefresh) {
    ptrRefreshing.value = true
    await animateOffsetTo(PTR_LOADING_OFFSET, 240)
    try {
      await loadHome({ showSkeleton: false })
    } finally {
      await animateOffsetTo(0, 300)
      ptrRefreshing.value = false
      ptrOffset.value = 0
      ptrAnimating.value = false
    }
  } else {
    await animateOffsetTo(0, 260)
    ptrOffset.value = 0
  }
}

onMounted(async () => {
  await loadHome({ showSkeleton: true })
  const el = homeRoot.value
  if (el) {
    el.addEventListener('touchstart', onPtrStart, { passive: true })
    el.addEventListener('touchmove', onPtrMove, { passive: false })
    el.addEventListener('touchend', onPtrEnd, { passive: true })
    el.addEventListener('touchcancel', onPtrEnd, { passive: true })
  }
})

onUnmounted(() => {
  const el = homeRoot.value
  if (el) {
    el.removeEventListener('touchstart', onPtrStart)
    el.removeEventListener('touchmove', onPtrMove)
    el.removeEventListener('touchend', onPtrEnd)
    el.removeEventListener('touchcancel', onPtrEnd)
  }
})
</script>

<style scoped>
.quick-tile {
  border-radius: 1.125rem;
  box-shadow: 0 2px 10px rgba(0,0,0,.08);
  transform: scale(1);
  transition: transform .28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.quick-tile.is-pressed { transform: scale(0.96); transition: transform .08s ease-out; }
.quick-tile--muted { background: var(--card); border: 1px solid var(--line); box-shadow: none; }
.menu-row {
  border-radius: 1.125rem; min-height: 4rem;
  box-shadow: 0 2px 10px rgba(0,0,0,.08);
  transform: scale(1);
  transition: transform .28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.menu-row.is-pressed { transform: scale(0.96); transition: transform .08s ease-out; }
.ptr-indicator {
  position: fixed;
  top: 0;
  left: 50%;
  width: 44px;
  height: 44px;
  z-index: 80;
  pointer-events: none;
  will-change: transform, opacity;
}
.ptr-indicator.is-animating {
  transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1),
              opacity 0.24s ease;
}
.ptr-circle {
  width: 44px;
  height: 44px;
  border-radius: 9999px;
  background: #FFFFFF;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.14), 0 1px 3px rgba(0, 0, 0, 0.08);
  display: grid;
  place-items: center;
  will-change: transform;
}
.ptr-spinner {
  width: 20px;
  height: 20px;
  border-radius: 9999px;
  border: 2.5px solid rgba(16, 22, 20, 0.12);
  border-top-color: #0A8F44;
  box-sizing: border-box;
  will-change: transform;
}
.ptr-spinner.is-spin {
  animation: ptr-spin 0.7s linear infinite;
}
@keyframes ptr-spin {
  to { transform: rotate(360deg); }
}
@media (prefers-reduced-motion: reduce) {
  .ptr-indicator.is-animating { transition: none; }
  .ptr-spinner.is-spin { animation: none; }
}
</style>
