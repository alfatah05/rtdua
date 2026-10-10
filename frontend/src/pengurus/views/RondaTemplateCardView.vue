<template>
  <div>
    <AppBackHeader :title="title" />

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <p class="text-[13px] text-[var(--mut)] mb-3">
        Pilih keluarga yang ronda di card ini. Perubahan berlaku di setiap periode (loop).
      </p>

      <input
        v-model="q"
        type="search"
        placeholder="Cari nama / alamat…"
        class="w-full min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none mb-3"
      />

      <p v-if="wargaLoading" class="text-[13px] text-[var(--mut)] text-center py-4">Memuat daftar warga…</p>
      <p v-else-if="!filtered.length" class="text-[13px] text-[var(--mut)] text-center py-4">Tidak ada data</p>

      <div v-else class="space-y-0 mb-24">
        <label
          v-for="w in filtered"
          :key="w.id"
          class="flex items-center gap-3 py-3 border-b border-[var(--line)]"
        >
          <input type="checkbox" class="w-5 h-5 shrink-0" :value="w.id" v-model="selected" />
          <span class="min-w-0 flex-1">
            <span class="block text-[14px] font-semibold truncate">{{ w.nama || '—' }}</span>
            <span class="block text-[12px] text-[var(--mut)]">{{ w.alamat }}</span>
          </span>
        </label>
      </div>

      <div class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--bg)] border-t border-[var(--line)] pb-[calc(16px+env(safe-area-inset-bottom,0px))]">
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
