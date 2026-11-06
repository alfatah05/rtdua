<template>
  <div>
    <AppMainHeader show-profil />

    <div class="flex gap-2 mb-3">
      <select
        v-model.number="bulan"
        @change="load"
        class="flex-1 min-h-[44px] px-4 rounded-full bg-[var(--search)] text-[var(--text)] border-0 outline-none ring-0 shadow-none appearance-none focus:outline-none focus:ring-0 focus:border-0 focus:shadow-none"
      >
        <option v-for="b in daftarBulan" :key="b.v" :value="b.v">{{ b.l }}</option>
      </select>
      <select
        v-model="tahun"
        @change="load"
        class="w-28 min-h-[44px] px-4 rounded-full bg-[var(--search)] text-[var(--text)] border-0 outline-none ring-0 shadow-none appearance-none focus:outline-none focus:ring-0 focus:border-0 focus:shadow-none"
      >
        <option v-for="t in daftarTahun" :key="t" :value="t">{{ t }}</option>
      </select>
    </div>

    <div
      class="flex p-1 rounded-full bg-[var(--card2)] mb-3"
      role="tablist"
      aria-label="Kategori aktivitas"
    >
      <button
        v-for="t in tabs"
        :key="t.id"
        type="button"
        role="tab"
        class="flex-1 min-h-[40px] rounded-full text-[13px] font-semibold transition"
        :class="tab === t.id
          ? 'bg-[var(--card)] text-[var(--text)] shadow-[var(--sh)]'
          : 'text-[var(--mut)]'"
        :aria-selected="tab === t.id"
        @click="tab = t.id"
      >
        {{ t.label }}
      </button>
    </div>

    <p class="text-[13px] text-[var(--mut)] mb-1 px-2">{{ filtered.length }} aktivitas</p>

    <AppPageSpinner v-if="loading" class="!py-8" />
    <div v-else-if="!filtered.length" class="px-2 py-8 text-center text-[var(--mut)] text-[14px]">
      Tidak ada aktivitas {{ tabLabel }} di bulan ini
    </div>
    <div v-else class="px-1 space-y-0.5">
      <button
        v-for="act in filtered"
        :key="act.id"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/aktivitas/' + act.id)"
      >
        <div
          class="w-10 h-10 rounded-full grid place-items-center shrink-0"
          :style="{ background: iconMeta(act.aksi).bg, color: iconMeta(act.aksi).color }"
        >
          <component :is="iconMeta(act.aksi).icon" :size="18" />
        </div>
        <div class="min-w-0 flex-1">
          <!-- Judul utama: Pelaku · Aksi (tebal, menonjol) -->
          <p class="font-bold text-[15px] m-0 leading-snug">{{ listTitle(act) }}</p>
          <!-- Sekunder: warga terkait + alamat (kecil) -->
          <p
            v-if="namaSubjek(act) || identitasAlamat(act)"
            class="text-[12px] text-[var(--mut)] m-0 leading-tight mt-0.5 truncate"
          >
            <template v-if="namaSubjek(act)">{{ namaSubjek(act) }}</template>
            <template v-if="namaSubjek(act) && identitasAlamat(act)"> · </template>
            <template v-if="identitasAlamat(act)">{{ identitasAlamat(act) }}</template>
          </p>
          <p class="text-[12px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ formatWaktu(act.waktu) }}</p>
        </div>
        <ChevronRight :size="18" class="text-[var(--mut)] shrink-0" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { listAktivitas } from '@shared/services/aktivitas.js'
import {
  listTitle, labelAksi, iconMeta, formatWaktu, kategoriAktivitas,
  namaSubjek, identitasAlamat,
} from '@shared/utils/aktivitas.js'
import { ChevronRight } from 'lucide-vue-next'

const tabs = [
  { id: 'pengurus', label: 'Pengurus' },
  { id: 'keuangan', label: 'Keuangan' },
  { id: 'warga', label: 'Warga' },
]

const tab = ref('pengurus')
const now = new Date()
const bulan = ref(now.getMonth() + 1)
const tahun = ref(String(now.getFullYear()))
const daftarBulan = [
  { v: 1, l: 'Januari' }, { v: 2, l: 'Februari' }, { v: 3, l: 'Maret' },
  { v: 4, l: 'April' }, { v: 5, l: 'Mei' }, { v: 6, l: 'Juni' },
  { v: 7, l: 'Juli' }, { v: 8, l: 'Agustus' }, { v: 9, l: 'September' },
  { v: 10, l: 'Oktober' }, { v: 11, l: 'November' }, { v: 12, l: 'Desember' },
]
const daftarTahun = [String(now.getFullYear()), String(now.getFullYear() - 1)]
const items = ref([])
const loading = ref(true)

const tabLabel = computed(() => {
  const t = tabs.find((x) => x.id === tab.value)
  return t ? t.label.toLowerCase() : ''
})

const filtered = computed(() =>
  (items.value || []).filter((a) => kategoriAktivitas(a) === tab.value)
)

function inSelectedMonth(act) {
  const w = String(act?.waktu || '')
  if (w.length < 7) return false
  const b = Number(bulan.value)
  const t = String(tahun.value)
  const prefix = `${t}-${String(b).padStart(2, '0')}`
  return w.startsWith(prefix)
}

async function load() {
  loading.value = true
  const res = await listAktivitas({
    bulan: Number(bulan.value),
    tahun: String(tahun.value),
    limit: 200,
  })
  loading.value = false
  if (res.ok) {
    // Cadangan filter client jika backend belum ter-deploy / gagal filter
    const raw = res.data || []
    items.value = raw
      .filter((a) => {
        // Hanya hide login/logout warga — aktivitas lain biarkan
        const aksi = String(a?.aksi || '')
        if (aksi !== 'login' && aksi !== 'logout') return true
        const role = String(a?.role || '').toLowerCase()
        if (role === 'warga') return false
        if (a?.sesudah && typeof a.sesudah === 'object' && a.sesudah.side === 'warga') return false
        return true
      })
      .filter(inSelectedMonth)
  } else {
    items.value = []
  }
}

onMounted(load)
watch([bulan, tahun], load)
</script>
