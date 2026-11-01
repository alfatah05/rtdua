<template>
  <div>
    <div v-if="loading" class="animate-pulse" aria-hidden="true">
      <div class="flex flex-col items-center text-center mb-6 pt-2">
        <div class="w-24 h-24 rounded-full bg-[var(--card2)] mb-3" />
        <div class="h-5 rounded-full bg-[var(--card2)] w-40 mb-2" />
        <div class="h-3 rounded-full bg-[var(--card2)] w-16" />
      </div>
      <div class="px-1 space-y-1">
        <div v-for="n in 6" :key="n" class="flex items-center gap-3 px-2 py-3.5">
          <div class="w-6 h-6 rounded bg-[var(--card2)] shrink-0" />
          <div class="flex-1 space-y-2">
            <div class="h-3.5 rounded-full bg-[var(--card2)] w-[45%]" />
            <div class="h-3 rounded-full bg-[var(--card2)] w-[60%]" />
          </div>
          <div class="w-4 h-4 rounded bg-[var(--card2)]" />
        </div>
      </div>
      <div class="w-full h-12 mt-8 rounded-full bg-[var(--card2)]" />
    </div>

    <template v-else>
      <div class="flex flex-col items-center text-center mb-6 pt-2">
        <button
          type="button"
          class="relative w-24 h-24 mb-3 active:scale-95 transition-transform disabled:opacity-70"
          aria-label="Ubah foto profil"
          :disabled="uploadingFoto"
          @click="pickFoto"
        >
          <span
            class="absolute inset-0 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center text-3xl font-bold overflow-hidden"
          >
            <img v-if="fotoUrl" :src="fotoUrl" alt="Foto profil" class="w-full h-full object-cover" />
            <span v-else>{{ inisial }}</span>
          </span>
          <span
            class="absolute -bottom-0.5 -right-0.5 w-8 h-8 rounded-full bg-[var(--g)] text-white grid place-items-center border-2 border-[var(--bg)] z-10 shadow-sm"
          >
            <Camera :size="14" />
          </span>
        </button>
        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFoto" />
        <p class="text-xl font-extrabold m-0">{{ user?.nama || 'Warga' }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5">{{ alamatLabel }}</p>
        <p v-if="uploadingFoto" class="text-[12px] text-[var(--mut)] m-0 mt-1">Mengunggah foto…</p>
      </div>

      <div class="px-1">
        <button
          v-for="m in menus"
          :key="m.label"
          type="button"
          class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:opacity-70 transition-opacity"
          @click="m.action ? m.action() : $router.push(m.to)"
        >
          <span class="w-5 shrink-0 grid place-items-center text-[var(--mut)]">
            <component :is="m.icon" :size="18" stroke-width="1.75" />
          </span>
          <div class="flex-1 min-w-0">
            <p class="font-medium text-[14px] m-0 text-[var(--text)] leading-snug">{{ m.label }}</p>
            <p v-if="m.desc" class="text-[11px] text-[var(--mut)] m-0 mt-0.5 leading-snug">{{ m.desc }}</p>
          </div>
          <span v-if="m.right" class="text-[11px] text-[var(--mut)] shrink-0">{{ m.right }}</span>
          <ChevronRight v-else :size="16" class="text-[var(--mut)] shrink-0 opacity-60" />
        </button>
      </div>

      <button
        type="button"
        class="w-full min-h-[48px] mt-8 rounded-full bg-[var(--card2)] font-bold active:scale-[0.98] transition-transform"
        @click="doLogout"
      >
        Keluar
      </button>

      <p class="text-center text-[12px] text-[var(--mut)] mt-6 mb-2">rtdua · versi 0.1.0</p>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  Camera, ChevronRight, KeyRound, Moon, User, HelpCircle, Download, RefreshCw, Sparkles
} from 'lucide-vue-next'
import { useAuth } from '@shared/composables/useAuth.js'
import { useTheme } from '@shared/composables/useTheme.js'
import { useMotion } from '@shared/composables/useMotion.js'
import { useToast } from '@shared/composables/useToast.js'
import { usePwaInstall } from '@shared/composables/usePwaInstall.js'
import { updateFotoProfil } from '@shared/services/auth.js'
import { uploadGambar, mediaUrl } from '@shared/services/upload.js'
import { waitImages } from '@shared/utils/waitImages.js'

const router = useRouter()
const { user, logout } = useAuth()
const { mode, toggle } = useTheme()
const { animEnabled, toggleAnim } = useMotion()
const { show } = useToast()
const { canInstall, install: installPwa } = usePwaInstall()
const fileInput = ref(null)
const uploadingFoto = ref(false)
const localFoto = ref('')
const loading = ref(true)

