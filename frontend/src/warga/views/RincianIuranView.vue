<template>
  <div>
    <AppBackHeader title="Rincian iuran" />

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div
        class="w-full rounded-[20px] p-5 mb-4 relative overflow-hidden shadow-[var(--sh)]"
        :class="heroStatus === 'loading' ? 'border border-[var(--line)]' : ''"
        :style="heroStyle"
      >
        <div class="relative z-10 flex items-start gap-3">
          <div
            class="w-11 h-11 rounded-full grid place-items-center shrink-0"
            :class="heroStatus === 'loading' ? 'bg-[var(--card)] text-[var(--gm)]' : 'bg-white/20 text-white'"
          >
            <Wallet :size="20" />
          </div>
          <div class="flex-1 min-w-0">
            <p
              class="text-[13px] font-semibold m-0"
              :class="heroStatus === 'loading' ? 'text-[var(--mut)]' : 'text-white/90'"
            >
              {{ heroTitle }}
            </p>
            <p
              class="text-[28px] font-extrabold tracking-tight m-0 mt-1 leading-none"
              :class="heroStatus === 'loading' ? 'text-[var(--text)]' : 'text-white'"
            >
              {{ heroNominal }}
            </p>
            <p
              class="text-[13px] m-0 mt-2"
              :class="heroStatus === 'loading' ? 'text-[var(--mut)]' : 'text-white/85'"
            >
              {{ heroHint }}
            </p>
          </div>
        </div>
        <div
          v-if="heroStatus !== 'loading'"
          class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"
        ></div>
      </div>

      <div v-if="permintaanMenunggu.length" class="mb-5 space-y-2.5">
        <h2 class="text-[15px] font-bold m-0 mb-1">Status permintaan</h2>
        <div
          v-for="p in permintaanMenunggu"
          :key="'pm-' + p.id"
          class="w-full rounded-[20px] p-5 relative overflow-hidden shadow-[var(--sh)]"
          :style="stylePermintaan"
        >
          <div class="relative z-10 flex items-start gap-3">
            <div class="w-11 h-11 rounded-full grid place-items-center shrink-0 bg-white/20 text-white">
              <Clock :size="20" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[13px] font-semibold m-0 text-white/90">Status permintaan</p>
              <p class="text-[17px] font-extrabold m-0 mt-1 leading-snug text-white">
                Menunggu konfirmasi pengurus
              </p>
              <p class="text-[13px] m-0 mt-1.5 text-white/85">
                {{ rp(p.nominal_diajukan ?? p.nominal) }} · {{ formatTgl(p.diajukan_pada) }}
              </p>
            </div>
          </div>
          <div class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none"></div>
        </div>
      </div>

      <h2 class="text-[15px] font-bold m-0 mb-2">Rincian tagihan</h2>
      <div
        class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] shadow-[var(--sh)] mb-4"
        style="padding: 5px"
      >
        <div
          class="flex items-center justify-between px-3"
          style="height: 30px; border-radius: 15px; background: rgba(34, 184, 99, 0.14)"
        >
          <span class="text-[12px] font-semibold text-[var(--gm)]">Jenis Pembayaran</span>
          <span class="text-[12px] font-semibold text-[var(--gm)]">Biaya</span>
        </div>

        <template v-if="tagihanTerbuka.length">
          <div v-for="t in tagihanTerbuka" :key="t.id">
            <button
              type="button"
              class="w-full flex items-center gap-3 text-left"
              :class="[
                bisaExpandKas(t) && isKasOpen(t.id) ? 'pt-2.5 pb-0.5' : 'py-2.5',
                bisaExpandKas(t) ? 'active:opacity-80' : 'cursor-default',
              ]"
              style="padding-left: 10px; padding-right: 10px"
              @click="bisaExpandKas(t) && toggleKas(t.id)"
            >
              <div
                class="w-9 h-9 rounded-full grid place-items-center shrink-0"
                :style="{ background: iconMeta(t.jenis).bg, color: iconMeta(t.jenis).color }"
              >
                <component :is="iconMeta(t.jenis).icon" :size="16" />
              </div>
              <div class="flex-1 min-w-0 flex items-center gap-1">
                <p class="text-[14px] font-semibold m-0 truncate">
                  {{ labelTagihan(t) }}
                </p>
                <ChevronDown
                  v-if="bisaExpandKas(t)"
                  :size="16"
                  class="shrink-0 text-[var(--text)] transition-transform duration-200"
                  :class="isKasOpen(t.id) ? 'rotate-180' : ''"
                />
              </div>
              <span class="text-[14px] font-bold tabular-nums shrink-0 whitespace-nowrap">
                {{ rp(t.sisa ?? t.nominal) }}
              </span>
            </button>

            <div
              v-if="bisaExpandKas(t) && isKasOpen(t.id)"
              class="pb-1"
            >
              <div
                v-for="c in komponenKas"
                :key="'k-' + t.id + '-' + c.id"
                class="flex items-center gap-3 py-1"
                style="padding-left: 10px; padding-right: 10px"
              >
                <div
                  class="w-9 shrink-0 opacity-0 pointer-events-none"
                  aria-hidden="true"
                ></div>
                <p class="flex-1 min-w-0 text-[13px] font-medium m-0 text-[var(--text)] truncate">
                  {{ c.nama }}
                </p>
                <span class="text-[13px] font-semibold tabular-nums shrink-0 text-[var(--text)]">
                  {{ rp(c.nominal) }}
                </span>
              </div>
            </div>
          </div>
        </template>
        <p v-else class="text-[13px] text-[var(--mut)] text-center py-6 m-0">Tidak ada tagihan terbuka</p>
      </div>

      <h2 class="text-[15px] font-bold m-0 mb-2">Riwayat</h2>
      <div
        class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] shadow-[var(--sh)] mb-6"
        style="padding: 5px"
      >
        <div
          class="flex items-center justify-between px-3"
          style="height: 30px; border-radius: 15px; background: rgba(34, 184, 99, 0.14)"
        >
          <span class="text-[12px] font-semibold text-[var(--gm)]">Keterangan</span>
          <span class="text-[12px] font-semibold text-[var(--gm)]">Nominal</span>
        </div>
        <template v-if="riwayat.length">
          <div
            v-for="r in riwayat"
            :key="'rw-' + r.id"
            class="flex items-center gap-3 py-2.5"
            style="padding-left: 10px; padding-right: 10px"
          >
            <div
              class="w-9 h-9 rounded-full grid place-items-center shrink-0"
              :style="{ background: iconMeta(r.jenis).bg, color: iconMeta(r.jenis).color }"
            >
              <component :is="iconMeta(r.jenis).icon" :size="16" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[14px] font-semibold m-0 truncate">{{ labelRiwayat(r) }}</p>
              <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">{{ formatTgl(r.tanggal || r.created_at) }}</p>
            </div>
            <span class="text-[14px] font-bold tabular-nums shrink-0 whitespace-nowrap">
              {{ rp(r.nominal) }}
            </span>
          </div>
        </template>
        <p v-else class="text-[13px] text-[var(--mut)] text-center py-6 m-0">Belum ada riwayat</p>
      </div>

      <button
        v-if="tagihanTerbuka.length"
        type="button"
        class="w-full h-12 rounded-2xl bg-[var(--gm)] text-white text-[15px] font-bold active:opacity-90"
        @click="goBayar"
      >
        Bayar sekarang
      </button>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Wallet, Home, ShieldAlert, Sparkles, Clock, ChevronDown } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { portalRingkasan, portalStatusPermintaan } from '@shared/services/keuangan.js'
