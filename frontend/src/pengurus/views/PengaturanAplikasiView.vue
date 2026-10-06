<template>
  <div>
    <AppBackHeader title="Pengaturan aplikasi" />
    <form class="space-y-4" @submit.prevent="msg = 'Disimpan (dummy)'">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nama aplikasi</label>
        <input v-model="namaApp" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nama RT</label>
        <input v-model="namaRt" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nama perumahan</label>
        <input v-model="perumahan" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Daftar blok</label>
        <div class="flex flex-wrap gap-2 mb-2">
          <span v-for="b in blok" :key="b" class="px-3 py-1.5 rounded-full bg-[var(--card2)] text-[13px] font-semibold">{{ b }}</span>
        </div>
        <button type="button" class="text-[13px] font-semibold text-[var(--g)]">+ Tambah blok</button>
      </div>
      <label class="flex items-center gap-3 min-h-[44px]">
        <input v-model="aksesWarga" type="checkbox" class="w-5 h-5 accent-[var(--g)]" />
        <span class="text-[15px] font-semibold">Akses warga dibuka</span>
      </label>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold">Simpan</button>
      <p v-if="msg" class="text-[13px] text-[var(--g)] text-center">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const namaApp = ref('rtdua')
const namaRt = ref('Rukun Warga 002')
const perumahan = ref('Perum. Pesona Gading Cibitung 2')
const blok = ref(['AB1', 'AB2', 'AB11', 'AB12'])
const aksesWarga = ref(false)
const msg = ref('')
</script>
