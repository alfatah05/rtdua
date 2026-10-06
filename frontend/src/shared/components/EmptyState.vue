<template>
  <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-8 text-center">
    <p class="font-bold text-[15px] m-0">{{ title }}</p>
    <p v-if="desc" class="text-[13px] text-[var(--mut)] m-0 mt-1">{{ desc }}</p>
    <button
      v-if="actionLabel"
      type="button"
      class="mt-4 min-h-[44px] px-5 rounded-full bg-[var(--g)] text-white font-bold text-[14px]"
      @click="$emit('action')"
    >{{ actionLabel }}</button>
  </div>
</template>
<script setup>
defineProps({
  title: { type: String, default: 'Belum ada data' },
  desc: String,
  actionLabel: String,
})
defineEmits(['action'])
</script>
