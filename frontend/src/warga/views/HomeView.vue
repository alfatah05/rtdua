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
        <div class="rounded-[20px] h-[96px] bg-[var(--card2)]" />
        <div v-for="n in 6" :key="n" class="rounded-[20px] h-[96px] bg-[var(--card2)]" />
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
              class="inline-flex items-center gap-1 mt-3.5 min-h-[44px] px-4 rounded-full font-bold text-[15px] bg-white text-[#0B6B34] transition-transform duration-150 ease-out touch-manipulation"
              :class="pressedKey === 'peng-' + p.id ? 'scale-95' : 'scale-100'"
              @click="goTo('/pengumuman/' + p.id, 'peng-' + p.id)"
            >
              Lihat detail <ChevronRight :size="18" />
            </button>
          </div>
          <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
        </div>

        <!-- Iuran: animasi klik + delay navigasi -->
        <button
          type="button"
          class="w-full min-h-[96px] rounded-[20px] p-5 text-left relative overflow-hidden shadow-[var(--sh)] transition-transform duration-150 ease-out touch-manipulation will-change-transform"
          :class="[
            iuranStatus === 'loading' ? 'border border-[var(--line)]' : '',
            pressedKey === 'iuran' ? 'scale-[0.96]' : 'scale-100',
          ]"
          :style="iuranCardStyle"
          @click="goTo('/rincian-iuran', 'iuran')"
        >
          <div class="relative z-10 flex items-center gap-3">
            <div
              class="w-11 h-11 rounded-full grid place-items-center shrink-0"
              :class="iuranStatus === 'loading' ? 'bg-[var(--card)] text-[var(--gm)]' : 'bg-white/20 text-white'"
            >
              <Wallet :size="20" />
            </div>
            <div class="flex-1 min-w-0">
              <p
                class="text-[13px] font-semibold m-0"
                :class="iuranStatus === 'loading' ? 'text-[var(--mut)]' : 'text-white/90'"
              >
                {{ iuranTitle }}
              </p>
              <p
                class="text-[26px] font-extrabold tracking-tight m-0 mt-1 leading-none"
                :class="iuranStatus === 'loading' ? 'text-[var(--text)]' : 'text-white'"
              >
                {{ iuranNominal }}
              </p>
              <p
                class="text-[13px] m-0 mt-1.5 flex items-center gap-1"
                :class="iuranStatus === 'loading' ? 'text-[var(--mut)]' : 'text-white/85'"
              >
                <span>{{ iuranHint }}</span>
                <ChevronRight :size="16" class="opacity-80 shrink-0" />
              </p>
            </div>
          </div>
          <div
            v-if="iuranStatus !== 'loading'"
            class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"
          ></div>
        </button>

        <!-- Menu cards: animasi klik + delay navigasi -->
        <button
          v-for="m in menus"
          :key="m.to"
          type="button"
          class="w-full min-h-[96px] rounded-[20px] p-5 text-left relative overflow-hidden shadow-[var(--sh)] transition-transform duration-150 ease-out touch-manipulation will-change-transform"
          :class="pressedKey === m.to ? 'scale-[0.96]' : 'scale-100'"
          :style="{ background: m.cardBg }"
          @click="goTo(m.to, m.to)"
        >
          <div class="relative z-10 flex items-center gap-3">
            <div class="w-11 h-11 rounded-full grid place-items-center shrink-0 bg-white/20 text-white">
              <component :is="m.icon" :size="20" />
            </div>
            <div class="flex-1 min-w-0">
              <p
                class="m-0 text-white leading-tight"
                :class="m.headline ? 'text-[13px] font-semibold text-white/90' : 'text-[15px] font-bold'"
              >{{ m.label }}</p>
              <p
                v-if="m.headline"
                class="text-[26px] font-extrabold tracking-tight m-0 mt-1 leading-none text-white"
              >{{ m.headline }}</p>
              <p class="text-[13px] m-0 mt-1.5 flex items-center gap-1 text-white/85 leading-snug">
                <span>{{ m.desc }}</span>
                <ChevronRight :size="16" class="opacity-80 shrink-0" />
              </p>
            </div>
          </div>
          <div class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"></div>
        </button>

        <!-- Bazis: expand dropdown — tanpa animasi scale (bukan navigasi) -->
        <div
          class="rounded-[20px] overflow-hidden shadow-[var(--sh)]"
          style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #34D399, #059669 55%, #047857)"
        >
          <button
            type="button"
            class="w-full min-h-[96px] p-5 text-left relative overflow-hidden"
            :aria-expanded="bazisOpen"
            @click="bazisOpen = !bazisOpen"
          >
            <div class="relative z-10 flex items-center gap-3">
              <div class="w-11 h-11 rounded-full grid place-items-center shrink-0 bg-white/20 text-white">
                <HandCoins :size="20" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="m-0 text-[15px] font-bold text-white leading-tight">Bazis</p>
                <p class="text-[13px] m-0 mt-1.5 flex items-center gap-1 text-white/85 leading-snug">
                  <span>{{ bazisLabelBulan }} · total per RT</span>
                  <ChevronDown
                    :size="16"
                    class="opacity-80 shrink-0 transition-transform duration-200"
                    :class="bazisOpen ? 'rotate-180' : ''"
                  />
                </p>
              </div>
            </div>
            <div class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"></div>
          </button>

          <div
            v-if="bazisOpen"
            class="px-4 pb-4 relative z-10"
          >
            <div class="rounded-[16px] bg-white/15 backdrop-blur-sm border border-white/20 overflow-hidden">
              <p class="text-[12px] font-bold text-white/90 m-0 px-3.5 pt-3 pb-1.5">
                {{ bazisLabelBulan }} (data contoh)
              </p>
              <div
                v-for="(row, idx) in bazisDummy"
                :key="row.rt"
                class="flex items-center justify-between gap-3 px-3.5 py-2.5"
                :class="idx < bazisDummy.length - 1 ? 'border-b border-white/15' : ''"
              >
                <span class="text-[14px] font-semibold text-white">{{ row.rt }}</span>
                <span class="text-[14px] font-bold text-white tabular-nums">{{ rp(row.total) }}</span>
              </div>
              <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 border-t border-white/25 bg-white/10">
                <span class="text-[13px] font-bold text-white">Total</span>
                <span class="text-[14px] font-extrabold text-white tabular-nums">{{ rp(bazisTotal) }}</span>
              </div>
            </div>
          </div>
        </div>
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
  Landmark, Handshake, Megaphone, ChevronRight, ChevronDown, Wallet,
  ShieldCheck, Images, Users, HeartHandshake, BookOpen, HandCoins
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
const bazisOpen = ref(false)
const router = useRouter()
const pressedKey = ref('')
let navLock = false

