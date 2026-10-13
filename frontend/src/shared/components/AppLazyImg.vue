<template>
  <img
    ref="el"
    :src="shown ? src : undefined"
    :alt="alt"
    :class="imgClass"
    :style="imgStyle"
    :width="width"
    :height="height"
    decoding="async"
    @load="onLoad"
    @error="onError"
  />
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  src: { type: String, default: '' },
  alt: { type: String, default: '' },
  imgClass: { type: [String, Object, Array], default: '' },
  imgStyle: { type: [String, Object], default: undefined },
  width: { type: [String, Number], default: undefined },
  height: { type: [String, Number], default: undefined },
  /** Load immediately (above the fold / critical) */
  eager: { type: Boolean, default: false },
  /** Root margin for IntersectionObserver */
  rootMargin: { type: String, default: '120px' },
})

const emit = defineEmits(['load', 'error'])
const el = ref(null)
const shown = ref(false)
let observer = null

function reveal() {
  if (shown.value) return
  if (props.src) shown.value = true
}

function onLoad(e) {
  emit('load', e)
}
function onError(e) {
  emit('error', e)
}

onMounted(() => {
  if (props.eager || !props.src) {
    reveal()
    return
  }
  if (typeof IntersectionObserver === 'undefined') {
    reveal()
    return
  }
  observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          reveal()
          observer?.disconnect()
          observer = null
          break
        }
      }
    },
    { rootMargin: props.rootMargin, threshold: 0.01 }
  )
  if (el.value) observer.observe(el.value)
})

onBeforeUnmount(() => {
  observer?.disconnect()
  observer = null
})

watch(
  () => props.src,
  (v) => {
    if (!v) {
      shown.value = false
      return
    }
    if (props.eager || shown.value) shown.value = true
  }
)
</script>
