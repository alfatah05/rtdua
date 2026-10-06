import { ref } from 'vue'

const message = ref('')
const type = ref('info')
const visible = ref(false)
let timer = null

export function useToast() {
  function show(msg, t = 'info', ms = 2500) {
    // Satu toast saja, ganti pesan sebelumnya (tidak bertumpuk)
    if (timer) clearTimeout(timer)
    // Potong teks panjang
    const short = String(msg || '').trim()
    message.value = short.length > 60 ? short.slice(0, 57) + '…' : short
    type.value = t
    visible.value = true
    timer = setTimeout(() => { visible.value = false }, ms)
  }
  function success(msg) { show(msg, 'success') }
  function error(msg) { show(msg, 'error') }
  function hide() { visible.value = false }
  return { message, type, visible, show, success, error, hide }
}