import { rp } from '@shared/utils/format.js'

const router = useRouter()
const loading = ref(true)
const err = ref('')
const ringkasan = ref(null)
const permintaan = ref([])
const kasOpen = ref({})

function bisaExpandKas(t) {
  return t?.jenis === 'kas' && Array.isArray(komponenKas.value) && komponenKas.value.length > 0
}

function isKasOpen(id) {
  return !!kasOpen.value[id]
}

function toggleKas(id) {
  kasOpen.value = { ...kasOpen.value, [id]: !kasOpen.value[id] }
}

function iconMeta(jenis) {
  if (jenis === 'kas') return { icon: Wallet, bg: 'rgba(34,184,99,0.14)', color: 'var(--gm)' }
  if (jenis === 'sosial') return { icon: Home, bg: 'rgba(59,130,246,0.14)', color: '#3b82f6' }
  if (jenis === 'denda') return { icon: ShieldAlert, bg: 'rgba(239,68,68,0.14)', color: '#ef4444' }
  if (jenis === 'khusus') return { icon: Sparkles, bg: 'rgba(236,72,153,0.14)', color: '#ec4899' }
  return { icon: Wallet, bg: 'rgba(34,184,99,0.14)', color: 'var(--gm)' }
}

function labelJenisDasar(jenis) {
  if (jenis === 'kas') return 'Kas'
  if (jenis === 'sosial') return 'Sosial'
  if (jenis === 'denda') return 'Denda'
  if (jenis === 'khusus') return 'Khusus'
  return jenis || 'Tagihan'
}

