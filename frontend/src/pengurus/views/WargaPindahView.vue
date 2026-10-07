<template>
  <div>
    <AppBackHeader title="Pindah keluarga" />
    <p class="text-[13px] text-[var(--mut)] mb-4">
      Keluarga akan ditandai pindah, akun dinonaktifkan, anggota berstatus keluar. Tidak bisa dibatalkan dari sini.
    </p>
    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal pindah</label>
        <input v-model="tanggal" type="date" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Catatan</label>
        <textarea v-model="catatan" rows="3" class="w-full px-4 py-3 rounded-[12px] bg-[var(--search)] outline-none resize-none" />
      </div>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-red-600 text-white font-bold" :disabled="saving">{{ saving ? '…' : 'Tandai pindah' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { pindahKeluarga } from '@shared/services/warga.js'
const route = useRoute()
const router = useRouter()
const tanggal = ref(new Date().toISOString().slice(0, 10))
const catatan = ref('')
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)
async function onSave() {
  if (!confirm('Yakin tandai keluarga ini pindah?')) return
  saving.value = true
  const res = await pindahKeluarga(route.params.id, { tanggal: tanggal.value, catatan: catatan.value })
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Keluarga ditandai pindah' : (res.error || 'Gagal')
  if (res.ok) setTimeout(() => router.replace('/warga'), 600)
}
</script>
