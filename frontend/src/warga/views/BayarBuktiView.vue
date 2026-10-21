<template>
  <div class="pb-[calc(72px+env(safe-area-inset-bottom,0px))]">
    <AppBackHeader title="Bukti pembayaran" />

    <template v-if="!data">
      <p class="text-[13px] text-[var(--mut)] text-center py-8 m-0">Data bukti tidak ditemukan.</p>
    </template>

    <template v-else>
      <!-- Status + nominal -->
      <div class="flex flex-col items-center text-center pt-2 mb-5">
        <div
          class="w-16 h-16 rounded-full grid place-items-center mb-3"
          style="background: rgba(245, 158, 11, 0.16); color: #D97706"
        >
          <Clock :size="32" stroke-width="2" />
        </div>
        <p class="text-[17px] font-extrabold m-0 text-[var(--text)]">Menunggu konfirmasi</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-1">
          {{ formatTglJam(data.diajukan_pada) }}
        </p>
        <p class="text-[28px] font-extrabold tracking-tight m-0 mt-3 tabular-nums text-[var(--text)]">
          {{ rp(data.nominal) }}
        </p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-1.5 px-4 leading-snug">
          Pengurus akan memeriksa bukti transfer Anda.
        </p>
      </div>

      <!-- Detail seperti struk -->
      <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] shadow-[var(--sh)] overflow-hidden mb-4">
        <div
          v-for="(row, i) in rows"
          :key="row.label"
          class="flex items-start justify-between gap-4 px-4 py-3.5"
          :class="i < rows.length - 1 ? 'border-b border-[var(--line)]' : ''"
        >
          <span class="text-[13px] text-[var(--mut)] shrink-0 leading-snug">{{ row.label }}</span>
          <span class="text-[13px] font-semibold text-right text-[var(--text)] leading-snug break-words min-w-0">
            {{ row.value }}
          </span>
        </div>
      </div>

      <!-- Rincian tagihan -->
      <div v-if="data.items?.length" class="mb-2">
        <h2 class="text-[15px] font-bold m-0 mb-2">Rincian pembayaran</h2>
        <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] shadow-[var(--sh)] overflow-hidden">
          <div
            v-for="(it, i) in data.items"
            :key="it.id || i"
            class="flex items-center justify-between gap-3 px-4 py-3"
            :class="i < data.items.length - 1 ? 'border-b border-[var(--line)]' : ''"
          >
            <p class="text-[14px] font-medium m-0 min-w-0 truncate">{{ it.label }}</p>
            <span class="text-[14px] font-bold tabular-nums shrink-0">{{ rp(it.sisa) }}</span>
          </div>
          <div class="flex items-center justify-between gap-3 px-4 py-3 bg-[var(--card2)]">
            <span class="text-[13px] font-bold">Total</span>
            <span class="text-[14px] font-extrabold tabular-nums">{{ rp(data.nominal) }}</span>
          </div>
        </div>
      </div>
    </template>

    <!-- Sticky bottom: kembali -->
    <div class="fixed bottom-0 left-0 right-0 z-30 pointer-events-none">
      <div class="max-w-[560px] mx-auto px-4 pt-2 pb-[max(12px,env(safe-area-inset-bottom,0px))]">
        <button
          type="button"
          class="pointer-events-auto w-full min-h-[48px] rounded-full bg-[var(--btn)] text-[var(--btn-fg)] font-bold active:scale-[0.98] transition shadow-lg"
          @click="$router.replace('/rincian-iuran')"
        >
          Kembali ke rincian iuran
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Clock } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'

const RECEIPT_KEY = 'rtdua_bayar_bukti'
const data = ref(null)

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}

function formatTglJam(s) {
  if (!s) return '—'
  const raw = String(s).replace(' ', 'T')
  const d = new Date(raw.length === 10 ? raw + 'T00:00:00' : raw)
  if (Number.isNaN(d.getTime())) return String(s)
  return d.toLocaleString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function labelJenisRekening(jenis) {
  if (jenis === 'qris') return 'QRIS'
  if (jenis === 'ewallet') return 'E-wallet'
  if (jenis === 'bank') return 'Transfer bank'
  return jenis || '—'
}

const rows = computed(() => {
  const d = data.value
  if (!d) return []
  const r = d.rekening || {}
  const list = [
    { label: 'Status', value: 'Menunggu konfirmasi pengurus' },
    { label: 'Nama penerima', value: r.atas_nama || r.nama || '—' },
    { label: 'Rekening tujuan', value: r.nomor || (r.jenis === 'qris' ? 'QRIS' : '—') },
    { label: 'Metode', value: labelJenisRekening(r.jenis) + (r.nama ? ` · ${r.nama}` : '') },
    { label: 'Pengirim', value: d.nama_pengirim || '—' },
    { label: 'Nominal', value: rp(d.nominal) },
  ]
  if (d.id) {
    list.push({ label: 'No. referensi', value: String(d.id) })
  }
  return list
})

onMounted(() => {
  try {
    const raw = sessionStorage.getItem(RECEIPT_KEY)
    data.value = raw ? JSON.parse(raw) : null
  } catch {
    data.value = null
  }
})
</script>
