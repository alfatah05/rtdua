<template>
  <div>
    <AppBackHeader title="Ganti PIN" />

    <p v-if="forced" class="text-[13px] text-[var(--mut)] mb-4">
      PIN masih default. Anda wajib mengganti PIN sebelum melanjutkan.
    </p>

    <form class="space-y-4" @submit.prevent="submit">
      <UiInput
        v-model="pin"
        label="PIN baru"
        type="password"
        inputmode="numeric"
        maxlength="6"
        placeholder="6 angka"
        autocomplete="new-password"
        :error="errors.pin"
      />
      <UiInput
        v-model="pin2"
        label="Ulangi PIN baru"
        type="password"
        inputmode="numeric"
        maxlength="6"
        placeholder="Ulangi 6 angka"
        autocomplete="new-password"
        :error="errors.pin2"
      />
      <p v-if="error" class="text-[13px] text-red-600">{{ error }}</p>
      <p v-if="okMsg" class="text-[13px] text-[var(--g)]">{{ okMsg }}</p>
      <UiButton type="submit" block :loading="loading">Simpan PIN</UiButton>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import UiInput from '@shared/components/UiInput.vue'
import UiButton from '@shared/components/UiButton.vue'
import { useAuth } from '@shared/composables/useAuth.js'
import { changeCredential } from '@shared/services/auth.js'

const router = useRouter()
const { user } = useAuth()
const forced = computed(() => !!user.value?.harus_ganti_kredensial)

const pin = ref('')
const pin2 = ref('')
const errors = ref({})
const error = ref('')
const okMsg = ref('')
const loading = ref(false)

async function submit() {
  errors.value = {}
  error.value = ''
  okMsg.value = ''

  if (!/^\d{6}$/.test(pin.value)) {
    errors.value.pin = 'PIN harus 6 angka'
    return
  }
  if (pin.value === '123456' || pin.value === '111111' || pin.value === '654321') {
    errors.value.pin = 'PIN terlalu mudah, pilih yang lain'
    return
  }
  if (pin.value !== pin2.value) {
    errors.value.pin2 = 'PIN tidak sama'
    return
  }

  loading.value = true
  const res = await changeCredential({ new_pin: pin.value })
  loading.value = false

  if (!res.ok) {
    error.value = res.error || 'Gagal menyimpan PIN'
    return
  }

  if (user.value) {
    user.value.harus_ganti_kredensial = false
    try {
      localStorage.setItem('rtdua-auth-warga', JSON.stringify(user.value))
    } catch (e) {}
  }

  okMsg.value = 'PIN berhasil diganti'
  setTimeout(() => router.replace('/'), 800)
}
</script>
