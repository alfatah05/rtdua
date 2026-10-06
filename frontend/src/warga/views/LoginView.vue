<template>
  <div class="min-h-screen flex flex-col justify-center px-4 max-w-[400px] mx-auto">
    <div class="text-center mb-8">
      <div class="w-16 h-16 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center mx-auto mb-4">
        <Home :size="28" />
      </div>
      <h1 class="text-2xl font-extrabold">Masuk Warga</h1>
      <p class="text-[var(--mut)] mt-1 text-[13px]">Gunakan username rumah + PIN 6 angka</p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <UiInput
        v-model="username"
        label="Username"
        placeholder="Contoh: AB2-22a"
        autocomplete="username"
        :error="errors.username"
      />
      <UiInput
        v-model="pin"
        label="PIN"
        type="password"
        inputmode="numeric"
        maxlength="6"
        placeholder="6 angka"
        autocomplete="current-password"
        :error="errors.pin"
      />
      <p v-if="formError" class="text-[13px] text-red-600">{{ formError }}</p>
      <UiButton type="submit" block :loading="loading">Masuk</UiButton>
    </form>

    <p class="text-center text-[13px] text-[var(--mut)] mt-8">
      PIN awal: 123456 (wajib diganti)
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Home } from 'lucide-vue-next'
import UiInput from '@shared/components/UiInput.vue'
import UiButton from '@shared/components/UiButton.vue'
import { useAuth } from '@shared/composables/useAuth.js'

const router = useRouter()
const { loginWarga, loading } = useAuth()

const username = ref('')
const pin = ref('')
const errors = ref({})
const formError = ref('')

async function submit() {
  errors.value = {}
  formError.value = ''
  if (!username.value.trim()) errors.value.username = 'Wajib diisi'
  if (!pin.value) errors.value.pin = 'Wajib diisi'
  if (Object.keys(errors.value).length) return

  const res = await loginWarga(username.value, pin.value)
  if (!res.ok) {
    formError.value = res.error
    return
  }
  if (res.mustChange) {
    router.replace('/ganti-pin')
  } else {
    router.replace('/')
  }
}
</script>
