<template>
  <!-- Skeleton generik halaman child — tanpa position:fixed -->
  <div class="page-child-skeleton" aria-hidden="true">
    <!-- Bar header (setara AppBackHeader) — ikut konten skeleton -->
    <div
      class="flex items-center gap-2 -mx-4 mb-3"
      :style="{
        paddingTop: 'max(8px, env(safe-area-inset-top, 0px))',
        paddingBottom: '10px',
        paddingLeft: '8px',
        paddingRight: '16px',
      }"
    >
      <div class="w-10 h-10 rounded-full bg-[var(--card2)] shrink-0 skel-pulse" />
      <div class="h-5 rounded-full bg-[var(--card2)] w-[42%] skel-pulse" />
      <div class="ml-auto w-10 h-10 rounded-full bg-[var(--card2)] shrink-0 skel-pulse" />
    </div>

    <!-- Hero / card utama -->
    <div class="h-32 rounded-[20px] bg-[var(--card2)] mb-4 skel-pulse" />

    <!-- Baris chip / filter -->
    <div class="flex gap-2 mb-4">
      <div class="h-8 rounded-full bg-[var(--card2)] w-20 skel-pulse" />
      <div class="h-8 rounded-full bg-[var(--card2)] w-24 skel-pulse" />
      <div class="h-8 rounded-full bg-[var(--card2)] w-16 skel-pulse" />
    </div>

    <!-- List rows -->
    <div class="space-y-3">
      <div
        v-for="n in 6"
        :key="n"
        class="flex items-center gap-3 py-1"
      >
        <div class="w-11 h-11 rounded-full bg-[var(--card2)] shrink-0 skel-pulse" />
        <div class="flex-1 space-y-2 min-w-0">
          <div class="h-3.5 rounded-full bg-[var(--card2)] skel-pulse" :style="{ width: n % 2 ? '70%' : '55%' }" />
          <div class="h-3 rounded-full bg-[var(--card2)] skel-pulse" :style="{ width: n % 2 ? '40%' : '48%' }" />
        </div>
        <div class="h-3.5 rounded-full bg-[var(--card2)] w-12 shrink-0 skel-pulse" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.skel-pulse {
  animation: skel-pulse 1.1s ease-in-out infinite;
}
@keyframes skel-pulse {
  0%, 100% { opacity: 0.55; }
  50% { opacity: 1; }
}
</style>
