<template>
  <div>
    <AppBackHeader title="Bayar tagihan" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="sedangMenunggu">
      <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] p-5 shadow-[var(--sh)] mb-4 text-center">
        <div class="w-12 h-12 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 grid place-items-center mx-auto mb-3">
          <Clock :size="22" />
        </div>
        <p class="text-[16px] font-extrabold m-0">Menunggu konfirmasi pengurus</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-2 leading-relaxed">
          Masih ada permintaan pembayaran yang menunggu konfirmasi.
          Selesaikan dulu sebelum mengajukan pembayaran baru.
        </p>
        <button
          type="button"
          class="mt-5 w-full min-h-[48px] rounded-full bg-[var(--card2)] font-bold active:scale-[0.98] transition"
          @click="$router.replace('/rincian-iuran')"
        >
          Kembali ke rincian iuran
        </button>
      </div>
    </template>
    <template v-else>
      <h2 class="text-[15px] font-bold m-0 mb-2">Pilih tagihan</h2>
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
          <label
            v-for="t in tagihanTerbuka"
            :key="t.id"
            class="flex items-center gap-3 py-2.5 cursor-pointer"
            style="padding-left: 10px; padding-right: 10px"
          >
            <input
              v-model="checked"
              type="checkbox"
              class="w-5 h-5 rounded accent-[var(--g)] shrink-0"
              :value="t.id"
            />
            <div
              class="w-9 h-9 rounded-full grid place-items-center shrink-0"
              :style="{ background: iconMeta(t.jenis).bg, color: iconMeta(t.jenis).color }"
            >
              <component :is="iconMeta(t.jenis).icon" :size="16" />
            </div>
            <p class="flex-1 min-w-0 text-[14px] font-semibold m-0 truncate">{{ labelTagihan(t) }}</p>
            <span class="text-[14px] font-bold tabular-nums shrink-0">{{ rp(t.sisa ?? t.nominal) }}</span>
          </label>

          <div class="flex justify-end" style="padding: 2px 0 4px">
            <div
              class="inline-flex items-center justify-between gap-4 px-3 min-w-[48%]"
              style="height: 30px; border-radius: 15px; background: rgba(245, 158, 11, 0.16)"
            >
              <span class="text-[12px] font-bold" style="color: #B45309">Total</span>
              <span class="text-[13px] font-extrabold tabular-nums" style="color: #D97706">{{ rp(totalChecked) }}</span>
            </div>
          </div>
        </template>
        <p v-else class="text-[13px] text-[var(--mut)] text-center py-5 m-0">Tidak ada tagihan terbuka</p>
      </div>

      <h2 class="text-[15px] font-bold m-0 mb-2">Metode pembayaran</h2>

      <div v-if="grupQris.length" class="mb-2 space-y-2">
        <label
          v-for="r in grupQris"
          :key="r.id"
          class="flex items-center gap-3 p-4 rounded-[16px] border bg-[var(--card)] cursor-pointer"
          :class="selectedId === r.id ? 'border-[var(--g)] shadow-[var(--sh)]' : 'border-[var(--line)]'"
        >
          <input v-model="selectedId" type="radio" class="accent-[var(--g)]" :value="r.id" />
          <div class="w-10 h-10 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center shrink-0">
            <QrCode :size="18" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-bold m-0 text-[14px]">{{ r.nama }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0">Scan QRIS merchant RT</p>
          </div>
        </label>
      </div>

      <div v-if="grupEwallet.length" class="mb-2 rounded-[16px] border border-[var(--line)] bg-[var(--card)] overflow-hidden">
        <button type="button" class="w-full flex items-center gap-3 p-4 text-left" @click="openEw = !openEw">
          <div class="w-10 h-10 rounded-full grid place-items-center shrink-0" style="background: rgba(236,72,153,.16); color: #DB2777">
            <Wallet :size="18" />
          </div>
          <span class="flex-1 font-bold text-[14px]">E-wallet</span>
          <ChevronDown :size="18" class="text-[var(--mut)] transition" :class="openEw ? 'rotate-180' : ''" />
        </button>
        <div v-show="openEw" class="px-3 pb-3 space-y-2">
          <label
            v-for="r in grupEwallet"
            :key="r.id"
            class="flex items-center gap-3 p-3 rounded-[12px] border cursor-pointer"
            :class="selectedId === r.id ? 'border-[var(--g)] bg-[var(--gd)]' : 'border-[var(--line)]'"
          >
            <input v-model="selectedId" type="radio" class="accent-[var(--g)]" :value="r.id" />
            <div class="min-w-0 flex-1">
              <p class="font-semibold m-0 text-[14px]">{{ r.nama }}</p>
              <p class="text-[12px] text-[var(--mut)] m-0">{{ r.nomor || '—' }}</p>
            </div>
          </label>
        </div>
      </div>

      <div v-if="grupBank.length" class="mb-4 rounded-[16px] border border-[var(--line)] bg-[var(--card)] overflow-hidden">
        <button type="button" class="w-full flex items-center gap-3 p-4 text-left" @click="openBank = !openBank">
          <div class="w-10 h-10 rounded-full grid place-items-center shrink-0" style="background: rgba(59,130,246,.16); color: #2563EB">
            <Building2 :size="18" />
          </div>
          <span class="flex-1 font-bold text-[14px]">Transfer bank</span>
          <ChevronDown :size="18" class="text-[var(--mut)] transition" :class="openBank ? 'rotate-180' : ''" />
        </button>
        <div v-show="openBank" class="px-3 pb-3 space-y-2">
          <label
            v-for="r in grupBank"
            :key="r.id"
            class="flex items-center gap-3 p-3 rounded-[12px] border cursor-pointer"
            :class="selectedId === r.id ? 'border-[var(--g)] bg-[var(--gd)]' : 'border-[var(--line)]'"
          >
            <input v-model="selectedId" type="radio" class="accent-[var(--g)]" :value="r.id" />
            <div class="min-w-0 flex-1">
              <p class="font-semibold m-0 text-[14px]">{{ r.nama }}</p>
              <p class="text-[12px] text-[var(--mut)] m-0">{{ r.nomor || '—' }} · {{ r.atas_nama || '' }}</p>
            </div>
          </label>
        </div>
      </div>

      <p v-if="!rekening.length" class="text-[13px] text-[var(--mut)] text-center mb-4">Belum ada metode pembayaran. Hubungi pengurus.</p>

      <button
        type="button"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold active:scale-[0.98] transition disabled:opacity-50"
        :disabled="!canBayar"
        @click="lanjut"
      >
        Bayar {{ totalChecked > 0 ? rp(totalChecked) : '' }}
      </button>
    </template>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Wallet, Home, ShieldAlert, Sparkles, QrCode, Building2, ChevronDown, Clock } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalRingkasan, portalListRekening, portalStatusPermintaan } from '@shared/services/keuangan.js'

