<template>
  <div>
    <!-- Skeleton beranda (header/nav tidak ikut) -->
    <div v-if="loading" class="animate-pulse" aria-hidden="true">
      <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3 text-center mb-2">
        <div class="w-[60px] h-[60px] rounded-[12px] bg-[var(--card2)]" />
        <div class="min-w-0 space-y-2">
          <div class="h-5 rounded-full bg-[var(--card2)] w-[70%] mx-auto" />
          <div class="h-3 rounded-full bg-[var(--card2)] w-[55%] mx-auto" />
        </div>
        <div class="w-[60px] h-[60px] rounded-[12px] bg-[var(--card2)]" />
      </div>
      <div class="h-px bg-[var(--line)] my-4" />
      <div class="flex flex-col gap-2.5">
        <div class="rounded-[20px] h-[140px] bg-[var(--card2)]" />
        <div class="rounded-[18px] h-[64px] bg-[var(--card2)]" />
        <div v-for="n in 6" :key="n" class="rounded-[18px] h-[64px] bg-[var(--card2)]" />
      </div>
    </div>

    <template v-else>
      <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3 text-center mb-2">
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
      <div class="h-px bg-[var(--line)] my-4"></div>

      <div class="flex flex-col gap-2.5">
        <!-- Pengumuman: card tidak dianimasi; hanya tombol Lihat detail -->
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

        <!-- Iuran + menu: rounded rectangle style -->
        <button
          type="button"
          class="menu-row w-full text-left relative overflow-hidden touch-manipulation will-change-transform"
          :class="[
            iuranStatus === 'loading' ? 'menu-row--muted' : '',
            pressedKey === 'iuran' ? 'is-pressed' : '',
          ]"
          :style="iuranStatus === 'loading' ? undefined : iuranCardStyle"
          @click="goTo('/rincian-iuran', 'iuran')"
        >
          <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-[64px]">
            <div
              class="w-11 h-11 rounded-[14px] grid place-items-center shrink-0"
              :class="iuranStatus === 'loading' ? 'bg-[var(--card2)] text-[var(--gm)]' : 'bg-white/20 text-white'"
            >
              <Wallet :size="22" />
            </div>
            <div class="flex-1 min-w-0 py-2.5">
              <p
                class="text-[15px] font-bold m-0 leading-tight truncate"
                :class="iuranStatus === 'loading' ? 'text-[var(--text)]' : 'text-white'"
              >{{ iuranTitle }}</p>
              <p
                class="text-[13px] font-semibold m-0 mt-0.5 leading-tight tabular-nums"
                :class="iuranStatus === 'loading' ? 'text-[var(--mut)]' : 'text-white/85'"
              >{{ iuranNominal }}</p>
            </div>
            <ChevronRight
              :size="18"
              class="shrink-0 opacity-70"
              :class="iuranStatus === 'loading' ? 'text-[var(--mut)]' : 'text-white'"
            />
          </div>
        </button>

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
import {
  Landmark, Handshake, Megaphone, ChevronRight, Wallet,
  ShieldCheck, Images, Users, HeartHandshake, BookOpen
} from 'lucide-vue-next'

const styleAktif = {
  background: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)',
}
const styleSelesai = {
  background: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.18), transparent 55%), linear-gradient(145deg, #9CA3AF, #6B7280 55%, #4B5563)',
}
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

/** Tekan → mantul naik → baru navigasi */
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
const iuranHint = ref('Ketuk untuk melihat rincian')

const styleTagihan = {
  background: 'linear-gradient(105deg, #F59E0B 0%, #B45309 100%)',
}
const styleLunas = {
  background: 'linear-gradient(105deg, #22B863 0%, #0B8442 100%)',
}
const styleLebih = {
  background: 'linear-gradient(105deg, #3B82F6 0%, #1D4ED8 100%)',
}
const styleLoading = {
  background: 'var(--card2)',
}

