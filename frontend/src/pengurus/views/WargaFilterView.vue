<template>
  <div>
    <AppBackHeader title="Filter" />

    <div class="space-y-5">
      <div>
        <p class="text-[13px] font-bold text-[var(--mut)] mb-2">Blok</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="b in blok"
            :key="b"
            type="button"
            class="min-h-[40px] px-4 rounded-full text-[13px] font-semibold border"
            :class="selectedBlok.includes(b) ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)] text-[var(--text)]'"
            @click="toggleBlok(b)"
          >{{ b }}</button>
        </div>
      </div>

      <div>
        <p class="text-[13px] font-bold text-[var(--mut)] mb-2">Kelengkapan data</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="k in kelengkapan"
            :key="k"
            type="button"
            class="min-h-[40px] px-4 rounded-full text-[13px] font-semibold border"
            :class="selectedLengkap === k ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)] text-[var(--text)]'"
            @click="selectedLengkap = k"
          >{{ k }}</button>
        </div>
      </div>

      <div>
        <p class="text-[13px] font-bold text-[var(--mut)] mb-2">Status keluarga</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="s in status"
            :key="s"
            type="button"
            class="min-h-[40px] px-4 rounded-full text-[13px] font-semibold border"
            :class="selectedStatus === s ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)] text-[var(--text)]'"
            @click="selectedStatus = s"
          >{{ s }}</button>
        </div>
      </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 p-4 bg-[var(--bg)] pb-[calc(16px+env(safe-area-inset-bottom))]">
      <div class="max-w-[1000px] mx-auto flex gap-3">
        <button type="button" class="flex-1 min-h-[48px] rounded-full bg-[var(--card2)] font-bold" @click="reset">Reset</button>
        <button type="button" class="flex-1 min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="$router.back()">Terapkan</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'

const blok = ['AB1', 'AB2', 'AB11', 'AB12']
const kelengkapan = ['Semua', 'Belum lengkap', 'Belum ganti PIN']
const status = ['Aktif', 'Pindah']

const selectedBlok = ref([])
const selectedLengkap = ref('Semua')
const selectedStatus = ref('Aktif')

function toggleBlok(b) {
  const i = selectedBlok.value.indexOf(b)
  if (i >= 0) selectedBlok.value.splice(i, 1)
  else selectedBlok.value.push(b)
}
function reset() {
  selectedBlok.value = []
  selectedLengkap.value = 'Semua'
  selectedStatus.value = 'Aktif'
}
</script>
