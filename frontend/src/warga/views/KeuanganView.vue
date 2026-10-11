<template>
  <div>
    <AppMainHeader />
    <h2 class="text-[17px] font-bold mb-3">Keuangan</h2>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-8">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div class="rounded-[20px] p-5 text-white mb-4 relative overflow-hidden"
        style="background: radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)">
        <p class="text-[13px] font-semibold text-white/90">{{ heroLabel }}</p>
        <p class="text-[32px] font-extrabold mt-2.5 tracking-tight leading-none">{{ heroNominal }}</p>
        <p class="text-[13px] text-white/80 mt-2">{{ heroSub }}</p>
        <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
      </div>
      <div class="grid grid-cols-2 gap-3 mb-4">
        <button type="button" class="min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="$router.push('/rincian-iuran')">Rincian iuran</button>
        <button type="button" class="min-h-[48px] rounded-full bg-[var(--card2)] font-bold" @click="$router.push('/transfer')">Saya transfer</button>
      </div>
      <h2 class="text-[15px] font-bold mb-2">Tagihan terbuka</h2>
      <div class="space-y-2 mb-5">
        <div v-for="t in tagihan" :key="t.id" class="flex justify-between bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5">
          <div>
            <p class="font-bold m-0 text-[14px]">{{ labelJenis(t.jenis || t.nama) }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0">{{ t.periode }}</p>
          </div>
          <span class="font-bold">{{ rp(t.sisa ?? t.nominal) }}</span>
        </div>
        <p v-if="!tagihan.length" class="text-[13px] text-[var(--mut)]">Tidak ada tagihan terbuka</p>
      </div>
      <h2 class="text-[15px] font-bold mb-2">Status permintaan transfer</h2>
      <div class="space-y-2">
        <div v-for="p in permintaan" :key="p.id" class="flex justify-between bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5">
          <div>
            <p class="font-bold m-0 text-[14px]">{{ rp(p.nominal_diajukan ?? p.nominal) }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0">{{ p.created_at || p.diajukan_pada }}</p>
          </div>
          <span class="text-[13px] font-semibold">{{ p.status }}</span>
        </div>
        <p v-if="!permintaan.length" class="text-[13px] text-[var(--mut)]">Belum ada permintaan</p>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { portalRingkasan, portalStatusPermintaan } from '@shared/services/keuangan.js'
const loading = ref(true)
const err = ref('')
const total = ref(0)
const tagihan = ref([])
const permintaan = ref([])
function rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID') }
function labelJenis(j) {
  const m = { kas: 'Kas', denda_ronda: 'Denda ronda', khusus: 'Iuran khusus' }
  return m[j] || j || 'Tagihan'
}
const heroLabel = computed(() => {
  if (total.value > 0) return 'Tagihan aktif keluarga Anda'
  if (total.value < 0) return 'Kelebihan bayar'
  return 'Status iuran'
})
const heroNominal = computed(() => {
  if (total.value > 0) return rp(total.value)
  if (total.value < 0) return rp(-total.value)
  return 'Lunas'
})
const heroSub = computed(() => {
  if (total.value > 0) return 'Ada tagihan belum lunas'
  if (total.value < 0) return 'Kelebihan akan dipotong tagihan berikutnya'
  return 'Semua iuran sudah lunas'
})
onMounted(async () => {
  const [iu, pm] = await Promise.all([portalRingkasan(), portalStatusPermintaan()])
  loading.value = false
  if (!iu.ok) { err.value = iu.error || 'Gagal memuat'; return }
  total.value = Number(iu.data?.total ?? iu.data?.total_tagihan ?? 0)
  tagihan.value = iu.data?.tagihan || []
  if (pm.ok) permintaan.value = Array.isArray(pm.data) ? pm.data : (pm.data?.items || [])
})
</script>
