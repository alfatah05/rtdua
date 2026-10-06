<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="classes"
    @click="$emit('click', $event)"
  >
    <span v-if="loading" class="opacity-70">Memproses...</span>
    <slot v-else />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: { type: String, default: 'primary' }, // primary | secondary | danger | ghost
  size: { type: String, default: 'md' }, // sm | md | lg
  type: { type: String, default: 'button' },
  disabled: Boolean,
  loading: Boolean,
  block: Boolean,
})

defineEmits(['click'])

const classes = computed(() => {
  const base = 'inline-flex items-center justify-center gap-2 font-bold rounded-full transition active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none'
  const sizes = {
    sm: 'min-h-[36px] px-4 text-sm',
    md: 'min-h-[44px] px-5 text-[15px]',
    lg: 'min-h-[48px] px-6 text-base',
  }
  const variants = {
    primary: 'bg-[var(--g)] text-white',
    secondary: 'bg-[var(--card2)] text-[var(--text)]',
    danger: 'bg-red-500/15 text-red-600 dark:text-red-400',
    ghost: 'bg-transparent text-[var(--text)]',
  }
  return [
    base,
    sizes[props.size] || sizes.md,
    variants[props.variant] || variants.primary,
    props.block ? 'w-full' : '',
  ].join(' ')
})
</script>
