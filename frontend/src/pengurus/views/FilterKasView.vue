<template>
  <div>
    <AppBackHeader title="Filter riwayat kas" />
    <div class="space-y-4">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Bulan</label>
        <input v-model="bulan" type="month" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tipe</label>
        <select v-model="tipe" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)]">
          <option value="">Semua</option>
          <option value="masuk">Masuk</option>
          <option value="keluar">Keluar</option>
        </select>
      </div>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="terapkan">Terapkan</button>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const router = useRouter()
const bulan = ref(new Date().toISOString().slice(0, 7))
const tipe = ref('')
function terapkan() {
  router.push({ path: '/keuangan', query: { bulan: bulan.value, tipe: tipe.value || undefined, tab: 'kas' } })
}
</script>
