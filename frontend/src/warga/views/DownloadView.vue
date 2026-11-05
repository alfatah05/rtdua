<template>
  <div
    class="download-page fixed inset-0 z-10 overflow-hidden flex flex-col bg-[var(--bg)] text-[var(--text)]"
  >
    <!-- konten tengah -->
    <div class="flex-1 flex flex-col items-center justify-center px-6 w-full max-w-[400px] mx-auto box-border">
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
          <Home :size="28" />
        </div>
      </div>
      <h1 class="text-2xl font-extrabold m-0 text-center leading-tight">{{ appName }}</h1>
      <p class="text-[var(--mut)] mt-2 mb-0 text-[13px] text-center">
        Pasang aplikasi untuk akses lebih cepat
      </p>
      <p
        v-if="hint"
        class="text-[12px] m-0 mt-3 text-center"
        :class="hintOk ? 'text-[var(--g)]' : 'text-[var(--mut)]'"
      >
        {{ hint }}
      </p>
    </div>

    <!-- tombol bawah full width -->
    <div
      class="shrink-0 w-full max-w-[400px] mx-auto px-5 pb-[max(20px,env(safe-area-inset-bottom))] pt-2 space-y-3 box-border"
    >
      <button
        type="button"
        class="w-full min-h-[48px] rounded-full font-bold text-[15px] bg-[var(--btn)] text-[var(--btn-fg)] active:scale-[0.98] transition disabled:opacity-50"
        :disabled="busy"
        @click="onInstall"
      >
        {{ busy ? 'Memproses…' : isInstalled ? 'Sudah terpasang' : 'Install' }}
      </button>
      <button
        type="button"
        class="w-full min-h-[48px] rounded-full font-bold text-[15px] border-2 border-[var(--g)] text-[var(--g)] bg-transparent active:scale-[0.98] transition"
        @click="onOpenWeb"
      >
        Buka dengan web
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { Home } from 'lucide-vue-next'
import { mediaUrl } from '@shared/services/upload.js'
import { usePwaInstall } from '@shared/composables/usePwaInstall.js'
import { useTheme } from '@shared/composables/useTheme.js'

const router = useRouter()
const { install, isInstalled } = usePwaInstall()
// Init theme dari preferensi HP (light/dark)
useTheme()

const logoUrl = ref('')
const appName = ref('Warga rtdua')
const busy = ref(false)
const hint = ref('')
const hintOk = ref(true)

function lockScroll() {
  document.documentElement.style.overflow = 'hidden'
  document.body.style.overflow = 'hidden'
}

function unlockScroll() {
  document.documentElement.style.overflow = ''
  document.body.style.overflow = ''
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
  // Nama dari meta PWA / title
  try {
    const metaTitle = document.querySelector('meta[name="apple-mobile-web-app-title"]')
    if (metaTitle?.content) appName.value = metaTitle.content
    else if (document.title) appName.value = document.title
  } catch (e) {}

  try {
    const res = await fetch('/api/auth/blok-login', { headers: { Accept: 'application/json' } })
    const json = await res.json()
    if (json?.ok && json.data && !Array.isArray(json.data)) {
      const d = json.data
      setLogoFrom(d)
      if (d.nama_app) appName.value = d.nama_app
      else if (d.nama_rt) appName.value = d.nama_rt
    }
  } catch (e) {
    // logo kosong ok
  }
})

onUnmounted(unlockScroll)

async function onInstall() {
  busy.value = true
  hint.value = ''
  const res = await install()
  busy.value = false
  hintOk.value = !!res.ok
  hint.value = res.message || (res.ok ? 'Berhasil' : '')
}

function onOpenWeb() {
  unlockScroll()
  router.replace('/login')
}
</script>
