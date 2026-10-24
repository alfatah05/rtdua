<template>
  <div
    ref="homeRoot"
    class="home-root"
    :style="{ paddingTop: 'max(1.3125rem, calc(env(safe-area-inset-top, 0px) + 1.0625rem))' }"
  >
    <div
      v-show="ptrVisible"
      class="ptr-circle"
      :style="ptrCircleStyle"
      aria-hidden="true"
    >
      <div class="ptr-spinner" :class="{ 'is-spin': ptrRefreshing }" />
    </div>
    <HomeBerandaBody
      :loading="loading"
      v-bind="bodyProps"
      @go="onGo"
      @foto-error="fotoBroken = true"
      @logo-rt-error="logoRtUrl = ''"
      @logo-desa-error="logoDesaUrl = ''"
    />
  </div>
</template>
<script setup>
import { ref, computed, onMounted, onUnmounted, reactive, toRefs } from 'vue'
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
import HomeBerandaBody from './HomeBerandaBody.vue'

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

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}
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
  const selesai = p.tampil_selesai
  if (!selesai) return false
  const t1 = new Date(String(selesai).replace(' ', 'T')).getTime()
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

async function onGo(to, key) {
  if (navLock) return
  navLock = true
  pressedKey.value = key
  await new Promise((r) => setTimeout(r, 90))
  pressedKey.value = ''
  await new Promise((r) => setTimeout(r, 160))
  try { await router.push(to) } finally { navLock = false }
}

