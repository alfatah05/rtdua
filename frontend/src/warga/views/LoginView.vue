<template>
  <!-- fixed + overflow body lock: tidak bisa scroll -->
  <div class="login-page fixed inset-0 z-10 overflow-hidden overscroll-none flex flex-col justify-center px-5 w-full max-w-[400px] left-1/2 -translate-x-1/2 box-border">
    <div class="text-center mb-5 shrink-0">
      <div class="mx-auto mb-3 flex items-center justify-center h-28 w-28">
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
          <Home :size="28" />
        </div>
      </div>
      <h1 class="text-2xl font-extrabold m-0">Masuk Warga</h1>
      <p class="text-[var(--mut)] mt-1 mb-0 text-[13px]">Pilih blok, isi nomor rumah, lalu PIN</p>
    </div>

    <form class="space-y-3.5 shrink-0" @submit.prevent="submit">
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
          <p v-if="errors.blok" class="mt-1.5 text-[13px] text-red-600 m-0">{{ errors.blok }}</p>
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
      <p v-if="formError" class="text-[13px] text-red-600 m-0">{{ formError }}</p>
      <UiButton type="submit" block :loading="loading">Masuk</UiButton>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
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

function lockScroll() {
  document.documentElement.style.overflow = 'hidden'
  document.documentElement.style.overscrollBehavior = 'none'
  document.body.style.overflow = 'hidden'
  document.body.style.overscrollBehavior = 'none'
  document.body.style.position = 'fixed'
  document.body.style.width = '100%'
  document.body.style.top = `-${window.scrollY}px`
}

function unlockScroll() {
  const top = document.body.style.top
  document.documentElement.style.overflow = ''
  document.documentElement.style.overscrollBehavior = ''
  document.body.style.overflow = ''
  document.body.style.overscrollBehavior = ''
  document.body.style.position = ''
  document.body.style.width = ''
  document.body.style.top = ''
  if (top) {
    const y = Math.abs(parseInt(top, 10) || 0)
    window.scrollTo(0, y)
  }
}

function setLogoFrom(d) {
  const path =
    d.logo ||
    (d.logo_login === 'desa'
      ? d.logo_desa || d.logo_rt
      : d.logo_rt || d.logo_desa) ||
    ''
  logoUrl.value = path ? mediaUrl(path) : ''
}

onMounted(async () => {
  lockScroll()
  try {
    const res = await fetch('/api/auth/blok-login', { headers: { Accept: 'application/json' } })
    const json = await res.json()
    if (!json?.ok || !json.data) return
    const d = json.data
    if (Array.isArray(d)) {
      blokList.value = d
      return
    }
    blokList.value = Array.isArray(d.blok) ? d.blok : []
    setLogoFrom(d)
  } catch (e) {
    // biarkan kosong
  }
})

onUnmounted(unlockScroll)

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
  unlockScroll()
  if (res.mustChange) {
    router.replace('/ganti-pin')
  } else {
    router.replace('/')
  }
}
</script>
