<template>
  <div :style="{ paddingTop: 'max(21px, calc(env(safe-area-inset-top, 0px) + 17px))' }">
    <!-- Skeleton beranda -->
    <div v-if="loading" class="animate-pulse" aria-hidden="true">
      <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3 text-center mb-3">
        <div class="w-[60px] h-[60px] rounded-[12px] bg-[var(--card2)]" />
        <div class="min-w-0 space-y-2">
          <div class="h-5 rounded-full bg-[var(--card2)] w-[70%] mx-auto" />
          <div class="h-3 rounded-full bg-[var(--card2)] w-[55%] mx-auto" />
        </div>
        <div class="w-[60px] h-[60px] rounded-[12px] bg-[var(--card2)]" />
      </div>
      <div class="h-px bg-[var(--line)] mb-2" />
      <div class="flex items-center justify-between gap-3 py-3.5 mb-1">
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
          <div class="w-11 h-11 rounded-full bg-[var(--card2)] shrink-0" />
          <div class="space-y-1.5 flex-1 min-w-0">
            <div class="h-3 rounded-full bg-[var(--card2)] w-[40%]" />
            <div class="h-3.5 rounded-full bg-[var(--card2)] w-[55%]" />
          </div>
        </div>
        <div class="flex gap-2 shrink-0">
          <div class="w-12 h-12 rounded-full bg-[var(--card2)]" />
          <div class="w-12 h-12 rounded-full bg-[var(--card2)]" />
        </div>
      </div>
      <div class="flex flex-col gap-2.5 mt-2">
        <div class="rounded-[20px] h-[140px] bg-[var(--card2)]" />
        <div class="grid grid-cols-2 gap-2.5">
          <div v-for="n in 4" :key="n" class="rounded-[18px] h-[108px] bg-[var(--card2)]" />
        </div>
        <div v-for="n in 6" :key="'m' + n" class="rounded-[18px] h-[64px] bg-[var(--card2)]" />
      </div>
    </div>

    <template v-else>
      <!-- Kop RT -->
      <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3 text-center mb-3">
        <div
          class="w-[60px] h-[60px] grid place-items-center shrink-0"
          :class="logoRtUrl
            ? 'rounded-[12px] bg-transparent'
            : 'rounded-full bg-[var(--card)] border border-[var(--line)] overflow-hidden'"
        >
          <img
            v-if="logoRtUrl"
            :src="logoRtUrl"
            alt="Logo RT"
            class="max-w-full max-h-full w-auto h-auto object-contain"
            @error="logoRtUrl = ''"
          />
          <Landmark v-else :size="26" class="text-[var(--mut)]" />
        </div>
        <div class="min-w-0">
          <p class="text-[22px] font-extrabold leading-tight m-0 text-[var(--text)]">{{ namaRt || 'RT' }}</p>
          <small class="block text-[13px] font-medium text-[var(--mut)] leading-snug">{{ namaPerumahan }}</small>
        </div>
        <div
          class="w-[60px] h-[60px] grid place-items-center shrink-0"
          :class="logoDesaUrl
            ? 'rounded-[12px] bg-transparent'
            : 'rounded-full bg-[var(--card)] border border-[var(--line)] overflow-hidden'"
        >
          <img
            v-if="logoDesaUrl"
            :src="logoDesaUrl"
            alt="Logo desa"
            class="max-w-full max-h-full w-auto h-auto object-contain"
            @error="logoDesaUrl = ''"
          />
          <Handshake v-else :size="26" class="text-[var(--mut)]" />
        </div>
      </div>

      <!-- Garis kop -->
      <div class="h-px bg-[var(--line)] mb-2" aria-hidden="true"></div>

      <!-- Bar selamat datang + foto + ikon -->
      <div class="flex items-center justify-between gap-3 py-3.5">
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
          <div
            class="w-11 h-11 rounded-full shrink-0 overflow-hidden bg-[var(--gd)] text-[var(--gm)] grid place-items-center text-[13px] font-bold"
          >
            <img
              v-if="fotoUrl"
              :src="fotoUrl"
              alt="Foto profil"
              class="w-full h-full object-cover"
              @error="fotoBroken = true"
            />
            <span v-else>{{ inisial }}</span>
          </div>
          <div class="min-w-0 flex-1 text-left">
            <p class="text-[12px] m-0 leading-snug text-[var(--mut)]">Selamat datang,</p>
            <p class="text-[14px] font-bold m-0 leading-snug text-[var(--text)] truncate">{{ namaWarga }}</p>
          </div>
        </div>
        <div class="flex items-center shrink-0" style="margin-right: -6px">
          <router-link
            to="/pengumuman"
            class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)] touch-manipulation"
            aria-label="Pengumuman"
          >
            <Megaphone :size="20" />
          </router-link>
          <router-link
            to="/notifikasi"
            class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)] relative touch-manipulation"
            aria-label="Notifikasi"
          >
            <Bell :size="20" />
            <span
              v-if="badge > 0"
              class="absolute top-1 right-1 min-w-[16px] h-[16px] px-0.5 rounded-full bg-[var(--g)] text-white text-[10px] font-bold grid place-items-center"
            >{{ badge > 9 ? '9+' : badge }}</span>
          </router-link>
        </div>
      </div>

      <div class="flex flex-col gap-2.5 mt-1">
        <div
          v-for="p in pengumuman"
          :key="p.id"
          class="rounded-[20px] overflow-hidden relative text-white shadow-[var(--sh)]"
          :style="cardStyle(true)"
        >
          <div class="p-5 relative z-10">
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <span class="flex items-center gap-2 text-[13px] font-semibold text-white/90">
                <Megaphone :size="16" /> Pengumuman
                <span v-if="p.hasLampiran" class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white/20">📎</span>
              </span>
              <span class="text-[12px] font-semibold px-3 py-1 rounded-full bg-white/20">
                {{ p.tanggal }}
              </span>
            </div>
            <h2 class="text-[17px] font-bold leading-snug mt-3 mb-1 text-white">{{ p.judul }}</h2>
            <p class="text-[13px] m-0 text-white/90">{{ p.ringkas }}</p>
            <button
              type="button"
              class="btn-press inline-flex items-center gap-1 mt-3.5 min-h-[44px] px-4 rounded-full font-bold text-[15px] bg-white text-[#0B6B34] touch-manipulation"
              :class="pressedKey === 'peng-' + p.id ? 'is-pressed' : ''"
              @click="goTo('/pengumuman/' + p.id, 'peng-' + p.id)"
            >
              Lihat detail <ChevronRight :size="18" />
            </button>
          </div>
          <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
        </div>

        <!-- Grid 2x2: Iuran · Kas · Ronda · Warga -->
        <div class="grid grid-cols-2 gap-2.5">
          <button
            v-for="tile in quickTiles"
            :key="tile.key"
            type="button"
            class="quick-tile text-left relative overflow-hidden touch-manipulation will-change-transform"
            :class="[
              tile.muted ? 'quick-tile--muted' : '',
              pressedKey === tile.key ? 'is-pressed' : '',
            ]"
            :style="tile.muted ? undefined : { background: tile.bg }"
            @click="goTo(tile.to, tile.key)"
          >
            <div class="relative z-10 flex flex-col h-full min-h-[108px] p-3.5">
              <div class="flex items-start gap-2.5">
                <div
                  class="w-10 h-10 rounded-[12px] grid place-items-center shrink-0"
                  :class="tile.muted ? 'bg-[var(--card2)] text-[var(--gm)]' : 'bg-white/20 text-white'"
                >
                  <component :is="tile.icon" :size="20" />
                </div>
                <div class="flex-1 min-w-0 pt-0.5">
                  <p
                    class="text-[13px] font-bold m-0 leading-snug line-clamp-2"
                    :class="tile.muted ? 'text-[var(--text)]' : 'text-white'"
                  >{{ tile.label }}</p>
                </div>
                <ChevronRight
                  :size="18"
                  class="shrink-0 mt-1 opacity-80"
                  :class="tile.muted ? 'text-[var(--mut)]' : 'text-white'"
                />
              </div>
              <p
                class="text-[16px] font-extrabold m-0 mt-auto pt-3 leading-tight tabular-nums truncate"
                :class="tile.muted ? 'text-[var(--mut)]' : 'text-white'"
              >{{ tile.value }}</p>
            </div>
            <div
              v-if="!tile.muted"
              class="absolute -right-6 -bottom-8 w-24 h-24 rounded-full bg-white/10 pointer-events-none"
            ></div>
          </button>
        </div>

        <!-- Menu list lainnya -->
        <button
          v-for="m in menus"
          :key="m.to"
          type="button"
          class="menu-row w-full text-left relative overflow-hidden touch-manipulation will-change-transform"
          :class="pressedKey === m.to ? 'is-pressed' : ''"
          :style="{ background: m.cardBg }"
          @click="goTo(m.to, m.to)"
        >
          <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-[64px]">
            <div class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0 bg-white/20 text-white">
              <component :is="m.icon" :size="22" />
            </div>
            <div class="flex-1 min-w-0 py-2.5">
              <p class="text-[15px] font-bold m-0 leading-tight text-white truncate">{{ m.label }}</p>
              <p
                v-if="m.headline"
                class="text-[13px] font-semibold m-0 mt-0.5 leading-tight text-white/85 tabular-nums truncate"
              >{{ m.headline }}</p>
            </div>
            <ChevronRight :size="18" class="shrink-0 text-white/70" />
          </div>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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
  Building2, Ambulance,
} from 'lucide-vue-next'

