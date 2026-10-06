<template>
  <div>
    <AppBackHeader title="Tolak permintaan" />
    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Alasan penolakan</label>
        <textarea v-model="alasan" rows="4" placeholder="Contoh: Nominal tidak sesuai / bukti tidak jelas"
          class="w-full px-4 py-3 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)] resize-none" />
      </div>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-red-600 text-white font-bold" :disabled="!alasan.trim()">Tolak permintaan</button>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useToast } from '@shared/composables/useToast.js'
const router = useRouter()
const { success } = useToast()
const alasan = ref('')
function onSave() {
  success('Permintaan ditolak')
  setTimeout(() => router.back(), 600)
}
</script>
