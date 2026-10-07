<template>
  <div>
    <AppBackHeader title="Jadwal ronda" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 mb-4">
        <p class="text-[13px] text-[var(--mut)] m-0">Malam ini</p>
        <template v-if="malam">
          <p class="font-bold text-[16px] m-0 mt-1">{{ malam.tanggal }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ jam(malam.jam_mulai) }}–{{ jam(malam.jam_selesai) }}</p>
          <p class="text-[13px] m-0 mt-2">Bertugas: {{ (malam.keluarga || []).map(k => k.alamat).join(', ') || '—' }}</p>
        </template>
        <p v-else class="text-[14px] text-[var(--mut)] m-0 mt-1">Tidak ada ronda malam ini</p>
      </div>
      <h2 class="text-[15px] font-bold mb-2">Kalender bulan ini</h2>
      <div class="space-y-1">
        <div v-for="d in kalender" :key="d.tanggal" class="flex justify-between px-2 py-2 border-b border-[var(--line)]">
          <span class="font-semibold">{{ d.tanggal }}</span>
          <span class="text-[13px] text-[var(--mut)]">{{ jam(d.jam_mulai) }}–{{ jam(d.jam_selesai) }} · {{ d.sumber }}</span>
        </div>
        <p v-if="!kalender.length" class="text-[13px] text-[var(--mut)] text-center py-4">Belum ada jadwal</p>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { malamIni, kalenderRonda } from '@shared/services/ronda.js'
const loading = ref(true)
const err = ref('')
const malam = ref(null)
const kalender = ref([])
function jam(j) { return j ? String(j).slice(0, 5) : '—' }
onMounted(async () => {
  const periode = new Date().toISOString().slice(0, 7)
  const [m, k] = await Promise.all([malamIni(), kalenderRonda(periode)])
  loading.value = false
  if (!m.ok && !k.ok) err.value = m.error || k.error || 'Gagal'
  if (m.ok) malam.value = m.data
  if (k.ok) kalender.value = k.data || []
})
</script>
