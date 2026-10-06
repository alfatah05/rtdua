<template>
  <component
    :is="tag"
    :class="[
      'bg-[var(--card)] rounded-[20px] p-[18px]',
      border ? 'border border-[var(--line)]' : '',
      shadow ? 'shadow-[var(--sh)]' : '',
      clickable ? 'clickable cursor-pointer' : '',
    ]"
    @click="clickable ? $emit('click', $event) : null"
  >
    <slot />
  </component>
</template>
<script setup>
defineProps({
  tag: { type: String, default: 'div' },
  border: { type: Boolean, default: true },
  shadow: { type: Boolean, default: false },
  clickable: Boolean,
})
defineEmits(['click'])
</script>
