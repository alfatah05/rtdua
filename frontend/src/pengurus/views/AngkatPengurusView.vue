<template>
  <div class="pb-24">
    <AppBackHeader :title="step === 'keluarga' ? 'Pilih keluarga' : 'Pilih anggota'" />

    <!-- STEP 1: list keluarga (sama pola halaman Warga) -->
    <template v-if="step === 'keluarga'">
      <div class="mb-3">
        <div class="flex items-center gap-2.5 bg-[var(--search)] rounded-full px-[18px] h-12">
          <Search :size="18" class="text-[var(--mut)] shrink-0" />
          <input
            v-model="q"
            type="search"
            autocomplete="off"
            placeholder="Cari nama atau blok/nomor rumah"
            class="flex-1 min-w-0 bg-transparent outline-none text-[15px] text-[var(--text)] placeholder:text-[var(--mut)]"
          />
        </div>
      </div>

      <AppPageSpinner v-if="loading" />
      <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4 m-0">{{ err }}</p>
      <template v-else>
        <p class="text-[13px] text-[var(--mut)] mb-1 px-2 m-0">{{ filtered.length }} keluarga</p>
        <div class="px-1 space-y-0.5">
          <button
            v-for="k in filtered"
            :key="k.id"
            type="button"
            class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
            @click="pilihKeluarga(k)"
          >
            <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0 overflow-hidden">
              {{ k.inisial }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-bold text-[15px] m-0 leading-tight">{{ k.nama }}</p>
              <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ k.alamat }}</p>
            </div>
            <ChevronRight :size="18" class="text-[var(--mut)] shrink-0" />
          </button>
        </div>
        <p v-if="!filtered.length" class="text-[13px] text-[var(--mut)] text-center py-6 m-0">Tidak ada keluarga cocok</p>
      </template>
    </template>

    <!-- STEP 2: list anggota di keluarga terpilih -->
    <template v-else>
      <div class="bg-[var(--gd)] rounded-[14px] px-4 py-3 mb-3 flex items-start justify-between gap-2">
        <div class="min-w-0">
          <p class="text-[12px] text-[var(--mut)] m-0">Keluarga</p>
          <p class="font-bold text-[15px] m-0 leading-tight">{{ selectedKeluarga?.nama }}</p>
          <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">{{ selectedKeluarga?.alamat }}</p>
        </div>
        <button type="button" class="text-[13px] font-semibold text-[var(--g)] shrink-0" @click="backKeluarga">Ganti</button>
      </div>

      <p v-if="loadingAnggota" class="text-[13px] text-[var(--mut)] text-center py-4 m-0">Memuat anggota…</p>
      <p v-else-if="errAnggota" class="text-[13px] text-red-600 text-center py-4 m-0">{{ errAnggota }}</p>
      <div v-else class="px-1 space-y-0.5">
        <button
          v-for="a in anggotaList"
          :key="a.id"
          type="button"
          class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left rounded-[14px] transition-colors"
          :class="selectedAnggota?.id === a.id ? 'bg-[var(--gd)]' : 'active:bg-[var(--search)]'"
          @click="selectedAnggota = a"
        >
          <div
            class="w-11 h-11 rounded-full grid place-items-center font-bold text-sm shrink-0"
            :class="selectedAnggota?.id === a.id ? 'bg-[var(--g)] text-white' : 'bg-[var(--gd)] text-[var(--gm)]'"
          >{{ inisial(a.nama) }}</div>
          <div class="flex-1 min-w-0">
            <p class="font-bold text-[15px] m-0 leading-tight">{{ a.nama }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">{{ a.hubungan || 'Anggota' }}</p>
          </div>
          <div
            v-if="selectedAnggota?.id === a.id"
            class="w-5 h-5 rounded-full bg-[var(--g)] grid place-items-center shrink-0"
          >
            <span class="text-white text-[11px] font-bold">✓</span>
          </div>
        </button>
        <p v-if="!anggotaList.length" class="text-[13px] text-[var(--mut)] text-center py-6 m-0">Tidak ada anggota aktif</p>
      </div>
    </template>

    <!-- sticky bottom — hanya saat step anggota -->
    <div
      v-if="step === 'anggota'"
      class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--card)] border-t border-[var(--line)] z-20"
    >
      <div class="max-w-lg mx-auto">
        <p v-if="selectedAnggota" class="text-[12px] text-[var(--mut)] text-center m-0 mb-2">
          Terpilih: <b class="text-[var(--ink)]">{{ selectedAnggota.nama }}</b>
        </p>
        <button
          type="button"
          class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-40 active:scale-[0.98] transition-transform"
          :disabled="!selectedAnggota"
          @click="lanjut"
        >Jadikan pengurus</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Search, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { listKeluarga, getKeluarga } from '@shared/services/warga.js'

const router = useRouter()

const step = ref('keluarga') // 'keluarga' | 'anggota'
const q = ref('')
const list = ref([])
const loading = ref(true)
const err = ref('')

const selectedKeluarga = ref(null)
const anggotaList = ref([])
const loadingAnggota = ref(false)
const errAnggota = ref('')
const selectedAnggota = ref(null)

function inisial(nama) {
  const p = String(nama || '').trim().split(/\s+/)
  if (!p[0]) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await listKeluarga({ status: 'aktif' })
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat data warga'
    return
  }
  list.value = (res.data || []).map((k) => ({
    id: k.id,
    nama: k.nama || 'Keluarga',
    alamat: k.alamat || k.alamat_label || '',
    inisial: inisial(k.nama),
  }))
}

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return list.value
  return list.value.filter(
    (k) =>
      String(k.nama || '').toLowerCase().includes(s) ||
      String(k.alamat || '').toLowerCase().includes(s)
  )
})

async function pilihKeluarga(k) {
  selectedKeluarga.value = k
  selectedAnggota.value = null
  anggotaList.value = []
  errAnggota.value = ''
  step.value = 'anggota'
  loadingAnggota.value = true
  const res = await getKeluarga(k.id)
  loadingAnggota.value = false
  if (!res.ok) {
    errAnggota.value = res.error || 'Gagal memuat anggota'
    return
  }
  const anggota = res.data?.anggota || []
  anggotaList.value = anggota.filter((a) => (a.status || 'aktif') === 'aktif')
}

function backKeluarga() {
  step.value = 'keluarga'
  selectedKeluarga.value = null
  selectedAnggota.value = null
  anggotaList.value = []
  errAnggota.value = ''
}

function lanjut() {
  const a = selectedAnggota.value
  const k = selectedKeluarga.value
  if (!a?.id) return
  router.push({
    path: '/kelola-pengurus/angkat/form',
    query: {
      warga_id: String(a.id),
      nama: a.nama || '',
      alamat: k?.alamat || '',
      hubungan: a.hubungan || '',
    },
  })
}

onMounted(load)
</script>
