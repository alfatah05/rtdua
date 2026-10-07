<template>
  <div>
    <AppBackHeader title="Filter iuran" />
    <div class="space-y-4">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Status</label>
        <select v-model="status" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)]">
          <option value="">Semua</option>
          <option value="lunas">Lunas</option>
          <option value="belum_lunas">Belum lunas</option>
          <option value="menunggak">Menunggak</option>
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
const status = ref('')
function terapkan() {
  router.push({ path: '/keuangan/iuran', query: { status: status.value || undefined } })
}
</script>