async function loadHome({ showSkeleton = true } = {}) {
  ensure()
  if (showSkeleton) loading.value = true
  const [pg, pn, iu, rn, kas] = await Promise.all([
    getPengaturan(),
    listPengumuman({ beranda: true }),
    portalRingkasan(),
    malamTerdekat(),
    portalKas(),
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
    pengumuman.value = (pn.data || []).filter((p) => isAktifTampil(p)).slice(0, 3).map((p) => ({
      id: p.id,
      judul: p.judul,
      ringkas: ringkas(p.isi),
      tanggal: formatTgl(p.diterbitkan_pada || p.created_at),
      hasLampiran: !!p.lampiran_file,
      tampil_mulai: p.tampil_mulai,
      tampil_selesai: p.tampil_selesai,
    }))
  }
  if (iu.ok && iu.data) {
    const total = Number(iu.data.total ?? iu.data.total_tagihan ?? 0)
    if (total > 0) { iuranStatus.value = 'pending'; iuranTitle.value = 'Iuran yang belum di bayar'; iuranNominal.value = rp(total) }
    else if (total < 0) { iuranStatus.value = 'lebih'; iuranTitle.value = 'Kelebihan bayar'; iuranNominal.value = rp(-total) }
    else { iuranStatus.value = 'lunas'; iuranTitle.value = 'Iuran yang belum di bayar'; iuranNominal.value = 'Lunas' }
  } else {
    iuranStatus.value = 'loading'; iuranTitle.value = 'Iuran yang belum di bayar'; iuranNominal.value = '—'
  }
  if (rn.ok && rn.data?.tanggal) {
    rondaHeadline.value = labelKapanTgl(rn.data.tanggal)
    const ng = String(rn.data.nama_group || '').trim()
    rondaGroupName.value = ng
    rondaDesc.value = formatHariPendek(rn.data.tanggal) + (ng ? ' · ' + ng : '') + ' · Ketuk untuk detail'
  } else {
    rondaHeadline.value = 'Belum ada'; rondaDesc.value = 'Ketuk untuk melihat jadwal ronda'; rondaGroupName.value = ''
  }
  if (kas.ok && kas.data) kasHeadline.value = rp(kas.data.saldo ?? 0)
  else kasHeadline.value = '—'
  const foto = user.value?.foto ? mediaUrl(user.value.foto) : ''
  if (foto) imgs.push(foto)
  await waitImages(imgs)
  if (showSkeleton) loading.value = false
}

const bodyProps = computed(() => ({
  namaRt: namaRt.value,
  namaPerumahan: namaPerumahan.value,
  logoRtUrl: logoRtUrl.value,
  logoDesaUrl: logoDesaUrl.value,
  fotoUrl: fotoUrl.value,
  inisial: inisial.value,
  namaWarga: namaWarga.value,
  badge: badge.value,
  pengumuman: pengumuman.value,
  iuranStatus: iuranStatus.value,
  iuranTitle: iuranTitle.value,
  iuranNominal: iuranNominal.value,
  rondaHeadline: rondaHeadline.value,
  rondaDesc: rondaDesc.value,
  rondaGroupName: rondaGroupName.value,
  kasHeadline: kasHeadline.value,
  pressedKey: pressedKey.value,
}))

const PTR_THRESHOLD = 72
const homeRoot = ref(null)
const ptrPull = ref(0)
const ptrX = ref(0)
const ptrY = ref(0)
const ptrRefreshing = ref(false)
const ptrVisible = computed(() => ptrPull.value > 8 || ptrRefreshing.value)
const ptrCircleStyle = computed(() => ({
  transform: `translate3d(${ptrX.value}px, ${ptrY.value}px, 0) translate(-50%, -50%)`,
  opacity: ptrRefreshing.value ? 1 : Math.min(1, ptrPull.value / PTR_THRESHOLD),
}))
let ptrStartY = 0, ptrStartX = 0, ptrTracking = false, ptrArmed = false
function scrollTop() { return window.scrollY || document.documentElement.scrollTop || 0 }
function onPtrStart(e) {
  if (ptrRefreshing.value) return
  if (scrollTop() > 2) { ptrArmed = false; return }
  const touch = e.touches?.[0]; if (!touch) return
  ptrArmed = true; ptrTracking = false
  ptrStartY = touch.clientY; ptrStartX = touch.clientX
  ptrX.value = touch.clientX; ptrY.value = touch.clientY
}
function onPtrMove(e) {
  if (!ptrArmed || ptrRefreshing.value) return
  const touch = e.touches?.[0]; if (!touch) return
  const dy = touch.clientY - ptrStartY, dx = touch.clientX - ptrStartX
  if (!ptrTracking) {
    if (dy < 8) return
    if (Math.abs(dx) > Math.abs(dy) * 0.9) { ptrArmed = false; return }
    if (scrollTop() > 2) { ptrArmed = false; return }
    ptrTracking = true
  }
  if (dy > 0) {
    e.preventDefault()
    ptrPull.value = Math.min(140, dy * 0.55)
    ptrX.value = touch.clientX; ptrY.value = touch.clientY
  } else ptrPull.value = 0
}
async function onPtrEnd() {
  if (!ptrTracking && !ptrArmed) return
  const should = ptrTracking && ptrPull.value >= PTR_THRESHOLD && !ptrRefreshing.value
  ptrArmed = false; ptrTracking = false
  if (should) {
    ptrRefreshing.value = true
    try { await loadHome({ showSkeleton: false }) }
    finally { ptrRefreshing.value = false; ptrPull.value = 0 }
  } else ptrPull.value = 0
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
.ptr-circle {
  position: fixed; top: 0; left: 0; width: 44px; height: 44px; border-radius: 9999px;
  background: #FFFFFF; box-shadow: 0 4px 16px rgba(0,0,0,.14), 0 1px 3px rgba(0,0,0,.08);
  z-index: 80; pointer-events: none; display: grid; place-items: center;
  will-change: transform, opacity;
}
.ptr-spinner {
  width: 20px; height: 20px; border-radius: 9999px;
  border: 2.5px solid rgba(16,22,20,.15); border-top-color: #0A8F44; box-sizing: border-box;
}
.ptr-spinner.is-spin { animation: ptr-spin .7s linear infinite; }
@keyframes ptr-spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .ptr-spinner.is-spin { animation: none; } }
</style>
