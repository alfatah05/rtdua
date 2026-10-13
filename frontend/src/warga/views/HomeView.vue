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
      <div class="rounded-[20px] h-[160px] bg-[var(--card2)] mb-3" />
      <div class="rounded-[20px] h-[110px] bg-[var(--card2)] mb-4" />
      <div class="grid grid-cols-2 gap-2">
        <div v-for="n in 6" :key="n" class="rounded-[20px] h-[120px] bg-[var(--card2)]" />
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

      <div v-if="pengumuman.length" class="space-y-3 mb-5">
        <div
          v-for="p in pengumuman"
          :key="p.id"
          class="rounded-[20px] overflow-hidden relative text-white"
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
              class="inline-flex items-center gap-1 mt-3.5 min-h-[44px] px-4 rounded-full font-bold text-[15px] bg-white text-[#0B6B34]"
              @click="$router.push('/pengumuman/' + p.id)"
            >
              Lihat detail <ChevronRight :size="18" />
            </button>
          </div>
          <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
        </div>
      </div>

      <button
        type="button"
        class="w-full rounded-[20px] p-5 text-left active:scale-[0.98] transition mb-4 relative overflow-hidden shadow-[var(--sh)]"
        :class="iuranStatus === 'loading' ? 'border border-[var(--line)]' : ''"
        :style="iuranCardStyle"
        @click="$router.push('/rincian-iuran')"
      >
        <div class="relative z-10 flex items-start gap-3">
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
              class="text-[28px] font-extrabold tracking-tight m-0 mt-1 leading-none"
              :class="iuranStatus === 'loading' ? 'text-[var(--text)]' : 'text-white'"
            >
              {{ iuranNominal }}
            </p>
            <p
              class="text-[13px] m-0 mt-2 flex items-center gap-1"
              :class="iuranStatus === 'loading' ? 'text-[var(--mut)]' : 'text-white/85'"
            >
              <span>{{ iuranHint }}</span>
              <ChevronRight :size="16" class="opacity-80" />
            </p>
          </div>
        </div>
        <div
          v-if="iuranStatus !== 'loading'"
          class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"
        ></div>
      </button>

      <div class="grid grid-cols-2 gap-2">
        <button
          v-for="m in menus"
          :key="m.to"
          type="button"
          class="relative flex flex-col items-start gap-1 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-[18px] shadow-[var(--sh)] text-left active:scale-[0.98] transition"
          @click="$router.push(m.to)"
        >
          <span class="w-11 h-11 rounded-full grid place-items-center mb-2" :style="{ background: m.bg, color: m.color }">
            <component :is="m.icon" :size="20" />
          </span>
          <b class="text-[15px] font-bold leading-tight">{{ m.label }}</b>
          <span class="text-[13px] text-[var(--mut)] leading-snug">{{ m.desc }}</span>
          <span class="absolute top-[26px] right-4 w-7 h-7 rounded-full grid place-items-center" :style="{ background: m.bg, color: m.color }">
            <ChevronRight :size="16" />
          </span>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getPengaturan } from '@shared/services/pengaturan.js'
import { listPengumuman } from '@shared/services/konten.js'
import { portalRingkasan } from '@shared/services/keuangan.js'
import { mediaUrl } from '@shared/services/upload.js'
import { plainFromMarkdown } from '@shared/utils/markdown.js'
import { waitImages } from '@shared/utils/waitImages.js'
import {
  Landmark, Handshake, Megaphone, ChevronRight, Wallet,
  ShieldCheck, ClipboardList, Images, Network, MessageCircle
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

const iuranStatus = ref('loading')
const iuranTitle = ref('Jumlah tagihan anda')
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

const menus = [
  { to: '/pengumuman', label: 'Pengumuman', desc: 'Riwayat kabar pengurus', icon: Megaphone, bg: 'rgba(245,158,11,.18)', color: '#D97706' },
  { to: '/ronda', label: 'Jadwal Ronda', desc: 'Giliran jaga malam', icon: ShieldCheck, bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { to: '/program', label: 'Program RT', desc: 'Kegiatan dan rencana RT', icon: ClipboardList, bg: 'rgba(168,85,247,.18)', color: '#7C3AED' },
  { to: '/galeri', label: 'Galeri RT', desc: 'Foto kegiatan warga', icon: Images, bg: 'rgba(236,72,153,.18)', color: '#DB2777' },
  { to: '/struktur', label: 'Struktur Pengurus', desc: 'Ketua dan pengurus', icon: Network, bg: 'rgba(99,102,241,.18)', color: '#4F46E5' },
  { to: '/bantuan', label: 'Bantuan', desc: 'Hubungi via WhatsApp', icon: MessageCircle, bg: 'rgba(34,197,94,.18)', color: '#16A34A' },
]

onMounted(async () => {
  loading.value = true
  const [pg, pn, iu] = await Promise.all([
    getPengaturan(),
    listPengumuman({ beranda: true }),
    portalRingkasan(),
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
      iuranTitle.value = 'Jumlah tagihan anda'
      iuranNominal.value = rp(total)
      iuranHint.value = 'Belum lunas · Ketuk untuk rincian'
    } else if (total < 0) {
      iuranStatus.value = 'lebih'
      iuranTitle.value = 'Kelebihan bayar'
      iuranNominal.value = rp(-total)
      iuranHint.value = 'Saldo menguntungkan · Ketuk untuk rincian'
    } else {
      iuranStatus.value = 'lunas'
      iuranTitle.value = 'Jumlah tagihan anda'
      iuranNominal.value = 'Lunas'
      iuranHint.value = 'Semua tagihan sudah dibayar'
    }
  } else {
    iuranStatus.value = 'loading'
    iuranTitle.value = 'Jumlah tagihan anda'
    iuranNominal.value = '—'
    iuranHint.value = 'Ketuk untuk melihat rincian'
  }

  await waitImages(imgs)
  loading.value = false
})
</script>
