<template>
  <div>
    <AppBackHeader title="Permintaan konfirmasi" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="item">
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4 space-y-3">
        <div><p class="text-[13px] text-[var(--mut)] m-0">Keluarga</p><p class="font-bold m-0">{{ item.alamat || '—' }} · {{ item.nama || item.nama_kepala || '—' }}</p></div>
        <div><p class="text-[13px] text-[var(--mut)] m-0">Nominal diajukan</p><p class="font-bold text-lg m-0">{{ rp(item.nominal) }}</p></div>
        <div v-if="item.keterangan"><p class="text-[13px] text-[var(--mut)] m-0">Keterangan</p><p class="font-bold m-0">{{ item.keterangan }}</p></div>
        <div><p class="text-[13px] text-[var(--mut)] m-0">Status</p><p class="font-bold m-0">{{ item.status }}</p></div>
        <div><p class="text-[13px] text-[var(--mut)] m-0">Waktu</p><p class="font-bold m-0">{{ item.created_at || item.diajukan_pada || '—' }}</p></div>
      </div>
      <div v-if="item.status === 'menunggu'" class="space-y-2">
        <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold"
          @click="$router.push('/keuangan/permintaan/' + item.id + '/konfirmasi')">Konfirmasi</button>
        <button type="button" class="w-full min-h-[48px] rounded-full bg-red-500/15 text-red-600 font-bold"
          @click="$router.push('/keuangan/permintaan/' + item.id + '/tolak')">Tolak</button>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listPermintaan } from '@shared/services/keuangan.js'

const route = useRoute()
const loading = ref(true)
const err = ref('')
const item = ref(null)

function rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID') }

onMounted(async () => {
  const id = String(route.params.id)
  for (const st of ['menunggu', 'dikonfirmasi', 'ditolak', '']) {
    const res = await listPermintaan(st || undefined)
    if (!res.ok) continue
    const found = (res.data || []).find((p) => String(p.id) === id)
    if (found) {
      item.value = found
      break
    }
  }
  loading.value = false
  if (!item.value) err.value = 'Permintaan tidak ditemukan'
})
</script>
