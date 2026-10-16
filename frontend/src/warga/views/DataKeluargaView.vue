<template>
  <div>
    <AppBackHeader title="Data keluarga">
      <template #actions>
        <button
          type="button"
          class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]"
          aria-label="Tambah anggota"
          @click="$router.push('/data-keluarga/tambah')"
        >
          <PlusCircle :size="20" />
        </button>
      </template>
    </AppBackHeader>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else-if="data">
      <div class="flex flex-col items-center text-center mb-6 pt-2">
        <div
          class="w-24 h-24 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center text-3xl font-bold overflow-hidden mb-3"
        >
          <img
            v-if="fotoUrl"
            :src="fotoUrl"
            alt=""
            class="w-full h-full object-cover"
            @error="fotoUrl = ''"
          />
          <span v-else>{{ inisial(data.nama) }}</span>
        </div>
        <p class="text-xl font-extrabold m-0 leading-tight">{{ data.nama || '—' }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-1">{{ data.alamat }}</p>
      </div>

      <div class="flex items-center justify-between mb-1 px-1">
        <p class="text-[13px] font-semibold text-[var(--mut)] m-0">Anggota keluarga</p>
        <button
          type="button"
          class="text-[13px] font-semibold text-[var(--g)] m-0"
          @click="$router.push('/data-keluarga/edit')"
        >
          Edit
        </button>
      </div>

      <div class="px-1">
        <div
          v-for="a in data.anggota || []"
          :key="a.id"
          class="flex items-center gap-3 py-3"
          :class="a.status && a.status !== 'aktif' ? 'opacity-60' : ''"
        >
          <div
            class="w-10 h-10 rounded-full bg-[var(--card2)] text-[var(--text)] grid place-items-center text-[12px] font-bold shrink-0"
          >
            {{ inisial(a.nama) }}
          </div>
          <p class="flex-1 min-w-0 font-semibold text-[15px] m-0 truncate">{{ a.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 shrink-0 text-right max-w-[40%]">
            {{ a.hubungan || '—' }}
          </p>
        </div>
        <p
          v-if="!(data.anggota || []).length"
          class="text-[13px] text-[var(--mut)] text-center py-4"
        >Belum ada anggota</p>
      </div>

      <button
        type="button"
        class="w-full min-h-[48px] mt-4 rounded-full border border-[var(--line)] font-bold text-[15px]"
        @click="$router.push('/data-keluarga/tambah')"
      >
        + Tambah anggota
      </button>
    </template>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, onMounted } from 'vue'
import { PlusCircle } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalKeluargaSaya } from '@shared/services/warga.js'
import { mediaUrl } from '@shared/services/upload.js'
import { waitImages } from '@shared/utils/waitImages.js'

const loading = ref(true)
const err = ref('')
const data = ref(null)
const fotoUrl = ref('')

function inisial(nama) {
  const p = String(nama || '').trim().split(/\s+/).filter(Boolean)
  if (!p.length) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await portalKeluargaSaya()
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    loading.value = false
    return
  }
  data.value = res.data
  fotoUrl.value = res.data?.foto ? mediaUrl(res.data.foto) : ''
  if (fotoUrl.value) await waitImages([fotoUrl.value])
  loading.value = false
}

onMounted(load)
</script>
