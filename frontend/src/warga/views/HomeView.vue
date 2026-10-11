<template>
  <div>
    <AppMainHeader />
    <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3 text-center mb-2">
      <div class="w-[60px] h-[60px] rounded-full bg-[var(--card)] border border-[var(--line)] grid place-items-center overflow-hidden">
        <img v-if="logoRtUrl" :src="logoRtUrl" alt="Logo RT" class="w-full h-full object-contain" @error="logoRtUrl = ''" />
        <Landmark v-else :size="26" class="text-[var(--mut)]" />
      </div>
      <div>
        <p class="text-[22px] font-extrabold leading-tight m-0 text-[var(--text)]">{{ namaRt || 'RT' }}</p>
        <small class="block text-[13px] font-medium text-[var(--mut)] leading-snug">{{ namaPerumahan }}</small>
      </div>
      <div class="w-[60px] h-[60px] rounded-full bg-[var(--card)] border border-[var(--line)] grid place-items-center overflow-hidden">
        <img v-if="logoDesaUrl" :src="logoDesaUrl" alt="Logo desa" class="w-full h-full object-contain" @error="logoDesaUrl = ''" />
        <Handshake v-else :size="26" class="text-[var(--mut)]" />
      </div>
    </div>
    <div class="h-px bg-[var(--line)] my-4"></div>

    <div class="space-y-3 mb-4">
      <div
        v-for="(p, i) in pengumuman"
        :key="p.id"
        class="rounded-[20px] overflow-hidden relative"
        :class="i === 0 ? 'text-white' : 'bg-[var(--card)] border border-[var(--line)] shadow-[var(--sh)]'"
        :style="i === 0 ? heroStyle : null"
      >
        <div class="p-5 relative z-10">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="flex items-center gap-2 text-[13px] font-semibold" :class="i === 0 ? 'text-white/90' : 'text-[var(--mut)]'">
              <Megaphone :size="16" /> Pengumuman
              <span v-if="p.hasLampiran" class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="i === 0 ? 'bg-white/20' : 'bg-[var(--search)] text-[var(--mut)]'">📎</span>
            </span>
            <span class="text-[12px] font-semibold px-3 py-1 rounded-full" :class="i === 0 ? 'bg-white/20' : 'bg-[var(--search)] text-[var(--mut)]'">
              {{ p.tanggal }}
            </span>
          </div>
          <h2 class="text-[17px] font-bold leading-snug mt-3 mb-1" :class="i === 0 ? 'text-white' : ''">{{ p.judul }}</h2>
          <p class="text-[13px] m-0" :class="i === 0 ? 'text-white/90' : 'text-[var(--mut)]'">{{ p.ringkas }}</p>
          <button
            type="button"
            class="inline-flex items-center gap-1 mt-3.5 min-h-[44px] px-4 rounded-full font-bold text-[15px]"
            :class="i === 0 ? 'bg-white text-[#0B6B34]' : 'bg-[var(--card2)] text-[var(--text)]'"
            @click="$router.push('/pengumuman/' + p.id)"
          >
            Lihat detail <ChevronRight :size="18" />
          </button>
        </div>
        <div v-if="i === 0" class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
      </div>
      <p v-if="!pengumuman.length && !loading" class="text-[13px] text-[var(--mut)] text-center py-2">Belum ada pengumuman</p>
    </div>

    <router-link to="/pengumuman" class="block text-center text-[13px] font-semibold text-[var(--g)] mb-5 no-underline">
      Pengumuman lain →
    </router-link>

    <button
      type="button"
      class="w-full flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-[18px] shadow-[var(--sh)] text-left active:scale-[0.98] transition mb-4"
      @click="$router.push('/rincian-iuran')"
    >
      <div class="flex-1 min-w-0">
        <span class="block text-[13px] font-semibold text-[var(--mut)]">{{ iuranLabel }}</span>
        <b class="block text-[17px] font-extrabold mt-0.5">{{ iuranNominal }}</b>
        <span class="text-[13px] text-[var(--mut)]">Ketuk untuk melihat rincian</span>
      </div>
      <ChevronRight :size="20" class="text-[var(--mut)] flex-none" />
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
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { getPengaturan } from '@shared/services/pengaturan.js'
import { listPengumuman } from '@shared/services/konten.js'
import { portalRingkasan } from '@shared/services/keuangan.js'
import { mediaUrl } from '@shared/services/upload.js'
import {
  Landmark, Handshake, Megaphone, ChevronRight,
  ShieldCheck, ClipboardList, Images, Network, MessageCircle
} from 'lucide-vue-next'

const heroStyle = {
  background: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)'
}

const namaRt = ref('')
const namaPerumahan = ref('')
const logoRtUrl = ref('')
const logoDesaUrl = ref('')
const pengumuman = ref([])
const iuranLabel = ref('Iuran')
const iuranNominal = ref('Memuat…')
const loading = ref(true)

function formatTgl(s) {
  if (!s) return ''
  const d = new Date(String(s).replace(' ', 'T'))
  return isNaN(d.getTime()) ? String(s).slice(0, 10) : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}
function ringkas(isi) {
  const t = String(isi || '').replace(/\s+/g, ' ').trim()
  return t.length > 100 ? t.slice(0, 100) + '…' : t
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
  const [pg, pn, iu] = await Promise.all([
    getPengaturan(),
    listPengumuman({ beranda: true }),
    portalRingkasan(),
  ])
  loading.value = false
  if (pg.ok && pg.data) {
    namaRt.value = pg.data.nama_rt || 'RT'
    namaPerumahan.value = pg.data.nama_perumahan || ''
    logoRtUrl.value = pg.data.logo_rt ? mediaUrl(pg.data.logo_rt) : ''
    logoDesaUrl.value = pg.data.logo_desa ? mediaUrl(pg.data.logo_desa) : ''
  }
  if (pn.ok) {
    pengumuman.value = (pn.data || []).slice(0, 3).map((p) => ({
      id: p.id,
      judul: p.judul,
      ringkas: ringkas(p.isi),
      tanggal: formatTgl(p.diterbitkan_pada),
      hasLampiran: !!p.lampiran_file,
    }))
  }
  if (iu.ok && iu.data) {
    const total = Number(iu.data.total ?? iu.data.total_tagihan ?? 0)
    if (total > 0) {
      iuranLabel.value = 'Iuran yang belum dibayar'
      iuranNominal.value = rp(total)
    } else if (total < 0) {
      iuranLabel.value = 'Kelebihan bayar'
      iuranNominal.value = rp(-total)
    } else {
      iuranLabel.value = 'Semua iuran sudah lunas'
      iuranNominal.value = 'Lunas'
    }
  } else {
    iuranNominal.value = '—'
  }
})
</script>
