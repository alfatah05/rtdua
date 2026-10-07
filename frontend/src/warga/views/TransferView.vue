<template>
  <div>
    <AppBackHeader title="Saya transfer" />
    <form class="space-y-4" @submit.prevent="onSubmit">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal</label>
        <input v-model="nominal" type="text" inputmode="numeric" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" placeholder="0" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Keterangan (opsional)</label>
        <input v-model="ket" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <p class="text-[13px] text-[var(--mut)]">Bukti foto menyusul. Pengurus akan konfirmasi nominal yang diterima.</p>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? 'Mengirim…' : 'Kirim permintaan' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalAjukanTransfer } from '@shared/services/keuangan.js'
const nominal = ref('')
const ket = ref('')
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)
async function onSubmit() {
  const n = parseInt(String(nominal.value).replace(/\D/g, ''), 10) || 0
  if (n < 1) { msgOk.value = false; msg.value = 'Nominal wajib'; return }
  saving.value = true
  const res = await portalAjukanTransfer({ nominal: n, keterangan: ket.value })
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Permintaan terkirim. Menunggu konfirmasi pengurus.' : (res.error || 'Gagal')
  if (res.ok) { nominal.value = ''; ket.value = '' }
}
</script>
