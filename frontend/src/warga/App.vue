<template>
  <router-view />
  <AppToast />
</template>
<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useTheme } from '@shared/composables/useTheme.js'
import { useAuth } from '@shared/composables/useAuth.js'
import AppToast from '@shared/components/AppToast.vue'

useTheme()

const router = useRouter()
const route = useRoute()
const { forceLogout } = useAuth()

function onUnauthorized() {
  forceLogout('warga')
  if (route.name !== 'login') {
    router.replace({ name: 'login', query: { reason: 'session' } })
  }
}

onMounted(() => {
  window.addEventListener('rtdua:unauthorized', onUnauthorized)
})
onUnmounted(() => {
  window.removeEventListener('rtdua:unauthorized', onUnauthorized)
})
</script>

<style>
/* Aplikasi warga: cegah zoom & seleksi teks di semua halaman */
html,
body,
#app {
  -webkit-text-size-adjust: 100%;
  text-size-adjust: 100%;
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
}

html,
body,
#app,
#app * {
  -webkit-user-select: none;
  user-select: none;
  -webkit-touch-callout: none;
}

/* Input form tetap bisa diedit & diseleksi */
#app input,
#app textarea,
#app [contenteditable="true"] {
  -webkit-user-select: text;
  user-select: text;
  -webkit-touch-callout: default;
}
</style>