const inisial = computed(() => {
  const n = user.value?.nama || 'W'
  return n.split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase()
})
const alamatLabel = computed(() => {
  const a = user.value?.alamat || user.value?.username || '—'
  return String(a).toUpperCase()
})
const fotoUrl = computed(() => {
  if (localFoto.value) return localFoto.value
  const f = user.value?.foto
  return f ? mediaUrl(f) : ''
})

async function hardRefresh() {
  show('Membersihkan cache…')
  try {
    if ('caches' in window) {
      const keys = await caches.keys()
      await Promise.all(keys.map((k) => caches.delete(k)))
    }
    if ('serviceWorker' in navigator) {
      const regs = await navigator.serviceWorker.getRegistrations()
      await Promise.all(regs.map((r) => r.unregister()))
    }
  } catch (_) {}
  const url = new URL(window.location.href)
  url.searchParams.set('_r', String(Date.now()))
  window.location.replace(url.toString())
}

const menus = computed(() => {
  const list = [
    {
      label: 'Data keluarga',
      desc: 'Anggota di rumah Anda',
      icon: User,
      to: '/data-keluarga',
    },
    {
      label: 'Ganti PIN',
      desc: 'Ubah PIN masuk aplikasi',
      icon: KeyRound,
      to: '/ganti-pin',
    },
    {
      label: 'Mode gelap',
      desc: mode.value === 'dark' ? 'Sedang aktif' : 'Nonaktif',
      icon: Moon,
      action: () => toggle(),
      right: mode.value === 'dark' ? 'Aktif' : 'Off',
    },
    {
      label: 'Animasi',
      desc: animEnabled.value ? 'Transisi halaman & tombol aktif' : 'Semua animasi nonaktif',
      icon: Sparkles,
      action: () => {
        toggleAnim()
        show(animEnabled.value ? 'Animasi diaktifkan' : 'Animasi dimatikan')
      },
      right: animEnabled.value ? 'Aktif' : 'Off',
    },
    {
      label: 'Bantuan',
      desc: 'Hubungi pengurus',
      icon: HelpCircle,
      to: '/bantuan',
    },
    {
      label: 'Hard refresh',
      desc: 'Hapus cache & muat ulang aplikasi',
      icon: RefreshCw,
      action: () => hardRefresh(),
    },
  ]
  if (canInstall.value) {
    list.push({
      label: 'Install aplikasi',
      desc: 'Pasang di layar utama HP',
      icon: Download,
      action: async () => {
        const res = await installPwa()
        if (res.message) show(res.message)
      },
    })
  }
  return list
})

async function finishLoading() {
  const url = fotoUrl.value
  if (url) await waitImages([url])
  loading.value = false
}

onMounted(() => {
  if (user.value) finishLoading()
  else {
    const t = setInterval(() => {
      if (user.value) {
        clearInterval(t)
        finishLoading()
      }
    }, 50)
    setTimeout(() => {
      clearInterval(t)
      loading.value = false
    }, 3000)
  }
})

watch(fotoUrl, (u) => {
  if (u && loading.value) waitImages([u]).then(() => { loading.value = false })
})

function pickFoto() {
  if (uploadingFoto.value) return
  fileInput.value?.click()
}

async function onFoto(e) {
  const f = e.target.files?.[0]
  e.target.value = ''
  if (!f) return
  uploadingFoto.value = true
  const up = await uploadGambar(f, 'foto_profil', { maxSide: 800 })
  if (!up.ok) {
    uploadingFoto.value = false
    show(up.error || 'Upload gagal')
    return
  }
  const res = await updateFotoProfil(up.data.path)
  uploadingFoto.value = false
  if (!res.ok) {
    show(res.error || 'Gagal menyimpan foto')
    return
  }
  const updated = res.user || {}
  if (user.value) {
    Object.assign(user.value, updated)
    try {
      const slim = {
        id: user.value.id ?? null,
        nama: user.value.nama ?? null,
        role: user.value.role ?? null,
        foto: user.value.foto ?? null,
        jabatan: user.value.jabatan ?? null,
        alamat: user.value.alamat ?? null,
        keluarga_id: user.value.keluarga_id ?? null,
        warga_id: user.value.warga_id ?? null,
        harus_ganti_kredensial: !!user.value.harus_ganti_kredensial,
        is_developer: !!user.value.is_developer,
      }
      localStorage.setItem('rtdua-auth-warga', JSON.stringify(slim))
    } catch (err) {}
  }
  localFoto.value = updated.foto ? mediaUrl(updated.foto) : mediaUrl(up.data.path)
  show('Foto profil diperbarui')
}

function doLogout() {
  logout('warga')
  router.replace('/login')
}
</script>
