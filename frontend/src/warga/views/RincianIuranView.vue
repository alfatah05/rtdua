<template>
  <div>
    <AppBackHeader title="Rincian iuran" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 mb-4">
        <p class="text-[13px] text-[var(--mut)] m-0">Total belum lunas</p>
        <p class="text-[28px] font-extrabold mt-1 m-0">{{ rp(total) }}</p>
      </div>
      <div class="space-y-2 mb-4">
        <div v-for="t in tagihan" :key="t.id" class="flex justify-between bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5">
          <div>
            <p class="font-bold m-0">{{ labelJenis(t.jenis) }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0">{{ t.periode }}</p>
          </div>
          <span class="font-bold">{{ rp(t.sisa ?? t.nominal) }}</span>
        </div>
        <p v-if="!tagihan.length" class="text-[13px] text-[var(--mut)] text-center py-4">Tidak ada tagihan</p>
      </div>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="$router.push('/transfer')">Saya sudah transfer</button>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalRingkasan } from '@shared/services/keuangan.js'
const loading = ref(true)
const err = ref('')
const total = ref(0)
const tagihan = ref([])
function rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID') }
function labelJenis(j) {
  const m = { kas: 'Kas', denda_ronda: 'Denda ronda', khusus: 'Iuran khusus' }
  return m[j] || j || 'Tagihan'
}
onMounted(async () => {
  const res = await portalRingkasan()
  loading.value = false
  if (!res.ok) { err.value = res.error || 'Gagal'; return }
  total.value = res.data?.total ?? 0
  tagihan.value = res.data?.tagihan || []
})
</script>
