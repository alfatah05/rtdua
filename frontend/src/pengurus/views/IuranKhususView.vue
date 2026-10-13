<template>
  <div>
    <AppBackHeader title="Iuran khusus" />

    <button
      type="button"
      class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4 active:scale-[0.98] transition"
      @click="openForm"
    >
      + Buat iuran khusus
    </button>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div v-if="!daftar.length">
        <EmptyState title="Belum ada iuran khusus" desc="Buat iuran untuk event atau kebutuhan tertentu. Tagihan otomatis masuk ke semua keluarga aktif." />
      </div>
      <div v-else class="space-y-2">
        <div
          v-for="i in daftar"
          :key="i.id"
          class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4"
          :class="i.dibatalkan ? 'opacity-60' : ''"
        >
          <div class="flex items-start gap-3">
            <div class="flex-1 min-w-0">
              <p class="font-bold text-[15px] m-0">{{ i.nama }}</p>
              <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5">
                {{ rp(i.nominal) }} · {{ labelPeriode(i.periode) }}
                <span v-if="i.jumlah_kk != null"> · {{ i.jumlah_kk }} KK</span>
              </p>
              <span
                v-if="i.dibatalkan"
                class="inline-block mt-2 text-[11px] font-bold px-2 py-0.5 rounded-full bg-[var(--card2)] text-[var(--mut)]"
              >Dibatalkan</span>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div
      v-if="showForm"
      class="fixed inset-0 z-50 bg-black/40 flex items-end justify-center"
      @click.self="showForm = false"
    >
      <div class="w-full max-w-[560px] bg-[var(--bg)] rounded-t-[24px] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
        <h2 class="text-lg font-bold mb-4 m-0">Buat iuran khusus</h2>
        <div class="space-y-3">
          <div>
            <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nama</label>
            <input
              v-model="form.nama"
              type="text"
              placeholder="Contoh: 17 Agustus, Gotong royong"
              class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
            />
          </div>
          <div>
            <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Periode (bulan tagihan)</label>
            <input
              v-model="form.periode"
              type="month"
              class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
            />
          </div>
          <div>
            <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nominal per KK</label>
            <input
              v-model="form.nominal"
              type="text"
              inputmode="numeric"
              placeholder="50000"
              class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
            />
          </div>
          <p v-if="formErr" class="text-[13px] text-red-600 text-center m-0">{{ formErr }}</p>
          <button
            type="button"
            class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50"
            :disabled="saving"
            @click="buat"
          >
            {{ saving ? 'Menyimpan…' : 'Buat & terbitkan tagihan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { listIuranKhusus, buatIuranKhusus } from '@shared/services/keuangan.js'
import { useToast } from '@shared/composables/useToast.js'

const { success, error } = useToast()
const loading = ref(true)
const err = ref('')
const showForm = ref(false)
const saving = ref(false)
const formErr = ref('')
const daftar = ref([])
const form = ref({ nama: '', nominal: '', periode: currentYm() })

const BULAN = [
  '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

function currentYm() {
  const d = new Date()
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0')
}

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}

function labelPeriode(p) {
  if (!p || String(p).length < 7) return p || '—'
  const m = Number(String(p).slice(5, 7))
  const y = String(p).slice(0, 4)
  return (BULAN[m] || p) + ' ' + y
}

function openForm() {
  formErr.value = ''
  form.value = { nama: '', nominal: '', periode: currentYm() }
  showForm.value = true
}

function asList(data) {
  if (Array.isArray(data)) return data
  if (Array.isArray(data?.items)) return data.items
  if (Array.isArray(data?.data)) return data.data
  return []
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await listIuranKhusus()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    daftar.value = []
    return
  }
  daftar.value = asList(res.data)
}

async function buat() {
  formErr.value = ''
  const nama = form.value.nama.trim()
  const periode = form.value.periode
  const nominal = Number(String(form.value.nominal).replace(/\D/g, ''))
  if (!nama) {
    formErr.value = 'Nama wajib'
    return
  }
  if (!periode || !/^\d{4}-\d{2}$/.test(periode)) {
    formErr.value = 'Periode wajib (bulan-tahun)'
    return
  }
  if (!nominal || nominal < 1) {
    formErr.value = 'Nominal wajib'
    return
  }
  saving.value = true
  const res = await buatIuranKhusus({ nama, periode, nominal })
  saving.value = false
  if (!res.ok) {
    formErr.value = res.error || 'Gagal membuat'
    error(formErr.value)
    return
  }
  const kk = res.data?.keluarga_terdampak ?? res.data?.data?.keluarga_terdampak
  showForm.value = false
  success(kk != null ? `Iuran dibuat untuk ${kk} keluarga` : 'Iuran khusus dibuat')
  await load()
}

onMounted(load)
</script>
