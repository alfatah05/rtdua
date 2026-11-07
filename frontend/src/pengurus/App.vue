<template>
  <div class="app-shell">
    <router-view />
    <AppToast />
    <div id="system-nav-underlay" aria-hidden="true" />
  </div>
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
  forceLogout('pengurus')
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
.app-shell {
  min-height: 100%;
  min-height: 100dvh;
  min-height: 100svh;
  background: var(--bg);
  background-color: var(--bg);
  position: relative;
  z-index: 1;
}
</style>
