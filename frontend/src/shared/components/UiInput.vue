<template>
  <div class="w-full">
    <label v-if="label" class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">{{ label }}</label>
    <input
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :inputmode="inputmode"
      :maxlength="maxlength"
      :autocomplete="autocomplete"
      :class="inputClass"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" class="mt-1.5 text-[13px] text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: String,
  type: { type: String, default: 'text' },
  placeholder: String,
  error: String,
  disabled: Boolean,
  inputmode: String,
  maxlength: [String, Number],
  autocomplete: String,
  /** true = bentuk kapsul (pill), untuk halaman login */
  pill: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])

const inputClass = computed(() => {
  const radius = props.pill ? 'rounded-full' : 'rounded-[12px]'
  return `w-full min-h-[48px] px-4 ${radius} bg-[var(--search)] text-[var(--text)] placeholder:text-[var(--mut)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)] disabled:opacity-50`
})
</script>
