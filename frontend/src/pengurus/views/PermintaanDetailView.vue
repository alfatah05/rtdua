<template>
  <div>
    <AppBackHeader title="Permintaan konfirmasi" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="item">
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4 space-y-3">
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Keluarga</p>
          <p class="font-bold m-0">{{ item.alamat || '—' }}</p>
          <p v-if="item.nama || item.nama_kepala" class="text-[13px] text-[var(--mut)] m-0">{{ item.nama || item.nama_kepala }}</p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Nominal diajukan</p>
          <p class="font-bold text-lg m-0">{{ rp(item.nominal_diajukan ?? item.nominal) }}</p>
        </div>
        <div>
          <p class="text-[13px] text-[var(--mut)] m-0">Pengirim</p>
          <p class="m-0">{{ item.nama_pengirim || '—' }} · {{ item.bank_pengirim || '—' }}</p>
        </div>
        <div v-if="item.diajukan_pada">
          <p class="text-[13px] text-[var(--mut)] m-0">Diajukan</p>
          <p class="m-0 text-[14px]">{{ item.diajukan_pada }}</p>
        </div>
        <div v-if="item.bukti_file">
          <p class="text-[13px] text-[var(--mut)] m-0 mb-1">Bukti</p>
          <img :src="buktiUrl" alt="Bukti" class="max-h-48 rounded-[12px] object-contain bg-[var(--search)]" />
        </div>
        <div v-if="item.status && item.status !== 'menunggu'">
          <p class="text-[13px] text-[var(--mut)] m-0">Status</p>
          <p class="font-semibold m-0">{{ item.status }}</p>
        </div>
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
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listPermintaan } from '@shared/services/keuangan.js'
import { mediaUrl } from '@shared/services/upload.js'

const route = useRoute()
const loading = ref(true)
const err = ref('')
const item = ref(null)

const buktiUrl = computed(() => item.value?.bukti_file ? mediaUrl(item.value.bukti_file) : '')

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
