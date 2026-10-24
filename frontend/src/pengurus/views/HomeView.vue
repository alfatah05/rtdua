<template>
  <div>
    <AppMainHeader show-profil />

    <div class="mb-4">
      <button
        type="button"
        class="w-full flex items-center gap-2.5 bg-[var(--search)] rounded-full px-[18px] h-12 text-[var(--mut)] text-left"
        @click="$router.push('/warga')"
      >
        <Search :size="18" />
        <span class="text-[15px]">Cari nama atau blok/nomor rumah</span>
      </button>
    </div>

    <!-- Card total kas — pola sama KeuanganView warga -->
    <button
      type="button"
      class="w-full text-left rounded-[20px] p-5 text-white mb-4 relative overflow-hidden active:scale-[0.99] transition-transform"
      style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #C084FC, #A855F7 55%, #7C3AED)"
      @click="$router.push('/keuangan')"
    >
      <div class="relative z-10">
        <div class="flex items-center justify-between gap-2 mb-1.5">
          <p class="text-[13px] font-semibold text-white/90 m-0">Total saldo kas</p>
          <span class="text-[12px] font-semibold px-3 py-1 rounded-full bg-white/20 shrink-0">
            {{ labelBulan }}
          </span>
        </div>
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-11 h-11 rounded-full bg-white/20 text-white grid place-items-center shrink-0">
            <Wallet :size="20" />
          </div>
          <p class="text-[26px] font-extrabold m-0 tracking-tight leading-none whitespace-nowrap tabular-nums">
            {{ loadingKas ? '…' : formatRp(saldo) }}
          </p>
        </div>

        <div class="grid grid-cols-2 gap-2 mt-4">
          <div class="flex items-center gap-2 min-h-[40px] px-3 rounded-full bg-white">
            <ArrowDownLeft :size="16" class="shrink-0" style="color: #16A34A" />
            <span class="text-[13px] font-bold truncate" style="color: #16A34A">
              {{ loadingKas ? '…' : formatRp(totalMasuk) }}
            </span>
          </div>
          <div class="flex items-center gap-2 min-h-[40px] px-3 rounded-full bg-white">
            <ArrowUpRight :size="16" class="shrink-0" style="color: #F87171" />
            <span class="text-[13px] font-bold truncate" style="color: #F87171">
              {{ loadingKas ? '…' : formatRp(totalKeluar) }}
            </span>
          </div>
        </div>

        <div v-if="daftarPos.length" class="mt-4 pt-3 border-t border-white/20 space-y-0">
          <div
            v-for="k in daftarPos"
            :key="k.id"
            class="flex items-center gap-3 py-1.5"
          >
            <div class="w-8 h-8 rounded-full grid place-items-center shrink-0 bg-white/15 text-white">
              <Landmark :size="15" />
            </div>
            <p class="flex-1 min-w-0 text-[13px] font-medium m-0 truncate text-white/90">
              {{ k.nama }}
            </p>
            <span class="text-[13px] font-bold tabular-nums shrink-0 text-white whitespace-nowrap">
              {{ formatRp(k.saldo) }}
            </span>
          </div>
        </div>
        <p v-if="loadErr" class="text-[12px] text-white/80 m-0 mt-2">{{ loadErr }}</p>
      </div>
      <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
    </button>

    <button
      v-if="jumlahPermintaan > 0"
      type="button"
      class="w-full flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 shadow-[var(--sh)] text-left mb-4 active:scale-[0.98] transition"
      @click="$router.push('/keuangan/iuran')"
    >
      <div class="w-10 h-10 rounded-full bg-amber-500/15 text-amber-600 grid place-items-center flex-none">
        <Banknote :size="18" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="font-bold text-[15px] m-0">Permintaan konfirmasi</p>
        <p class="text-[13px] text-[var(--mut)] m-0">{{ jumlahPermintaan }} menunggu diperiksa</p>
      </div>
      <ChevronRight :size="18" class="text-[var(--mut)]" />
    </button>

    <div class="grid grid-cols-5 gap-2 text-center mb-5">
      <button
        v-for="a in aksi"
        :key="a.label"
        type="button"
        class="flex flex-col items-center gap-1.5 active:scale-95 transition-transform"
        @click="$router.push(a.to)"
      >
        <span class="w-12 h-12 rounded-full grid place-items-center relative" :style="{ background: a.bg, color: a.color }">
          <component :is="a.icon" :size="20" />
        </span>
        <span class="text-[11px] font-semibold leading-tight text-center">{{ a.label }}</span>
      </button>
    </div>

    <div class="grid grid-cols-2 gap-2 mb-5">
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4">
        <div class="flex items-center gap-2"><House :size="20" /><b class="text-[22px] font-extrabold">{{ jumlahKk }}</b></div>
        <span class="block text-[12px] font-semibold text-[var(--mut)] mt-1">Keluarga (KK)</span>
      </div>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4">
        <div class="flex items-center gap-2"><Users :size="20" /><b class="text-[22px] font-extrabold">{{ jumlahWarga }}</b></div>
        <span class="block text-[12px] font-semibold text-[var(--mut)] mt-1">Warga</span>
      </div>
    </div>

    <div
      class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-[18px] mb-5 active:scale-[0.98] transition cursor-pointer"
      @click="$router.push('/keuangan/iuran')"
    >
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-[17px] font-bold m-0">Iuran {{ labelBulan }}</h2>
          <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5">{{ jumlahLunas }} dari {{ jumlahKk }} keluarga sudah lunas</p>
        </div>
        <div class="flex items-center gap-1">
          <span class="text-2xl font-extrabold">{{ pctLunas }}%</span>
          <ChevronRight :size="20" class="text-[var(--mut)]" />
        </div>
      </div>
      <div class="h-2.5 rounded-full bg-[var(--card2)] mt-3.5 overflow-hidden">
        <div class="h-full rounded-full bg-[var(--g)]" :style="{ width: pctLunas + '%' }"></div>
      </div>
      <p class="text-[13px] text-[var(--mut)] mt-2.5 mb-0">{{ Math.max(0, jumlahKk - jumlahLunas) }} keluarga belum lunas</p>
    </div>

    <div class="flex items-center justify-between mb-3">
      <h2 class="text-[17px] font-bold m-0">Aktivitas terakhir</h2>
      <router-link to="/aktivitas" class="text-[13px] font-semibold text-[var(--g)] no-underline">Lihat semua</router-link>
    </div>
    <AppPageSpinner v-if="loadingAct" class="!py-6" />
    <EmptyState v-else-if="!aktivitas.length" title="Belum ada aktivitas" />
    <div v-else class="px-1 space-y-0.5">
      <div v-for="act in aktivitas" :key="act.id" class="w-full flex flex-row items-center gap-3 px-2 py-3">
        <div class="w-10 h-10 rounded-full grid place-items-center shrink-0 bg-[var(--search)]">
          <Check :size="18" class="text-[var(--g)]" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ act.pelaku }} · {{ act.aksi }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ act.waktu }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'

