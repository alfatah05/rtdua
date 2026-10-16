<template>
  <div class="h-dvh max-h-dvh overflow-hidden flex flex-col justify-center px-5 max-w-[400px] mx-auto w-full py-6 box-border">
    <div class="text-center mb-6 shrink-0">
      <!-- Logo: tanpa crop lingkaran, tanpa background -->
      <div class="mx-auto mb-4 flex items-center justify-center h-28 w-28">
        <img
          v-if="logoUrl"
          :src="logoUrl"
          alt="Logo"
          class="max-h-28 max-w-28 w-auto h-auto object-contain"
          @error="logoUrl = ''"
        />
        <div
          v-else
          class="w-16 h-16 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center"
        >
          <Shield :size="28" />
        </div>
      </div>
      <h1 class="text-2xl font-extrabold m-0">Masuk Pengurus</h1>
      <p class="text-[var(--mut)] mt-1 mb-0 text-[13px]">Username + password</p>
    </div>

    <form class="space-y-3.5 shrink-0" @submit.prevent="submit">
      <UiInput
        v-model="username"
        label="Username"
        placeholder="ketua / pengurus"
        autocomplete="username"
        pill
        :error="errors.username"
      />
      <UiInput
        v-model="password"
        label="Password"
        type="password"
        placeholder="Minimal 8 karakter"
        autocomplete="current-password"
        pill
        :error="errors.password"
      />
      <p v-if="formError" class="text-[13px] text-red-600 m-0">{{ formError }}</p>
      <UiButton type="submit" block :loading="loading">Masuk</UiButton>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Shield } from 'lucide-vue-next'
import UiInput from '@shared/components/UiInput.vue'
import UiButton from '@shared/components/UiButton.vue'
import { useAuth } from '@shared/composables/useAuth.js'
import { mediaUrl } from '@shared/services/upload.js'

const router = useRouter()
const { loginPengurus, loading } = useAuth()

const username = ref('')
const password = ref('')
const errors = ref({})
const formError = ref('')
const logoUrl = ref('')

onMounted(async () => {
  try {
    const res = await fetch('/api/auth/blok-login', { headers: { Accept: 'application/json' } })
    const json = await res.json()
    if (!json?.ok || !json.data || Array.isArray(json.data)) return
    const d = json.data
    const path =
      d.logo ||
      (d.logo_login === 'desa' ? d.logo_desa || d.logo_rt : d.logo_rt || d.logo_desa) ||
      ''
    if (path) logoUrl.value = mediaUrl(path)
  } catch (e) {
    // ignore
  }
})

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
