<template>
  <div>
    <AppBackHeader title="Tarif iuran & denda" />
    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal kas / bulan</label>
        <input v-model="kas" type="text" inputmode="numeric" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Denda ronda / ketidakhadiran</label>
        <input v-model="denda" type="text" inputmode="numeric" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <label class="flex items-center gap-3 min-h-[44px]">
        <input v-model="terapkan" type="checkbox" class="w-5 h-5 accent-[var(--g)]" />
        <span class="text-[15px] font-semibold">Terapkan juga ke bulan ini</span>
      </label>
      <div v-if="terapkan" class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 text-[13px]">
        <p class="font-bold m-0 mb-1">Pratinjau dampak</p>
        <p class="text-[var(--mut)] m-0">42 keluarga terdampak, 5 di antaranya jadi kelebihan bayar</p>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Jam ronda default</label>
        <div class="flex gap-2">
          <input v-model="jamMulai" type="time" class="flex-1 min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
          <input v-model="jamSelesai" type="time" class="flex-1 min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
        </div>
      </div>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold">Simpan</button>
      <p v-if="msg" class="text-[13px] text-[var(--g)] text-center">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const kas = ref('40000')
const denda = ref('10000')
const terapkan = ref(false)
const jamMulai = ref('21:00')
const jamSelesai = ref('24:00')
const msg = ref('')
function onSave() { msg.value = 'Pengaturan disimpan (dummy)' }
</script>