function bulanDariPeriode(periode) {
  if (!periode) return ''
  const s = String(periode)
  // periode bisa YYYY-MM atau YYYY-MM-DD atau label
  const m = s.match(/(\d{4})-(\d{2})/)
  if (!m) return s
  const bln = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
  ]
  const idx = parseInt(m[2], 10)
  return bln[idx] || s
}

function labelTagihan(t) {
  // Tagihan khusus: tampilkan judul saja (tanpa suffix periode)
  if (t.jenis === 'khusus' && t.nama) {
    return t.nama
  }
  const jenis = labelJenisDasar(t.jenis)
  const bln = bulanDariPeriode(t.periode)
  return bln ? `${jenis} ${bln}` : jenis
}

function labelRiwayat(r) {
  if (r.jenis === 'kelebihan') return 'Kelebihan bayar'
  if (r.jenis === 'pembayaran') return 'Pembayaran'
  if (r.nama) return r.nama
  return labelJenisDasar(r.jenis)
}

function formatTgl(v) {
  if (!v) return ''
  const d = new Date(v)
  if (Number.isNaN(d.getTime())) return String(v)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const tagihanTerbuka = computed(() => {
  const items = ringkasan.value?.tagihan || ringkasan.value?.items || []
  return (items || []).filter((t) => (t.status || '') !== 'lunas' && (t.status || '') !== 'dibatalkan')
})

const riwayat = computed(() => ringkasan.value?.riwayat || ringkasan.value?.pembayaran || [])

const komponenKas = computed(() => ringkasan.value?.komponen_kas || ringkasan.value?.komponenKas || [])

const totalTunggakan = computed(() => {
  return tagihanTerbuka.value.reduce((s, t) => s + Number(t.sisa ?? t.nominal ?? 0), 0)
})

const heroStatus = computed(() => {
  if (loading.value) return 'loading'
  if (totalTunggakan.value > 0) return 'tunggakan'
  return 'lunas'
})

const heroStyle = computed(() => {
  if (heroStatus.value === 'loading') return { background: 'var(--card)' }
  if (heroStatus.value === 'tunggakan') {
    return { background: 'linear-gradient(135deg, #ef4444 0%, #f97316 100%)' }
  }
  return { background: 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)' }
})

const heroTitle = computed(() => {
  if (heroStatus.value === 'loading') return 'Memuat...'
  if (heroStatus.value === 'tunggakan') return 'Total tunggakan'
  return 'Status iuran'
})

const heroNominal = computed(() => {
  if (heroStatus.value === 'loading') return '—'
  if (heroStatus.value === 'tunggakan') return rp(totalTunggakan.value)
  return 'Lunas'
})

const heroHint = computed(() => {
  if (heroStatus.value === 'loading') return 'Mengambil data tagihan'
  if (heroStatus.value === 'tunggakan') return `${tagihanTerbuka.value.length} tagihan belum lunas`
  return 'Tidak ada tagihan terbuka'
})

const permintaanMenunggu = computed(() =>
  (permintaan.value || []).filter((p) => p.status === 'menunggu')
)

const stylePermintaan = {
  background: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
}

function goBayar() {
  router.replace({ name: 'pre-bayar' }).catch(() => router.push('/pre-bayar'))
}

onMounted(async () => {
  loading.value = true
  err.value = ''
  try {
    const [res, pm] = await Promise.all([portalRingkasan(), portalStatusPermintaan()])
    if (res?.error) {
      err.value = res.error || res.message || 'Gagal memuat'
    } else {
      ringkasan.value = res?.data || res
    }
    permintaan.value = Array.isArray(pm.data) ? pm.data : (pm.data?.items || [])
  } catch (e) {
    err.value = e?.message || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
})
</script>
