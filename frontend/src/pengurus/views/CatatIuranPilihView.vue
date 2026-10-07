<template>
  <div>
    <AppBackHeader title="Catat iuran" />
    <p class="text-[13px] text-[var(--mut)] px-1 mb-3">Pilih keluarga yang membayar.</p>

    <div class="flex items-center gap-2 bg-[var(--search)] rounded-full px-4 h-11 mb-3">
      <Search :size="16" class="text-[var(--mut)]" />
      <input v-model="q" type="search" placeholder="Cari nama atau alamat" class="flex-1 bg-transparent outline-none text-[14px]" />
    </div>

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <EmptyState v-else-if="!filtered.length" title="Tidak ada keluarga" />
    <div v-else class="px-1 space-y-0.5">
      <button
        v-for="k in filtered"
        :key="k.keluarga_id"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/keuangan/tagihan/' + k.keluarga_id + '/bayar')"
      >
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ k.alamat }}</p>
        </div>
        <div class="text-right shrink-0">
          <p class="font-bold text-[14px] m-0">{{ k.total <= 0 ? 'Lunas' : formatRp(k.total) }}</p>
          <ChevronRight :size="18" class="text-[var(--mut)] inline" />
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Search, ChevronRight } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { daftarIuran } from '@shared/services/keuangan.js'

const q = ref('')
const loading = ref(true)
const err = ref('')
const daftar = ref([])

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
  const res = await daftarIuran({})
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat.'
    return
  }
  daftar.value = res.data || []
}

onMounted(load)
</script>
