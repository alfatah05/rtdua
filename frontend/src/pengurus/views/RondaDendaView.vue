<template>
  <div>
    <AppBackHeader title="Denda ronda" />

    <p class="text-[13px] text-[var(--mut)] m-0 mb-4">
      Terbitkan denda untuk keluarga yang bertugas di bulan lalu tetapi tidak absen.
      Setelah diterbitkan, absensi bulan tersebut terkunci.
    </p>

    <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Periode ronda (bulan lalu)</p>
    <input
      v-model="periodeLalu"
      type="month"
      class="w-full min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none mb-4"
    />

    <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Periode tagihan</p>
    <input
      v-model="periodeTagihan"
      type="month"
      class="w-full min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none mb-5"
    />

    <button
      type="button"
      class="w-full min-h-[44px] rounded-full bg-[var(--g)] text-white font-bold mb-3 disabled:opacity-50 dark:bg-white dark:text-black"
      :disabled="busy"
      @click="jalankan"
    >
      {{ busy ? 'Memproses…' : 'Terbitkan denda' }}
    </button>

    <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { terbitkanDendaRonda } from '@shared/services/ronda.js'

function ymOffset(monthsBack) {
  const d = new Date()
  d.setDate(1)
  d.setMonth(d.getMonth() - monthsBack)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

const periodeLalu = ref(ymOffset(1))
const periodeTagihan = ref(ymOffset(0))
const busy = ref(false)
const msg = ref('')
const msgOk = ref(true)

async function jalankan() {
  busy.value = true
  msg.value = ''
  const res = await terbitkanDendaRonda({
    periode_lalu: periodeLalu.value,
    periode_tagihan: periodeTagihan.value,
  })
  busy.value = false
  msgOk.value = !!res.ok
  if (res.ok) {
    const d = res.data || {}
    msg.value = `Denda terbit: ${d.denda ?? 0} keluarga · total Rp ${(d.total_nominal ?? 0).toLocaleString('id-ID')} (Rp ${(d.nominal_satuan ?? 0).toLocaleString('id-ID')}/kali)`
  } else {
    msg.value = res.error || 'Gagal terbitkan denda'
  }
}
</script>