const DRAFT_KEY = 'rtdua_bayar_draft'
const router = useRouter()
const loading = ref(true)
const err = ref('')
const tagihan = ref([])
const rekening = ref([])
const checked = ref([])
const selectedId = ref(null)
const openEw = ref(false)
const openBank = ref(false)
const sedangMenunggu = ref(false)

const BULAN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}
function bulanDariPeriode(periode) {
  if (!periode || String(periode).length < 7) return ''
  const m = Number(String(periode).slice(5, 7))
  return BULAN[m] || String(periode)
}
function labelJenisDasar(jenis) {
  if (jenis === 'kas') return 'Iuran'
  if (jenis === 'denda_ronda') return 'Denda ronda'
  if (jenis === 'khusus') return 'Iuran khusus'
  return jenis || 'Tagihan'
}
function labelTagihan(t) {
  // Tagihan khusus (termasuk per keluarga): judul saja, tanpa suffix bulan
  if (t.jenis === 'khusus' && t.nama) {
    return t.nama
  }
  const jenis = labelJenisDasar(t.jenis)
  const bln = bulanDariPeriode(t.periode)
  return bln ? `${jenis} ${bln}` : jenis
}
function iconMeta(jenis) {
  if (jenis === 'kas') return { icon: Home, bg: 'rgba(59,130,246,.16)', color: '#2563EB' }
  if (jenis === 'denda_ronda') return { icon: ShieldAlert, bg: 'rgba(168,85,247,.16)', color: '#7C3AED' }
  if (jenis === 'khusus') return { icon: Sparkles, bg: 'rgba(236,72,153,.16)', color: '#DB2777' }
  return { icon: Wallet, bg: 'rgba(99,102,241,.14)', color: '#4F46E5' }
}

const tagihanTerbuka = computed(() =>
  (tagihan.value || []).filter((t) => Number(t.sisa ?? t.nominal ?? 0) > 0)
)
const totalChecked = computed(() =>
  tagihanTerbuka.value
    .filter((t) => checked.value.includes(t.id))
    .reduce((s, t) => s + Number(t.sisa ?? t.nominal ?? 0), 0)
)
const grupQris = computed(() => rekening.value.filter((r) => r.jenis === 'qris'))
const grupEwallet = computed(() => rekening.value.filter((r) => r.jenis === 'ewallet'))
const grupBank = computed(() => rekening.value.filter((r) => r.jenis === 'bank'))
const canBayar = computed(() => checked.value.length > 0 && selectedId.value && totalChecked.value > 0)

function lanjut() {
  if (sedangMenunggu.value) return
  const metode = rekening.value.find((r) => r.id === selectedId.value)
  if (!metode) return
  const items = tagihanTerbuka.value.filter((t) => checked.value.includes(t.id))
  const draft = {
    tagihanIds: items.map((t) => t.id),
    items: items.map((t) => ({ id: t.id, label: labelTagihan(t), sisa: Number(t.sisa ?? t.nominal ?? 0) })),
    total: totalChecked.value,
    rekening: metode,
  }
  sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft))
  router.replace('/bayar/detail')
}

onMounted(async () => {
  const [res, rek, pm] = await Promise.all([
    portalRingkasan(),
    portalListRekening(),
    portalStatusPermintaan(),
  ])
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  tagihan.value = res.data?.tagihan || []
  if (rek.ok) {
    rekening.value = Array.isArray(rek.data) ? rek.data : []
    const q = rekening.value.find((r) => r.jenis === 'qris')
    if (q) selectedId.value = q.id
    else if (rekening.value[0]) selectedId.value = rekening.value[0].id
  }
  if (pm.ok) {
    const list = Array.isArray(pm.data) ? pm.data : (pm.data?.items || [])
    sedangMenunggu.value = list.some((p) => p.status === 'menunggu')
  }
})
</script>
