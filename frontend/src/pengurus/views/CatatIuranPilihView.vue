<template>
  <div>
    <AppBackHeader title="Tagihan warga" />

    <div class="mb-4">
      <div class="flex items-center gap-2.5 bg-[var(--search)] rounded-full px-[18px] h-12">
        <Search :size="18" class="text-[var(--mut)] shrink-0" />
        <input
          v-model="q"
          type="search"
          placeholder="Cari nama atau blok/nomor rumah"
          class="flex-1 min-w-0 bg-transparent outline-none text-[15px] text-[var(--text)] placeholder:text-[var(--mut)]"
        />
      </div>
    </div>

    <p class="text-[13px] text-[var(--mut)] mb-1 px-2">{{ filtered.length }} keluarga</p>
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <EmptyState v-else-if="!filtered.length" title="Tidak ada keluarga" />
    <div v-else class="px-1 space-y-0.5">
      <button
        v-for="k in filtered"
        :key="k.keluarga_id"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/keuangan/tagihan/' + k.keluarga_id)"
      >
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0 overflow-hidden">
          <img
            v-if="k.fotoUrl"
            :src="k.fotoUrl"
            alt=""
            class="w-full h-full object-cover"
            @error="k.fotoUrl = ''"
          />
          <template v-else>{{ k.inisial }}</template>
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ k.alamat }}</p>
        </div>
        <span
          class="shrink-0 text-[11px] font-bold px-2.5 py-1 rounded-full"
          :class="k.total <= 0
            ? 'bg-[var(--ok)] text-[var(--g)]'
            : 'bg-amber-500/15 text-amber-700 dark:text-amber-400'"
        >
          {{ k.total <= 0 ? 'Lunas' : formatRp(k.total) }}
        </span>
        <ChevronRight :size="18" class="text-[var(--mut)] shrink-0" />
      </button>
    </div>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, computed, onMounted } from 'vue'
import { Search, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { daftarIuran } from '@shared/services/keuangan.js'
import { listKeluarga } from '@shared/services/warga.js'
import { mediaUrl } from '@shared/services/upload.js'

const q = ref('')
const loading = ref(true)
const err = ref('')
const daftar = ref([])

function inisial(nama) {
  return String(nama || '?')
    .split(/\s+/)
    .map((w) => w[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return daftar.value
  return daftar.value.filter(
    (k) =>
      (k.nama || '').toLowerCase().includes(s) ||
      (k.alamat || '').toLowerCase().includes(s)
  )
})

async function load() {
  loading.value = true
  err.value = ''
  const [iu, kel] = await Promise.all([
    daftarIuran({}),
    listKeluarga({ status: 'aktif' }),
  ])
  loading.value = false
  if (!iu.ok) {
    err.value = iu.error || 'Gagal memuat.'
    return
  }
  const fotoMap = {}
  if (kel.ok) {
    for (const k of kel.data || []) {
      if (k.foto) fotoMap[k.id] = mediaUrl(k.foto)
    }
  }
  daftar.value = (iu.data || []).map((k) => ({
    ...k,
    inisial: inisial(k.nama),
    fotoUrl: fotoMap[k.keluarga_id] || '',
  }))
}

onMounted(load)
</script>
