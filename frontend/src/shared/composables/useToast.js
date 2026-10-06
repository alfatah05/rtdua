import { ref } from 'vue'

const message = ref('')
const type = ref('info') // info | success | error
const visible = ref(false)
let timer = null

export function useToast() {
  function show(msg, t = 'info', ms = 2800) {
    message.value = msg
    type.value = t
    visible.value = true
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => { visible.value = false }, ms)
  }
  function success(msg) { show(msg, 'success') }
  function error(msg) { show(msg, 'error') }
  function hide() { visible.value = false }
  return { message, type, visible, show, success, error, hide }
}
