<template>
  <div>
    <AppBackHeader title="Malam ronda" />
    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4">
      <p class="font-bold text-[17px] m-0">Sabtu, 11 Oktober 2026</p>
      <p class="text-[13px] text-[var(--mut)] m-0">21.00 – 24.00 · Sumber: jadwal tetap</p>
    </div>

    <h2 class="text-[15px] font-bold mb-2">Bertugas</h2>
    <div class="space-y-2 mb-5">
      <div v-for="k in keluarga" :key="k.id" class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5">
        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0">{{ k.nama }}</p>
          <p class="text-[13px] m-0" :class="k.status === 'Hadir' ? 'text-[var(--g)]' : 'text-amber-600'">{{ k.status }}{{ k.jam ? ' · ' + k.jam : '' }}</p>
        </div>
        <button v-if="k.status === 'Hadir'" type="button" class="text-[12px] font-semibold text-red-600" @click="msg = 'Absen dibatalkan (dummy)'">Batalkan</button>
        <button v-else type="button" class="text-[12px] font-semibold text-[var(--g)]" @click="msg = 'Absen manual (dummy)'">Absenkan</button>
      </div>
    </div>
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--card2)] font-bold mb-2" @click="$router.push('/ronda/malam/edit')">Ganti keluarga</button>
    <p v-if="msg" class="text-[13px] text-[var(--g)] text-center">{{ msg }}</p>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const msg = ref('')
const keluarga = [
  { id: 1, nama: 'AB2-22 · Budi Santoso', status: 'Hadir', jam: '21.12' },
  { id: 2, nama: 'AB1-05 · Andi Wijaya', status: 'Belum absen', jam: null },
]
</script>
