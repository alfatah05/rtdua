<template>
  <div>
    <AppBackHeader title="Saya sudah transfer" />

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Total transfer</label>
        <input v-model="nominal" type="text" inputmode="numeric" placeholder="Contoh: 50000"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Atas nama pengirim</label>
        <input v-model="nama" type="text" placeholder="Nama di rekening"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Bank pengirim</label>
        <input v-model="bank" type="text" placeholder="Contoh: BCA / BRI / Mandiri"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Bukti transfer</label>
        <div class="w-full min-h-[120px] rounded-[12px] bg-[var(--search)] grid place-items-center text-[var(--mut)] text-[13px]">
          Ketuk untuk pilih foto (dummy)
        </div>
      </div>
      <p v-if="msg" class="text-[13px] text-[var(--g)]">{{ msg }}</p>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold">
        Kirim permintaan
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const nominal = ref('')
const nama = ref('')
const bank = ref('')
const msg = ref('')

function submit() {
  if (!nominal.value || !nama.value || !bank.value) {
    msg.value = 'Lengkapi semua isian'
    return
  }
  msg.value = 'Permintaan terkirim (dummy). Menunggu konfirmasi pengurus.'
  setTimeout(() => router.back(), 1500)
}
</script>
