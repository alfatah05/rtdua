<template>
  <div>
    <AppBackHeader title="Reward" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!items.length" class="text-[13px] text-[var(--mut)] text-center py-8">Belum ada undian</p>
    <div v-else class="space-y-2">
      <button
        v-for="e in items"
        :key="e.id"
        type="button"
        class="w-full text-left rounded-[16px] border border-[var(--line)] bg-[var(--card)] px-4 py-3.5 active:bg-[var(--search)] transition"
        @click="$router.push('/reward/' + e.id)"
      >
        <p class="font-bold text-[15px] m-0 leading-tight">{{ e.judul }}</p>
        <p class="text-[12px] text-[var(--mut)] m-0 mt-1">
          {{ e.jumlah_pemenang || 0 }} pemenang · ketuk untuk detail
        </p>
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { listReward } from '@shared/services/reward.js'

const items = ref([])
const loading = ref(true)
const err = ref('')

onMounted(async () => {
  loading.value = true
  const res = await listReward()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    return
  }
  items.value = res.data?.items || []
})
</script>
