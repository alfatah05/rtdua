<template>
  <div class="min-h-screen flex flex-col justify-center px-4 max-w-[400px] mx-auto">
    <div class="text-center mb-8">
      <div class="w-20 h-20 rounded-full bg-[var(--gd)] grid place-items-center mx-auto mb-4 overflow-hidden">
        <img v-if="logoUrl" :src="logoUrl" alt="Logo" class="w-full h-full object-contain p-1.5" @error="logoUrl = ''" />
        <Home v-else :size="28" class="text-[var(--gm)]" />
      </div>
      <h1 class="text-2xl font-extrabold">Masuk Warga</h1>
      <p class="text-[var(--mut)] mt-1 text-[13px]">Pilih blok, isi nomor rumah, lalu PIN</p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <div class="flex gap-3 items-start">
        <div class="flex-1 min-w-0">
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Blok</label>
          <select
            v-model="blokNama"
            class="w-full min-h-[48px] px-4 rounded-full bg-[var(--search)] text-[var(--text)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)] appearance-none"
          >
            <option value="" disabled>Pilih blok</option>
            <option v-for="b in blokList" :key="b.id" :value="b.nama">{{ b.nama }}</option>
          </select>
          <p v-if="errors.blok" class="mt-1.5 text-[13px] text-red-600">{{ errors.blok }}</p>
        </div>
        <div class="flex-1 min-w-0">
          <UiInput
            v-model="nomor"
            label="Nomor"
            placeholder="19 atau 19a"
            autocomplete="off"
            pill
            :error="errors.nomor"
          />
        </div>
      </div>

      <UiInput
        v-model="pin"
        label="PIN"
        type="password"
        inputmode="numeric"
        maxlength="6"
        placeholder="6 angka"
        autocomplete="current-password"
        pill
        :error="errors.pin"
      />
      <p v-if="formError" class="text-[13px] text-red-600">{{ formError }}</p>
      <UiButton type="submit" block :loading="loading">Masuk</UiButton>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Home } from 'lucide-vue-next'
import UiInput from '@shared/components/UiInput.vue'
import UiButton from '@shared/components/UiButton.vue'
import { useAuth } from '@shared/composables/useAuth.js'
import { mediaUrl } from '@shared/services/upload.js'

const router = useRouter()
const { loginWarga, loading } = useAuth()

const blokList = ref([])
const blokNama = ref('')
const nomor = ref('')
const pin = ref('')
const errors = ref({})
const formError = ref('')
const logoUrl = ref('')

onMounted(async () => {
  try {
    const res = await fetch('/api/auth/blok-login', { headers: { Accept: 'application/json' } })
    const json = await res.json()
    if (json?.ok && json.data) {
      const d = json.data
      // format baru: { blok, logo_... } atau array blok lama
      if (Array.isArray(d)) {
        blokList.value = d
      } else {
        blokList.value = Array.isArray(d.blok) ? d.blok : []
        const prefer = d.logo_login === 'desa' ? d.logo_desa : d.logo_rt
        const fallback = d.logo_login === 'desa' ? d.logo_rt : d.logo_desa
        const path = prefer || fallback || ''
        logoUrl.value = path ? mediaUrl(path) : ''
      }
    }
  } catch (e) {
    // biarkan kosong jika API belum siap
  }
})

function buildUsername() {
  const b = String(blokNama.value || '').trim()
  const n = String(nomor.value || '').replace(/\s+/g, '').trim()
  return (b + '-' + n).toLowerCase()
}

async function submit() {
  errors.value = {}
  formError.value = ''
  if (!blokNama.value) errors.value.blok = 'Pilih blok'
  if (!String(nomor.value || '').trim()) errors.value.nomor = 'Wajib diisi'
  if (!pin.value) errors.value.pin = 'Wajib diisi'
  else if (!/^\d{6}$/.test(pin.value)) errors.value.pin = 'PIN 6 angka'
  if (Object.keys(errors.value).length) return

  const username = buildUsername()
  const res = await loginWarga(username, pin.value)
  if (!res.ok) {
    formError.value = res.error || 'Login gagal'
    return
  }
  if (res.mustChange) {
    router.replace('/ganti-pin')
  } else {
    router.replace('/')
  }
}
</script>
