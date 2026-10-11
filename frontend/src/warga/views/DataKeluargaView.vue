<template>
  <div>
    <AppBackHeader title="Data keluarga" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 mb-4">
        <p class="text-[13px] text-[var(--mut)] m-0">Alamat rumah</p>
        <p class="font-extrabold text-[18px] m-0 mt-1">{{ data?.alamat || '—' }}</p>
      </div>
      <h2 class="text-[15px] font-bold mb-2">Anggota</h2>
      <div class="space-y-2">
        <div
          v-for="a in data?.anggota || []"
          :key="a.id"
          class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5"
          :class="a.status !== 'aktif' ? 'opacity-60' : ''"
        >
          <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0 overflow-hidden">
            <img v-if="a.fotoUrl" :src="a.fotoUrl" alt="" class="w-full h-full object-cover" @error="a.fotoUrl = ''" />
            <template v-else>{{ inisial(a.nama) }}</template>
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-bold m-0 text-[15px]">{{ a.nama }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0">
              {{ a.hubungan || '—' }}
              <span v-if="a.status && a.status !== 'aktif'"> · {{ a.status }}</span>
            </p>
          </div>
        </div>
        <p v-if="!(data?.anggota || []).length" class="text-[13px] text-[var(--mut)] text-center py-4">Belum ada anggota</p>
      </div>
      <p class="text-[12px] text-[var(--mut)] text-center mt-6">Data diisi pengurus. Hubungi pengurus bila ada yang perlu diperbarui.</p>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalKeluargaSaya } from '@shared/services/warga.js'
import { mediaUrl } from '@shared/services/upload.js'

const loading = ref(true)
const err = ref('')
const data = ref(null)

function inisial(nama) {
  return String(nama || '?').split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()
}

onMounted(async () => {
  const res = await portalKeluargaSaya()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    return
  }
  const d = res.data || {}
  data.value = {
    ...d,
    anggota: (d.anggota || []).map((a) => ({
      ...a,
      fotoUrl: a.foto ? mediaUrl(a.foto) : '',
    })),
  }
})
</script>
