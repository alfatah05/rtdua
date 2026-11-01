<template>
  <div>
    <AppBackHeader title="Reward" />
    <div class="flex gap-2 mb-4">
      <button
        type="button"
        class="flex-1 min-h-[44px] rounded-full bg-[var(--g)] text-white text-[14px] font-bold"
        @click="$router.push('/reward/tambah')"
      >
        + Buat undian
      </button>
    </div>
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!items.length" class="text-[13px] text-[var(--mut)] text-center py-8">Belum ada event undian</p>
    <div v-else class="space-y-2">
      <button
        v-for="e in items"
        :key="e.id"
        type="button"
        class="w-full text-left rounded-[16px] border border-[var(--line)] bg-[var(--card)] px-4 py-3.5 active:bg-[var(--search)] transition"
        @click="$router.push('/reward/' + e.id)"
      >
        <div class="flex items-start justify-between gap-2">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ e.judul }}</p>
          <span
            class="text-[11px] font-bold px-2 py-0.5 rounded-full shrink-0"
            :class="e.status === 'aktif' ? 'bg-[var(--gd)] text-[var(--gm)]' : 'bg-[var(--search)] text-[var(--mut)]'"
          >{{ e.status === 'aktif' ? 'Aktif' : 'Arsip' }}</span>
        </div>
        <p class="text-[12px] text-[var(--mut)] m-0 mt-1">
          {{ e.jumlah_batch || 0 }} putaran · {{ e.jumlah_pemenang || 0 }} pemenang · sisa pool {{ e.pool_sisa ?? 0 }}
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
