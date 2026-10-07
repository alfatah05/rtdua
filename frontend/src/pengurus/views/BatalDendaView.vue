<template>
  <div>
    <AppBackHeader title="Batalkan denda" />
    <form class="space-y-4" @submit.prevent="onSave">
      <p class="text-[13px] text-[var(--mut)]">Tagihan #{{ $route.params.id }}</p>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Alasan</label>
        <textarea v-model="alasan" rows="3" class="w-full px-4 py-3 rounded-[12px] bg-[var(--search)] outline-none resize-none" />
      </div>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? '…' : 'Batalkan denda' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { api } from '@shared/api/http.js'

const route = useRoute()
const router = useRouter()
const alasan = ref('')
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)

async function onSave() {
  if (!alasan.value.trim()) { msgOk.value = false; msg.value = 'Alasan wajib'; return }
  saving.value = true
  try {
    await api('/keuangan/denda/' + route.params.id + '/batal', { method: 'POST', body: { alasan: alasan.value.trim() } })
    msgOk.value = true
    msg.value = 'Denda dibatalkan'
    setTimeout(() => router.back(), 600)
  } catch (e) {
    msgOk.value = false
    msg.value = e.message || 'Gagal'
  }
  saving.value = false
}
</script>
