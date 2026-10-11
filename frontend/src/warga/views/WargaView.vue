<template>
  <div>
    <div class="mb-3 relative">
      <Search
        :size="18"
        class="absolute left-[18px] top-1/2 -translate-y-1/2 text-[var(--mut)] pointer-events-none"
      />
      <input
        v-model="q"
        type="search"
        placeholder="Cari nama / alamat"
        class="w-full min-h-12 h-12 pl-12 pr-4 rounded-full bg-[var(--search)] outline-none border-0 focus:outline-none focus:ring-0 focus:border-0 text-[15px]"
      />
    </div>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <div v-else class="px-1">
      <button
        v-for="k in filtered"
        :key="k.id"
        type="button"
        class="w-full flex items-center gap-3 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/warga/' + k.id)"
      >
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0 overflow-hidden">
          <img v-if="k.fotoUrl" :src="k.fotoUrl" alt="" class="w-full h-full object-cover" @error="k.fotoUrl = ''" />
          <template v-else>{{ k.inisial }}</template>
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-bold m-0 text-[15px]">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ k.alamat }}</p>
        </div>
        <ChevronRight :size="18" class="text-[var(--mut)] shrink-0" />
      </button>
      <p v-if="!filtered.length" class="text-[13px] text-[var(--mut)] text-center py-4">Tidak ada data</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ChevronRight, Search } from 'lucide-vue-next'
import { listPortalWarga } from '@shared/services/warga.js'
import { mediaUrl } from '@shared/services/upload.js'

const list = ref([])
const q = ref('')
const loading = ref(true)
const err = ref('')

function inisial(nama) {
  return String(nama || '?').split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()
}

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return list.value
  return list.value.filter(
    (k) => (k.nama || '').toLowerCase().includes(s) || (k.alamat || '').toLowerCase().includes(s)
  )
})

onMounted(async () => {
  const res = await listPortalWarga()
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else {
    list.value = (res.data || []).map((k) => ({
      ...k,
      inisial: inisial(k.nama),
      fotoUrl: k.foto ? mediaUrl(k.foto) : '',
    }))
  }
})
</script>
