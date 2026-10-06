<template>
  <div>
    <AppBackHeader title="Ganti PIN" />

    <p v-if="forced" class="text-[13px] text-[var(--mut)] mb-4">
      PIN masih default. Anda wajib mengganti PIN sebelum melanjutkan.
    </p>

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">PIN baru</label>
        <input v-model="pin" type="password" inputmode="numeric" maxlength="6" placeholder="6 angka"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Ulangi PIN baru</label>
        <input v-model="pin2" type="password" inputmode="numeric" maxlength="6" placeholder="Ulangi 6 angka"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <p v-if="error" class="text-[13px] text-red-600">{{ error }}</p>
      <p v-if="ok" class="text-[13px] text-[var(--g)]">{{ ok }}</p>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold">
        Simpan PIN
      </button>
    </form>
  </div>
</template>

<script setup>
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@shared/composables/useAuth.js'

const router = useRouter()
const { user } = useAuth()
const forced = computed(() => !!user.value?.harus_ganti_kredensial)

const pin = ref('')
const pin2 = ref('')
const error = ref('')
const ok = ref('')

function submit() {
  error.value = ''
  ok.value = ''
  if (!/^\d{6}$/.test(pin.value)) {
    error.value = 'PIN harus 6 angka'
    return
  }
  if (pin.value === '123456' || pin.value === '111111' || pin.value === '654321') {
    error.value = 'PIN terlalu mudah, pilih yang lain'
    return
  }
  if (pin.value !== pin2.value) {
    error.value = 'PIN tidak sama'
    return
  }
  if (user.value) user.value.harus_ganti_kredensial = false
  try { localStorage.setItem('rtdua-auth-warga', JSON.stringify(user.value)) } catch (e) {}
  ok.value = 'PIN berhasil diganti'
  setTimeout(() => router.replace('/'), 800)
}
</script>