const { user } = useAuth()
const { badge, ensure } = useNotifBadge()

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

const styleAktif = { background: '#22B863' }
const styleSelesai = { background: '#6B7280' }
function cardStyle(aktif) {
  return aktif ? styleAktif : styleSelesai
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

const namaRt = ref('')
const namaPerumahan = ref('')
const logoRtUrl = ref('')
const logoDesaUrl = ref('')
const pengumuman = ref([])
const loading = ref(true)
const router = useRouter()
const pressedKey = ref('')
let navLock = false

async function goTo(to, key) {
  if (navLock) return
  navLock = true
  pressedKey.value = key
  await new Promise((r) => setTimeout(r, 90))
  pressedKey.value = ''
  await new Promise((r) => setTimeout(r, 160))
  try {
    await router.push(to)
  } finally {
    navLock = false
  }
}

const iuranStatus = ref('loading')
const iuranTitle = ref('Iuran yang belum di bayar')
const iuranNominal = ref('…')

const styleTagihan = '#F59E0B'
const styleLunas = '#22B863'
const styleLebih = '#3B82F6'

const iuranBg = computed(() => {
  if (iuranStatus.value === 'pending') return styleTagihan
  if (iuranStatus.value === 'lunas') return styleLunas
  if (iuranStatus.value === 'lebih') return styleLebih
  return null
})

function formatTgl(s) {
  if (!s) return ''
  const d = new Date(String(s).length === 10 ? s + 'T00:00:00' : String(s).replace(' ', 'T'))
  return Number.isNaN(d.getTime()) ? String(s).slice(0, 10) : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}
function ringkas(isi) {
  return plainFromMarkdown(isi, 100)
}
function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}
const rondaHeadline = ref('…')
const rondaDesc = ref('Ketuk untuk detail jadwal')
const rondaGroupName = ref('')
const kasHeadline = ref('…')

