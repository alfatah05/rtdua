<template>
  <div class="min-h-screen flex flex-col justify-center px-4 max-w-[400px] mx-auto">
    <div class="text-center mb-8">
      <div class="w-16 h-16 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center mx-auto mb-4">
        <Shield :size="28" />
      </div>
      <h1 class="text-2xl font-extrabold">Masuk Pengurus</h1>
      <p class="text-[var(--mut)] mt-1 text-[13px]">Username + password</p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <UiInput
        v-model="username"
        label="Username"
        placeholder="ketua / pengurus"
        autocomplete="username"
        :error="errors.username"
      />
      <UiInput
        v-model="password"
        label="Password"
        type="password"
        placeholder="Minimal 8 karakter"
        autocomplete="current-password"
        :error="errors.password"
      />
      <p v-if="formError" class="text-[13px] text-red-600">{{ formError }}</p>
      <UiButton type="submit" block :loading="loading">Masuk</UiButton>
    </form>

    <p class="text-center text-[13px] text-[var(--mut)] mt-8">
      Demo: username <b>ketua</b> atau <b>pengurus</b>, password bebas
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Shield } from 'lucide-vue-next'
import UiInput from '@shared/components/UiInput.vue'
import UiButton from '@shared/components/UiButton.vue'
import { useAuth } from '@shared/composables/useAuth.js'

const router = useRouter()
const { loginPengurus, loading } = useAuth()

const username = ref('')
const password = ref('')
const errors = ref({})
const formError = ref('')

async function submit() {
  errors.value = {}
  formError.value = ''
  if (!username.value.trim()) errors.value.username = 'Wajib diisi'
  if (!password.value) errors.value.password = 'Wajib diisi'
  if (Object.keys(errors.value).length) return

  const res = await loginPengurus(username.value, password.value)
  if (!res.ok) {
    formError.value = res.error
    return
  }
  router.replace('/')
}
</script>
