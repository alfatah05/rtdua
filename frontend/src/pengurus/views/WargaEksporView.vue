<template>
  <div>
    <AppBackHeader title="Ekspor data warga" />

    <div class="space-y-4 mb-6">
      <div>
        <p class="text-[13px] font-bold text-[var(--mut)] mb-2">Mode</p>
        <div class="flex gap-2">
          <button type="button" class="flex-1 min-h-[44px] rounded-full text-[13px] font-semibold border"
            :class="mode === 'keluarga' ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="mode = 'keluarga'">Per keluarga</button>
          <button type="button" class="flex-1 min-h-[44px] rounded-full text-[13px] font-semibold border"
            :class="mode === 'warga' ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="mode = 'warga'">Per warga</button>
        </div>
      </div>
      <p class="text-[13px] text-[var(--mut)]">
        Kolom default: No, Nama, Blok/No. rumah.
        Tanpa NIK, telepon, dan nominal.
      </p>
    </div>

    <div class="space-y-3">
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="msg = 'PDF dibuat (dummy Stage 5)'">
        Unduh PDF
      </button>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--card2)] font-bold" @click="msg = 'XLSX dibuat (dummy Stage 5)'">
        Unduh XLSX
      </button>
      <button type="button" class="w-full min-h-[48px] rounded-full border border-[var(--line)] font-bold" @click="msg = 'Bagikan (dummy)'">
        Bagikan
      </button>
    </div>
    <p v-if="msg" class="text-[13px] text-[var(--g)] mt-4 text-center">{{ msg }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const mode = ref('keluarga')
const msg = ref('')
</script>
