<template>
  <div>
    <AppBackHeader :title="title" />

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <div class="flex items-center gap-2.5 bg-[var(--search)] rounded-full px-[18px] h-12 mb-3">
        <Search :size="18" class="text-[var(--mut)] shrink-0" />
        <input
          v-model="q"
          type="search"
          placeholder="Cari nama / alamat…"
          class="flex-1 min-w-0 bg-transparent outline-none text-[15px] text-[var(--text)] placeholder:text-[var(--mut)]"
        />
      </div>

      <p v-if="wargaLoading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
      <p v-else-if="!filtered.length" class="text-[13px] text-[var(--mut)] text-center py-6">Tidak ada data</p>

      <div v-else class="space-y-0.5 mb-28">
        <button
          v-for="w in filtered"
          :key="w.id"
          type="button"
          class="w-full flex items-center gap-3 px-1 py-3 text-left active:scale-[0.99] transition-transform"
          @click="toggle(w.id)"
        >
          <span
            class="w-6 h-6 rounded-[8px] shrink-0 grid place-items-center border-2 transition-colors"
            :class="isOn(w.id)
              ? 'bg-[var(--g)] border-[var(--g)] text-white'
              : 'bg-transparent border-[var(--card2)]'"
            aria-hidden="true"
          >
            <Check v-if="isOn(w.id)" :size="14" :stroke-width="3" />
          </span>

          <div class="w-11 h-11 rounded-full overflow-hidden shrink-0 bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm">
            <img
              v-if="w.fotoUrl"
              :src="w.fotoUrl"
              alt=""
              class="w-full h-full object-cover"
              @error="w.fotoUrl = ''"
            />
            <span v-else>{{ inisial(w.nama) }}</span>
          </div>

          <span class="text-[15px] font-bold min-w-0 flex-1 truncate leading-tight">{{ w.nama || '—' }}</span>
          <span class="text-[13px] text-[var(--mut)] shrink-0 text-right max-w-[38%] truncate">{{ w.alamat }}</span>
        </button>
      </div>

      <div class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--bg)] pb-[calc(16px+env(safe-area-inset-bottom,0px))] z-30">
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
import { Search, Check } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listTemplateCards, simpanTemplateKeluarga } from '@shared/services/ronda.js'
import { listKeluarga } from '@shared/services/warga.js'
import { mediaUrl } from '@shared/services/upload.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const cardId = computed(() => Number(route.params.id || 0))

const loading = ref(true)
const wargaLoading = ref(false)
const err = ref('')
const title = ref('Ubah warga')
const q = ref('')
const selected = ref([])
const wargaList = ref([])
const saveBusy = ref(false)

function inisial(nama) {
  if (!nama) return '?'
  const p = String(nama).trim().split(/\s+/)
  return ((p[0]?.[0] || '') + (p[1]?.[0] || '')).toUpperCase() || '?'
}

function isOn(id) {
  return selected.value.includes(Number(id))
}

function toggle(id) {
  const n = Number(id)
  const i = selected.value.indexOf(n)
  if (i >= 0) {
    selected.value = selected.value.filter((x) => x !== n)
  } else {
    selected.value = [...selected.value, n]
  }
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
  selected.value = (card.keluarga || []).map((k) => Number(k.keluarga_id)).filter((x) => x > 0)

  wargaLoading.value = true
  const wres = await listKeluarga({ status: 'aktif' })
  wargaLoading.value = false
  if (!wres.ok) {
    wargaList.value = []
    err.value = wres.error || 'Gagal memuat daftar warga'
    return
  }
  wargaList.value = (wres.data || [])
    .map((r) => ({
      id: Number(r.id),
      nama: r.nama || '—',
      alamat: r.alamat || '',
      fotoUrl: r.foto ? mediaUrl(r.foto) : '',
    }))
    .filter((x) => x.id > 0)
}

async function simpan() {
  saveBusy.value = true
  const ids = selected.value.map((x) => Number(x)).filter((x) => x > 0)
  const res = await simpanTemplateKeluarga(cardId.value, ids)
  saveBusy.value = false
  if (!res.ok) {
    toast.error(res.error || 'Gagal simpan')
    return
  }
  const n = res.data?.jumlah ?? ids.length
  toast.success(n ? `${n} warga disimpan` : 'Daftar dikosongkan')
  router.replace('/ronda/jadwal-tetap')
}

onMounted(load)
</script>