function weekIndex(d) {
  const x = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const day = (x.getDay() + 6) % 7
  x.setDate(x.getDate() - day)
  x.setHours(0, 0, 0, 0)
  return x.getTime()
}

function labelKapanTgl(tanggal) {
  if (!tanggal) return 'Belum ada'
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const tgl = new Date(String(tanggal).slice(0, 10) + 'T00:00:00')
  if (Number.isNaN(tgl.getTime())) return 'Belum ada'
  if (tgl.getTime() === today.getTime()) return 'Malam ini'
  const wToday = weekIndex(today)
  const wTgl = weekIndex(tgl)
  const diff = Math.round((wTgl - wToday) / (7 * 86400000))
  if (diff === 0) return 'Minggu ini'
  if (diff === 1) return 'Minggu depan'
  if (diff > 1) return diff + ' minggu lagi'
  if (diff < 0) return 'Sudah lewat'
  return 'Ronda'
}

function formatHariPendek(s) {
  if (!s) return ''
  const d = new Date(String(s).length === 10 ? s + 'T00:00:00' : String(s).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return String(s).slice(0, 10)
  return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' })
}

/** 4 card utama — grid 2x2 */
const quickTiles = computed(() => {
  const rt = namaRt.value || 'RT'
  return [
    {
      key: 'iuran',
      to: '/rincian-iuran',
      label: iuranTitle.value,
      value: iuranNominal.value,
      icon: Wallet,
      bg: iuranBg.value || '#F59E0B',
      muted: iuranStatus.value === 'loading',
    },
    {
      key: 'kas',
      to: '/keuangan',
      label: 'Total Kas ' + rt,
      value: kasHeadline.value,
      icon: Wallet,
      bg: '#A855F7',
      muted: false,
    },
    {
      key: 'ronda',
      to: '/ronda',
      label: rondaGroupName.value
        ? 'Jadwal Ronda (' + rondaGroupName.value + ')'
        : 'Jadwal Ronda',
      value: rondaHeadline.value,
      icon: ShieldCheck,
      bg: '#3B82F6',
      muted: false,
    },
    {
      key: 'warga',
      to: '/warga',
      label: 'Daftar warga ' + rt,
      value: 'Lihat daftar',
      icon: Users,
      bg: '#06B6D4',
      muted: false,
    },
  ]
})

/** Menu list di bawah grid (tanpa 4 yang sudah di grid) */
const menus = computed(() => [
  {
    to: '/galeri',
    label: 'Galeri RT',
    desc: 'Foto kegiatan warga',
    icon: Images,
    cardBg: '#EC4899',
  },
  {
    to: '/komunitas',
    label: 'Komunitas',
    desc: 'Kegiatan komunitas warga',
    icon: Trophy,
    cardBg: '#14B8A6',
  },
  {
    to: '/posyandu',
    label: 'Kader RT 002',
    desc: 'Kegiatan pengurus ibu-ibu',
    icon: HeartHandshake,
    cardBg: '#F97316',
  },
  {
    to: '/talim',
    label: 'DKM',
    desc: 'Kegiatan & informasi DKM',
    icon: BookOpen,
    cardBg: '#0EA5E9',
  },
  {
    to: '/rw',
    label: 'RW',
    desc: 'Informasi & kegiatan RW',
    icon: Building2,
    cardBg: '#64748B',
  },
  {
    to: '/ambulance',
    label: 'Ambulance',
    desc: 'Info & kontak ambulance',
    icon: Ambulance,
    cardBg: '#DC2626',
  },
])

onMounted(async () => {
  ensure()
  loading.value = true
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
    pengumuman.value = (pn.data || [])
      .filter((p) => isAktifTampil(p))
      .slice(0, 3)
      .map((p) => ({
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
    if (total > 0) {
      iuranStatus.value = 'pending'
      iuranTitle.value = 'Iuran yang belum di bayar'
      iuranNominal.value = rp(total)
    } else if (total < 0) {
      iuranStatus.value = 'lebih'
      iuranTitle.value = 'Kelebihan bayar'
      iuranNominal.value = rp(-total)
    } else {
      iuranStatus.value = 'lunas'
      iuranTitle.value = 'Iuran yang belum di bayar'
      iuranNominal.value = 'Lunas'
    }
  } else {
    iuranStatus.value = 'loading'
    iuranTitle.value = 'Iuran yang belum di bayar'
    iuranNominal.value = '—'
  }

  if (rn.ok && rn.data?.tanggal) {
    rondaHeadline.value = labelKapanTgl(rn.data.tanggal)
    const ng = String(rn.data.nama_group || '').trim()
    rondaGroupName.value = ng
    rondaDesc.value = formatHariPendek(rn.data.tanggal) + (ng ? ' · ' + ng : '') + ' · Ketuk untuk detail'
  } else {
    rondaHeadline.value = 'Belum ada'
    rondaDesc.value = 'Ketuk untuk melihat jadwal ronda'
    rondaGroupName.value = ''
  }

  if (kas.ok && kas.data) {
    kasHeadline.value = rp(kas.data.saldo ?? 0)
  } else {
    kasHeadline.value = '—'
  }

  const foto = user.value?.foto ? mediaUrl(user.value.foto) : ''
  if (foto) imgs.push(foto)

  await waitImages(imgs)
  loading.value = false
})
</script>

<style scoped>
.quick-tile {
  border-radius: 18px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  transform: scale(1);
  transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.quick-tile.is-pressed {
  transform: scale(0.96);
  transition: transform 0.08s ease-out;
}
.quick-tile--muted {
  background: var(--card);
  border: 1px solid var(--line);
  box-shadow: none;
}
.menu-row {
  border-radius: 18px;
  min-height: 64px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  transform: scale(1);
  transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.menu-row.is-pressed {
  transform: scale(0.96);
  transition: transform 0.08s ease-out;
}
.btn-press {
  transform: scale(1);
  transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.btn-press.is-pressed {
  transform: scale(0.94);
  transition: transform 0.08s ease-out;
}
:global(html[data-mode="dark"]) .quick-tile:not(.quick-tile--muted),
:global(html[data-mode="dark"]) .menu-row {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
}
:global(html:not([data-mode="dark"])) .quick-tile:not(.quick-tile--muted),
:global(html:not([data-mode="dark"])) .menu-row {
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.12);
}
@media (prefers-reduced-motion: reduce) {
  .quick-tile,
  .quick-tile.is-pressed,
  .menu-row,
  .menu-row.is-pressed,
  .btn-press,
  .btn-press.is-pressed {
    transition: none;
  }
}
</style>
