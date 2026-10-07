<template>
  <div>
    <AppBackHeader title="Buat album" />
    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Judul album</label>
        <input v-model="judul" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal kegiatan</label>
        <input v-model="tanggal" type="date" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <p class="text-[13px] text-[var(--mut)]">Upload foto per item menyusul (setelah album dibuat).</p>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Buat album' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { buatAlbum } from '@shared/services/konten.js'

const router = useRouter()
const judul = ref('')
const tanggal = ref('')
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)

async function onSave() {
  if (!judul.value.trim()) { msgOk.value = false; msg.value = 'Judul wajib'; return }
  saving.value = true
  const res = await buatAlbum({ judul: judul.value.trim(), tanggal_kegiatan: tanggal.value || null })
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Album dibuat' : (res.error || 'Gagal')
  if (res.ok) setTimeout(() => router.replace('/konten/galeri'), 500)
}
</script>
