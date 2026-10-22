<template>
  <div class="pb-24">
    <AppBackHeader title="Pilih warga" />

    <div class="mb-3">
      <div class="flex items-center gap-2.5 bg-[var(--search)] rounded-full px-[18px] h-12">
        <Search :size="18" class="text-[var(--mut)] shrink-0" />
        <input
          v-model="cari"
          type="search"
          autocomplete="off"
          placeholder="Cari nama atau alamat…"
          class="flex-1 bg-transparent outline-none text-[15px] min-w-0"
          @input="onCariInput"
        />
        <button
          v-if="cari"
          type="button"
          class="text-[var(--mut)] text-[13px] font-semibold shrink-0"
          @click="clearCari"
        >✕</button>
      </div>
    </div>

    <p v-if="cariLoading" class="text-[13px] text-[var(--mut)] text-center py-4 m-0">Mencari…</p>
    <p v-else-if="cariErr" class="text-[13px] text-red-600 text-center py-4 m-0">{{ cariErr }}</p>
    <p
      v-else-if="cari.trim().length >= 2 && !hasil.length"
      class="text-[13px] text-[var(--mut)] text-center py-6 m-0"
    >Tidak ada anggota cocok</p>
    <p
      v-else-if="cari.trim().length < 2"
      class="text-[13px] text-[var(--mut)] text-center py-6 m-0"
    >Ketik minimal 2 huruf untuk mencari anggota</p>

    <div v-else class="space-y-0.5">
      <button
        v-for="a in hasil"
        :key="a.id"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left rounded-[14px] transition-colors"
        :class="selected?.id === a.id ? 'bg-[var(--gd)]' : 'active:bg-[var(--search)]'"
        @click="pilih(a)"
      >
        <div
          class="w-11 h-11 rounded-full grid place-items-center font-bold text-sm shrink-0"
          :class="selected?.id === a.id ? 'bg-[var(--g)] text-white' : 'bg-[var(--gd)] text-[var(--gm)]'"
        >{{ inisial(a.nama) }}</div>
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ a.nama }}</p>
          <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5 leading-tight">
            {{ a.alamat || '—' }} · {{ a.hubungan || 'Anggota' }}
          </p>
        </div>
        <div
          v-if="selected?.id === a.id"
          class="w-5 h-5 rounded-full bg-[var(--g)] grid place-items-center shrink-0"
        >
          <span class="text-white text-[11px] font-bold">✓</span>
        </div>
      </button>
    </div>

    <!-- sticky bottom action -->
    <div class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--card)] border-t border-[var(--line)] safe-bottom z-20">
      <div class="max-w-lg mx-auto">
        <p v-if="selected" class="text-[12px] text-[var(--mut)] text-center m-0 mb-2">
          Terpilih: <b class="text-[var(--ink)]">{{ selected.nama }}</b>
        </p>
        <button
          type="button"
          class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-40 active:scale-[0.98] transition-transform"
          :disabled="!selected"
          @click="lanjut"
        >Jadikan pengurus</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { Search } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { cariAnggota, listKeluarga, getKeluarga } from '@shared/services/warga.js'

const router = useRouter()

const cari = ref('')
const cariLoading = ref(false)
const cariErr = ref('')
const hasil = ref([])
const selected = ref(null)

let debounceTimer = null
let cariSeq = 0

function inisial(nama) {
  const p = String(nama || '').trim().split(/\s+/)
  if (!p[0]) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

function clearCari() {
  cari.value = ''
  hasil.value = []
  selected.value = null
  cariErr.value = ''
}

function onCariInput() {
  selected.value = null
  clearTimeout(debounceTimer)
  const q = cari.value.trim()
  if (q.length < 2) {
    hasil.value = []
    cariErr.value = ''
    cariLoading.value = false
    return
  }
  debounceTimer = setTimeout(() => doCari(q), 300)
}

function normalizeList(data) {
  if (Array.isArray(data)) return data
  if (data && Array.isArray(data.data)) return data.data
  return []
}

async function doCari(q) {
  const seq = ++cariSeq
  cariLoading.value = true
  cariErr.value = ''
  let list = []

  const res = await cariAnggota(q, 50)
  if (seq !== cariSeq) return
  if (res.ok) {
    list = normalizeList(res.data)
  }

  // Fallback: list keluarga → anggota
  if (!list.length) {
    const lk = await listKeluarga({ q, status: 'aktif' })
    if (seq !== cariSeq) return
    if (lk.ok && Array.isArray(lk.data) && lk.data.length) {
      const qLower = q.toLowerCase()
      const flat = []
      const seen = new Set()
      for (const k of lk.data.slice(0, 25)) {
        const d = await getKeluarga(k.id)
        if (seq !== cariSeq) return
        if (!d.ok || !d.data?.anggota) continue
        const alamat = k.alamat || k.alamat_label || d.data.alamat_label || ''
        for (const a of d.data.anggota) {
          if ((a.status || 'aktif') !== 'aktif') continue
          if (seen.has(a.id)) continue
          seen.add(a.id)
          const nama = String(a.nama || '')
          flat.push({
            id: a.id,
            nama,
            hubungan: a.hubungan,
            keluarga_id: k.id,
            alamat,
            _prio: nama.toLowerCase().includes(qLower) ? 0 : 1,
          })
        }
      }
      flat.sort((a, b) => a._prio - b._prio || String(a.nama).localeCompare(String(b.nama)))
      list = flat.map(({ _prio, ...rest }) => rest)
    } else if (!res.ok) {
      cariErr.value = res.error || 'Gagal mencari'
    }
  }

  if (seq !== cariSeq) return
  cariLoading.value = false
  hasil.value = list
}

function pilih(a) {
  selected.value = a
}

function lanjut() {
  if (!selected.value?.id) return
  const a = selected.value
  router.push({
    path: '/kelola-pengurus/angkat/form',
    query: {
      warga_id: String(a.id),
      nama: a.nama || '',
      alamat: a.alamat || '',
      hubungan: a.hubungan || '',
    },
  })
}

onBeforeUnmount(() => clearTimeout(debounceTimer))
</script>
