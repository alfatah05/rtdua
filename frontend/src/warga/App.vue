<template>
  <router-view />
</template>

<script setup>
import { useTheme } from '@shared/composables/useTheme.js'
useTheme()
</script>
