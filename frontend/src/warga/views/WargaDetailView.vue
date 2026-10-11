<template>
  <div>
    <AppBackHeader title="Profil keluarga" />

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-8">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else-if="data">
      <!-- Hero seperti profil -->
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
        <p class="text-xl font-extrabold m-0 leading-tight">{{ data.nama }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-1">{{ data.alamat }}</p>
      </div>

      <p class="text-[13px] font-semibold text-[var(--mut)] mb-1 px-1">Anggota keluarga</p>

      <div class="px-1">
        <div
          v-for="a in data.anggota || []"
          :key="a.id"
          class="flex items-center gap-3 py-3"
        >
          <div
            class="w-10 h-10 rounded-full bg-[var(--card2)] text-[var(--text)] grid place-items-center text-[12px] font-bold shrink-0"
          >
            {{ inisial(a.nama) }}
          </div>
          <p class="flex-1 min-w-0 font-semibold text-[15px] m-0 truncate">{{ a.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 shrink-0 text-right max-w-[40%]">{{ a.hubungan }}</p>
        </div>
        <p
          v-if="!(data.anggota || []).length"
          class="text-[13px] text-[var(--mut)] text-center py-4"
        >Belum ada anggota</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalDetailKeluarga } from '@shared/services/warga.js'
import { mediaUrl } from '@shared/services/upload.js'

const route = useRoute()
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

onMounted(async () => {
  const id = route.params.id
  const res = await portalDetailKeluarga(id)
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  data.value = res.data
  fotoUrl.value = res.data?.foto ? mediaUrl(res.data.foto) : ''
})
</script>
