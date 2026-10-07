<template>
  <div>
    <AppBackHeader title="Iuran" />

    <div class="grid grid-cols-3 gap-2 mb-4">
      <div class="rounded-[16px] p-3 text-center bg-[var(--search)]">
        <p class="text-[18px] font-extrabold m-0">{{ pctLunas }}%</p>
        <p class="text-[11px] text-[var(--mut)] m-0">Lunas</p>
      </div>
      <div class="rounded-[16px] p-3 text-center bg-[var(--search)]">
        <p class="text-[18px] font-extrabold m-0">{{ formatRpSingkat(terkumpul) }}</p>
        <p class="text-[11px] text-[var(--mut)] m-0">Terkumpul*</p>
      </div>
      <div class="rounded-[16px] p-3 text-center bg-[var(--search)]">
        <p class="text-[18px] font-extrabold m-0 text-amber-600">{{ formatRpSingkat(tunggakan) }}</p>
        <p class="text-[11px] text-[var(--mut)] m-0">Tunggakan</p>
      </div>
    </div>
    <p class="text-[11px] text-[var(--mut)] -mt-2 mb-3 px-1">* Perkiraan dari total yang sudah lunas (bukan saldo kas)</p>

    <template v-if="permintaan.length">
      <h2 class="text-[15px] font-bold mb-1 px-2">Permintaan konfirmasi</h2>
      <div class="px-1 space-y-0.5 mb-4">
        <button
          v-for="p in permintaan"
          :key="p.id"
          type="button"
          class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
          @click="$router.push('/keuangan/permintaan/' + p.id)"
        >
          <div class="w-10 h-10 rounded-full bg-amber-500/15 text-amber-600 grid place-items-center shrink-0">
            <Banknote :size="18" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-bold text-[15px] m-0 leading-tight">{{ p.alamat }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">
              {{ formatRp(p.nominal_diajukan) }} · {{ p.nama_pengirim || '—' }} · {{ p.bank_pengirim || '—' }}
            </p>
          </div>
          <ChevronRight :size="18" class="text-[var(--mut)] shrink-0" />
        </button>
      </div>
    </template>

    <div class="flex gap-2 mb-2 px-1">
      <div class="flex-1 flex items-center gap-2 bg-[var(--search)] rounded-full px-4 h-11">
        <Search :size="16" class="text-[var(--mut)]" />
        <input v-model="q" type="search" placeholder="Cari keluarga" class="flex-1 bg-transparent outline-none text-[14px]" />
      </div>
      <button
        type="button"
        class="h-11 px-4 rounded-full text-[13px] font-semibold shrink-0 bg-[var(--g)] text-white"
        @click="$router.push('/keuangan/catat')"
      >
        Catat
      </button>
    </div>

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="loadErr" class="text-[13px] text-red-600 text-center py-4">{{ loadErr }}</p>
    <EmptyState v-else-if="!filtered.length" title="Tidak ada data iuran" />
    <div v-else class="px-1 space-y-0.5">
      <button
        v-for="k in filtered"
        :key="k.keluarga_id"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/keuangan/tagihan/' + k.keluarga_id)"
      >
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ k.alamat }}</p>
        </div>
        <div class="text-right shrink-0">
          <p class="font-bold text-[15px] m-0" :class="k.total <= 0 ? 'text-[var(--g)]' : ''">
            {{ k.total < 0 ? 'Kelebihan ' + formatRp(-k.total) : formatRp(k.total) }}
          </p>
          <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="statusClass(k.status)">
            {{ labelStatus(k.status) }}
          </span>
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Banknote, ChevronRight, Search } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { daftarIuran, listPermintaan } from '@shared/services/keuangan.js'

const q = ref('')
const loading = ref(true)
const loadErr = ref('')
const daftar = ref([])
const permintaan = ref([])

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return daftar.value
  return daftar.value.filter(
    (k) =>
      (k.nama || '').toLowerCase().includes(s) ||
      (k.alamat || '').toLowerCase().includes(s)
  )
})

const pctLunas = computed(() => {
  const n = daftar.value.length
  if (!n) return 0
  const lunas = daftar.value.filter((k) => k.total <= 0).length
  return Math.round((lunas / n) * 100)
})

const tunggakan = computed(() =>
  daftar.value.reduce((s, k) => s + (k.total > 0 ? k.total : 0), 0)
)

// Perkiraan sederhana: tidak ada data "terkumpul" global di API — tampilkan 0 atau selisih
const terkumpul = computed(() => 0)

function formatRpSingkat(n) {
  const x = Math.round(Number(n) || 0)
  if (x >= 1_000_000) return 'Rp ' + (x / 1_000_000).toFixed(1).replace('.', ',') + 'jt'
  if (x >= 1000) return 'Rp ' + Math.round(x / 1000) + 'rb'
  return formatRp(x)
}

function labelStatus(s) {
  if (s === 'lunas') return 'Lunas'
  if (s === 'menunggak') return 'Menunggak'
  return 'Belum lunas'
}

function statusClass(s) {
  if (s === 'lunas') return 'bg-[var(--ok)] text-[var(--g)]'
  if (s === 'menunggak') return 'bg-red-500/15 text-red-600'
  return 'bg-amber-500/15 text-amber-700'
}

async function load() {
  loading.value = true
  loadErr.value = ''
  const [iuran, perm] = await Promise.all([
    daftarIuran({}),
    listPermintaan('menunggu'),
  ])
  loading.value = false
  if (!iuran.ok) {
    loadErr.value = iuran.error || 'Gagal memuat iuran.'
    return
  }
  daftar.value = iuran.data || []
  if (perm.ok) permintaan.value = perm.data || []
}

onMounted(load)
</script>