const iuranCardStyle = computed(() => {
  if (iuranStatus.value === 'pending') return styleTagihan
  if (iuranStatus.value === 'lunas') return styleLunas
  if (iuranStatus.value === 'lebih') return styleLebih
  return styleLoading
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
  if (!tanggal) return 'Belum ada jadwal'
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const tgl = new Date(String(tanggal).slice(0, 10) + 'T00:00:00')
  if (Number.isNaN(tgl.getTime())) return 'Belum ada jadwal'
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

const menus = computed(() => [
  {
    to: '/ronda',
    label: rondaGroupName.value ? ('Jadwal Ronda (' + rondaGroupName.value + ')') : 'Jadwal Ronda',
    headline: rondaHeadline.value,
    desc: rondaDesc.value,
    icon: ShieldCheck,
    cardBg: 'linear-gradient(105deg, #3B82F6 0%, #1D4ED8 100%)',
  },
  {
    to: '/keuangan',
    label: 'Jumlah kas ' + (namaRt.value || 'RT'),
    headline: kasHeadline.value,
    desc: 'Ketuk untuk riwayat kas',
    icon: Wallet,
    cardBg: 'linear-gradient(105deg, #A855F7 0%, #6D28D9 100%)',
  },
  {
    to: '/galeri',
    label: 'Galeri RT',
    desc: 'Foto kegiatan warga',
    icon: Images,
    cardBg: 'linear-gradient(105deg, #EC4899 0%, #BE185D 100%)',
  },
  {
    to: '/warga',
    label: 'Warga',
    desc: 'Daftar warga RT 002',
    icon: Users,
    cardBg: 'linear-gradient(105deg, #F87171 0%, #DC2626 100%)',
  },
  {
    to: '/posyandu',
    label: 'Kader RT 002',
    desc: 'Kegiatan pengurus ibu-ibu',
    icon: HeartHandshake,
    cardBg: 'linear-gradient(105deg, #F97316 0%, #C2410C 100%)',
  },
  {
    to: '/talim',
    label: 'DKM',
    desc: 'Kegiatan & informasi DKM',
    icon: BookOpen,
    cardBg: 'linear-gradient(105deg, #0EA5E9 0%, #0369A1 100%)',
  },
])

onMounted(async () => {
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
      iuranHint.value = 'Belum lunas · Ketuk untuk rincian'
    } else if (total < 0) {
      iuranStatus.value = 'lebih'
      iuranTitle.value = 'Kelebihan bayar'
      iuranNominal.value = rp(-total)
      iuranHint.value = 'Saldo menguntungkan · Ketuk untuk rincian'
    } else {
      iuranStatus.value = 'lunas'
      iuranTitle.value = 'Iuran yang belum di bayar'
      iuranNominal.value = 'Lunas'
      iuranHint.value = 'Semua tagihan sudah dibayar'
    }
  } else {
    iuranStatus.value = 'loading'
    iuranTitle.value = 'Iuran yang belum di bayar'
    iuranNominal.value = '—'
    iuranHint.value = 'Ketuk untuk melihat rincian'
  }

  if (rn.ok && rn.data?.tanggal) {
    rondaHeadline.value = labelKapanTgl(rn.data.tanggal)
    const ng = String(rn.data.nama_group || '').trim()
    rondaGroupName.value = ng
    rondaDesc.value = formatHariPendek(rn.data.tanggal) + (ng ? ' · ' + ng : '') + ' · Ketuk untuk detail'
  } else {
    rondaHeadline.value = 'Belum ada jadwal'
    rondaDesc.value = 'Ketuk untuk melihat jadwal ronda'
    rondaGroupName.value = ''
  }

  if (kas.ok && kas.data) {
    kasHeadline.value = rp(kas.data.saldo ?? 0)
  } else {
    kasHeadline.value = '—'
  }

  await waitImages(imgs)
  loading.value = false
})
</script>

<style scoped>
/* Rounded rectangle — mirip list Apple Arcade, bukan capsule */
.menu-row {
  border-radius: 18px;
  min-height: 64px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  transform: scale(1);
  /* naik: spring bounce */
  transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.menu-row.is-pressed {
  transform: scale(0.96);
  /* turun: cepat */
  transition: transform 0.08s ease-out;
}
.menu-row--muted {
  background: var(--card);
  border: 1px solid var(--line);
  box-shadow: none;
}
.btn-press {
  transform: scale(1);
  transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.btn-press.is-pressed {
  transform: scale(0.94);
  transition: transform 0.08s ease-out;
}
/* Dark mode: redupkan gradient agar tidak silau */
:global(html[data-mode="dark"]) .menu-row:not(.menu-row--muted) {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
  filter: brightness(0.82) saturate(0.9);
}
/* Light mode: kontras di atas bg putih */
:global(html:not([data-mode="dark"])) .menu-row:not(.menu-row--muted) {
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.12);
}
@media (prefers-reduced-motion: reduce) {
  .menu-row,
  .menu-row.is-pressed,
  .btn-press,
  .btn-press.is-pressed {
    transition: none;
  }
}
</style>
