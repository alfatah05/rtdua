<template>
  <div>
    <AppBackHeader title="Tolak permintaan" />
    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Alasan penolakan</label>
        <textarea v-model="alasan" rows="4" class="w-full px-4 py-3 rounded-[12px] bg-[var(--search)] outline-none resize-none" placeholder="Wajib diisi" />
      </div>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-red-600 text-white font-bold" :disabled="saving">{{ saving ? 'Mengirim…' : 'Tolak permintaan' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { tolakPermintaan } from '@shared/services/keuangan.js'

const route = useRoute()
const router = useRouter()
const alasan = ref('')
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)

async function onSave() {
  if (!alasan.value.trim()) { msgOk.value = false; msg.value = 'Alasan wajib'; return }
  saving.value = true
  const res = await tolakPermintaan(route.params.id, alasan.value.trim())
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Permintaan ditolak' : (res.error || 'Gagal')
  if (res.ok) setTimeout(() => router.replace('/keuangan'), 600)
}
</script>
