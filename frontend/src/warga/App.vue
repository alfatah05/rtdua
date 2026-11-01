<template>
  <router-view v-slot="{ Component, route }">
    <Transition :name="appTransitionName" mode="out-in">
      <component :is="Component" :key="route.matched[0]?.path || route.path" />
    </Transition>
  </router-view>
  <AppToast />
  <AppFullscreenToggle />
</template>
<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useTheme } from '@shared/composables/useTheme.js'
import { useMotion } from '@shared/composables/useMotion.js'
import { useAuth } from '@shared/composables/useAuth.js'
import AppToast from '@shared/components/AppToast.vue'
import AppFullscreenToggle from '@shared/components/AppFullscreenToggle.vue'

useTheme()
const { animEnabled } = useMotion()
const appTransitionName = computed(() => (animEnabled.value ? 'app-fade' : ''))

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
  overscroll-behavior: none;
  overscroll-behavior-y: none;
}

html,
body,
#app,
#app * {
  -webkit-user-select: none;
  user-select: none;
  -webkit-touch-callout: none;
}

#app input,
#app textarea,
#app [contenteditable="true"] {
  -webkit-user-select: text;
  user-select: text;
  -webkit-touch-callout: default;
}

html,
body,
#app {
  scrollbar-width: none;
  -ms-overflow-style: none;
}
html::-webkit-scrollbar,
body::-webkit-scrollbar,
#app::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}
#app,
#app * {
  scrollbar-width: none;
  -ms-overflow-style: none;
}
#app *::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}

.app-fade-enter-active,
.app-fade-leave-active {
  transition: opacity 0.2s ease;
}
.app-fade-enter-from,
.app-fade-leave-to {
  opacity: 0;
}
@media (prefers-reduced-motion: reduce) {
  .app-fade-enter-active,
  .app-fade-leave-active {
    transition: none !important;
  }
}
</style>
