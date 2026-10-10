<template>
  <div>
    <AppBackHeader :title="title" />

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <input
        v-model="q"
        type="search"
        placeholder="Cari nama / alamat…"
        class="w-full min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none mb-3"
      />

      <p v-if="wargaLoading" class="text-[13px] text-[var(--mut)] text-center py-4">Memuat daftar warga…</p>
      <p v-else-if="!filtered.length" class="text-[13px] text-[var(--mut)] text-center py-4">Tidak ada data</p>

      <div v-else class="space-y-1 mb-24">
        <label
          v-for="w in filtered"
          :key="w.id"
          class="flex items-center gap-3 py-2.5 px-1"
        >
          <input type="checkbox" class="w-5 h-5 shrink-0" :value="w.id" v-model="selected" />
          <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 bg-[var(--card2)] grid place-items-center text-[12px] font-bold text-[var(--gm)]">
            <img v-if="w.fotoUrl" :src="w.fotoUrl" alt="" class="w-full h-full object-cover" @error="w.fotoUrl = ''" />
            <span v-else>{{ inisial(w.nama) }}</span>
          </div>
          <span class="text-[14px] font-semibold min-w-0 flex-1 truncate">{{ w.nama || '—' }}</span>
          <span class="text-[13px] text-[var(--mut)] shrink-0 text-right">{{ w.alamat }}</span>
        </label>
      </div>

      <div class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--bg)] pb-[calc(16px+env(safe-area-inset-bottom,0px))]">
        <p v-if="msg" class="text-[13px] text-center mb-2" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
        <button
          type="button"
          class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50 active:scale-[0.99]"
          :disabled="saveBusy"
          @click="simpan"
        >{{ saveBusy ? 'Menyimpan…' : 'Simpan (' + selected.length + ')' }}</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listTemplateCards, simpanTemplateKeluarga } from '@shared/services/ronda.js'
import { api } from '@shared/api/http.js'
import { mediaUrl } from '@shared/services/upload.js'

const route = useRoute()
const router = useRouter()
const cardId = computed(() => Number(route.params.id || 0))

const loading = ref(true)
const wargaLoading = ref(false)
const err = ref('')
const title = ref('Ubah warga')
const q = ref('')
const selected = ref([])
const wargaList = ref([])
const saveBusy = ref(false)
const msg = ref('')
const msgOk = ref(true)

function inisial(nama) {
  if (!nama) return '?'
  const p = String(nama).trim().split(/\s+/)
  return ((p[0]?.[0] || '') + (p[1]?.[0] || '')).toUpperCase() || '?'
}

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return wargaList.value
  return wargaList.value.filter(
    (w) =>
      String(w.nama || '').toLowerCase().includes(s) ||
      String(w.alamat || '').toLowerCase().includes(s),
  )
})

async function load() {
  loading.value = true
  err.value = ''
  const res = await listTemplateCards()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat card'
    return
  }
  const card = (res.data || []).find((c) => Number(c.id) === cardId.value)
  if (!card) {
    err.value = 'Card tidak ditemukan'
    return
  }
  title.value = card.label || 'Ubah warga'
  selected.value = (card.keluarga || []).map((k) => Number(k.keluarga_id))

  wargaLoading.value = true
  try {
    const wres = await api('/warga')
    const rows = wres.data || wres || []
    const list = Array.isArray(rows) ? rows : []
    wargaList.value = list
      .map((r) => ({
        id: Number(r.id || r.keluarga_id),
        nama: r.nama_kepala || r.kepala || r.nama || '—',
        alamat: r.alamat || `${r.blok || ''}-${r.nomor || ''}${r.akhiran || ''}`,
        fotoUrl: r.foto ? mediaUrl(r.foto) : '',
      }))
      .filter((x) => x.id > 0)
  } catch (e) {
    wargaList.value = []
    err.value = e?.message || 'Gagal memuat daftar warga'
  }
  wargaLoading.value = false
}

async function simpan() {
  saveBusy.value = true
  msg.value = ''
  const res = await simpanTemplateKeluarga(cardId.value, selected.value)
  saveBusy.value = false
  if (!res.ok) {
    msgOk.value = false
    msg.value = res.error || 'Gagal simpan'
    return
  }
  msgOk.value = true
  msg.value = 'Tersimpan'
  router.replace('/ronda/jadwal-tetap')
}

onMounted(load)
</script>
