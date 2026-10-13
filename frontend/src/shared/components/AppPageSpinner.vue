<template>
  <div class="flex justify-center items-center py-16" role="status" aria-label="Memuat">
    <div class="app-page-spinner" />
  </div>
</template>

<style scoped>
.app-page-spinner {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 3px solid color-mix(in srgb, var(--mut) 28%, transparent);
  border-top-color: var(--mut);
  animation: app-spin 0.65s linear infinite;
}
@keyframes app-spin {
  to { transform: rotate(360deg); }
}
</style>
