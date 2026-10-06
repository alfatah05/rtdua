<template>
  <router-view />
  <AppToast />
</template>
<script setup>
import { useTheme } from '@shared/composables/useTheme.js'
import AppToast from '@shared/components/AppToast.vue'
useTheme()
</script>