import { ref, computed, onMounted } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import {
  Search, Banknote, ArrowDownLeft, ArrowUpRight,
  FileText, LayoutGrid, House, Users, ChevronRight, Check,
  Wallet, Landmark,
} from 'lucide-vue-next'
import { formatRp } from '@shared/utils/format.js'
import { listKas, listPermintaan, daftarIuran } from '@shared/services/keuangan.js'
import { listKeluarga } from '@shared/services/warga.js'
import { listAktivitas } from '@shared/services/aktivitas.js'

const saldo = ref(0)
const daftarPos = ref([])
const totalMasuk = ref(0)
const totalKeluar = ref(0)
const loadingKas = ref(true)
const loadErr = ref('')
const jumlahPermintaan = ref(0)
const jumlahKk = ref(0)
const jumlahWarga = ref(0)
const jumlahLunas = ref(0)
const aktivitas = ref([])
const loadingAct = ref(true)

const labelBulan = computed(() => {
  const d = new Date()
  return d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

const pctLunas = computed(() => {
  if (!jumlahKk.value) return 0
  return Math.round((jumlahLunas.value / jumlahKk.value) * 100)
})

const aksi = [
  { label: 'Catat iuran', icon: Banknote, to: '/keuangan/catat', bg: 'rgba(16,185,129,.18)', color: '#059669' },
  { label: 'Kas masuk', icon: ArrowDownLeft, to: '/keuangan/kas-masuk', bg: 'rgba(16,185,129,.18)', color: '#059669' },
  { label: 'Kas keluar', icon: ArrowUpRight, to: '/keuangan/kas-keluar', bg: 'rgba(239,68,68,.15)', color: '#DC2626' },
  { label: 'Laporan', icon: FileText, to: '/keuangan/laporan', bg: 'rgba(59,130,246,.18)', color: '#2563EB' },
  { label: 'Menu', icon: LayoutGrid, to: '/lainnya', bg: 'rgba(107,114,128,.18)', color: '#4B5563' },
]

async function load() {
  loadingKas.value = true
  loadingAct.value = true
  loadErr.value = ''

  const [kas, perm, kel, iuran, act] = await Promise.all([
    listKas({ bulan: new Date().toISOString().slice(0, 7) }),
    listPermintaan('menunggu'),
    listKeluarga({ status: 'aktif' }),
    daftarIuran({}),
    listAktivitas({ limit: 5 }),
  ])

  loadingKas.value = false
  loadingAct.value = false

  if (kas.ok) {
    saldo.value = kas.data?.saldo ?? 0
    daftarPos.value = kas.data?.pos || []
    const items = kas.data?.items || []
    const now = new Date()
    const ym = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
    let masuk = 0
    let keluar = 0
    for (const it of items) {
      const tgl = String(it.tanggal || it.created_at || '')
      if (!tgl.startsWith(ym)) continue
      const n = Number(it.nominal) || 0
      if (it.tipe === 'masuk') masuk += n
      else if (it.tipe === 'keluar') keluar += n
    }
    totalMasuk.value = masuk
    totalKeluar.value = keluar
  } else {
    loadErr.value = kas.error || 'Gagal memuat saldo'
  }
  if (perm.ok) jumlahPermintaan.value = (perm.data || []).length
  if (kel.ok) {
    const rows = kel.data || []
    jumlahKk.value = rows.length
    jumlahWarga.value = rows.reduce((s, k) => s + (Number(k.jumlah_anggota) || 0), 0)
  }
  if (iuran.ok) {
    const rows = iuran.data || []
    jumlahLunas.value = rows.filter((r) => (r.total ?? 0) <= 0 || r.status === 'lunas').length
    if (!jumlahKk.value) jumlahKk.value = rows.length
  }
  if (act.ok) {
    aktivitas.value = (act.data || []).slice(0, 5)
  }
}

onMounted(load)
</script>