/** Tahan scale sebentar biar animasi klik terasa sebelum pindah halaman */
async function goTo(to, key) {
  if (navLock) return
  navLock = true
  pressedKey.value = key
  await new Promise((r) => setTimeout(r, 170))
  try {
    await router.push(to)
  } finally {
    pressedKey.value = ''
    navLock = false
  }
}

// Dummy Bazis Oktober — RT 001–008
const bazisDummy = [
  { rt: 'RT 001', total: 1250000 },
  { rt: 'RT 002', total: 980000 },
  { rt: 'RT 003', total: 1100000 },
  { rt: 'RT 004', total: 750000 },
  { rt: 'RT 005', total: 1320000 },
  { rt: 'RT 006', total: 890000 },
  { rt: 'RT 007', total: 1040000 },
  { rt: 'RT 008', total: 960000 },
]
const bazisTotal = computed(() => bazisDummy.reduce((s, r) => s + r.total, 0))
const bazisLabelBulan = computed(() => {
  const d = new Date()
  return 'Bazis ' + d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

const iuranStatus = ref('loading')
const iuranTitle = ref('Iuran yang belum di bayar')
const iuranNominal = ref('…')
const iuranHint = ref('Ketuk untuk melihat rincian')

const styleTagihan = {
  background:
    'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #F59E0B, #D97706 55%, #B45309)',
}
const styleLunas = {
  background:
    'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)',
}
const styleLebih = {
  background:
    'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #3B82F6, #2563EB 55%, #1D4ED8)',
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
    label: 'Jadwal Ronda',
    headline: rondaHeadline.value,
    desc: rondaDesc.value,
    icon: ShieldCheck,
    cardBg: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #60A5FA, #3B82F6 55%, #2563EB)',
  },
  {
    to: '/keuangan',
    label: 'Jumlah kas ' + (namaRt.value || 'RT'),
    headline: kasHeadline.value,
    desc: 'Ketuk untuk riwayat kas',
    icon: Wallet,
    cardBg: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #C084FC, #A855F7 55%, #7C3AED)',
  },
  {
    to: '/galeri',
    label: 'Galeri RT',
    desc: 'Foto kegiatan warga',
    icon: Images,
    cardBg: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #F472B6, #EC4899 55%, #DB2777)',
  },
  {
    to: '/warga',
    label: 'Warga',
    desc: 'Daftar warga RT 002',
    icon: Users,
    cardBg: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #F87171, #EF4444 55%, #DC2626)',
  },
  {
    to: '/posyandu',
    label: 'Kader RT 002',
    desc: 'Data & informasi kader RT 002',
    icon: HeartHandshake,
    cardBg: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #FB923C, #F97316 55%, #EA580C)',
  },
  {
    to: '/talim',
    label: 'DKM',
    desc: 'Kegiatan & informasi DKM',
    icon: BookOpen,
    cardBg: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #38BDF8, #0EA5E9 55%, #0284C7)',
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
    rondaDesc.value = formatHariPendek(rn.data.tanggal) + ' · Ketuk untuk detail'
  } else {
    rondaHeadline.value = 'Belum ada jadwal'
    rondaDesc.value = 'Ketuk untuk melihat jadwal ronda'
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
