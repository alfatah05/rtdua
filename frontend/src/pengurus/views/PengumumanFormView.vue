<template>
  <div>
    <AppBackHeader :title="isEdit ? 'Edit pengumuman' : 'Tambah pengumuman'" />
    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Judul</label>
        <input v-model="judul" type="text" placeholder="Judul pengumuman"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Isi</label>
        <textarea v-model="isi" rows="5" placeholder="Isi pengumuman"
          class="w-full px-4 py-3 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)] resize-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Lampiran (PDF/gambar, opsional)</label>
        <div class="w-full min-h-[80px] rounded-[12px] bg-[var(--search)] grid place-items-center text-[13px] text-[var(--mut)]">Ketuk untuk pilih file</div>
      </div>
      <label class="flex items-center gap-3 min-h-[44px]">
        <input v-model="pin" type="checkbox" class="w-5 h-5 accent-[var(--g)]" />
        <span class="text-[15px] font-semibold">Pin di Beranda warga (maks 2)</span>
      </label>
      <label v-if="isEdit" class="flex items-center gap-3 min-h-[44px]">
        <input v-model="kirimNotif" type="checkbox" class="w-5 h-5 accent-[var(--g)]" />
        <span class="text-[15px] font-semibold">Kirim notifikasi lagi</span>
      </label>
      <p v-if="pinError" class="text-[13px] text-red-600">{{ pinError }}</p>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold">Simpan</button>
      <p v-if="msg" class="text-[13px] text-[var(--g)] text-center">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id && route.params.id !== 'tambah')
const judul = ref(isEdit.value ? 'Kerja bakti membersihkan selokan' : '')
const isi = ref(isEdit.value ? 'Hari Minggu, 12 Oktober pukul 07.00.' : '')
const pin = ref(false)
const kirimNotif = ref(false)
const pinError = ref('')
const msg = ref('')
const pinCount = 2 // dummy: sudah 2 pin aktif
function onSave() {
  pinError.value = ''
  if (pin.value && pinCount >= 2 && !isEdit.value) {
    pinError.value = 'Lepas salah satu pin dulu (maksimal 2)'
    return
  }
  msg.value = 'Pengumuman disimpan (dummy)'
  setTimeout(() => router.back(), 800)
}
</script>
