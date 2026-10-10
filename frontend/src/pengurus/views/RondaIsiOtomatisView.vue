<template>
  <div>
    <AppBackHeader title="Isi otomatis" />

    <p class="text-[13px] text-[var(--mut)] m-0 mb-4">
      Hanya mengisi <b>jadwal tetap</b> yang masih kosong. KK aktif dibagi bergiliran (urut blok & nomor rumah).
      Jadwal khusus dan malam yang sudah diisi manual tidak diubah.
    </p>

    <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Keluarga per malam</p>
    <input
      v-model.number="perMalam"
      type="number"
      min="1"
      max="20"
      class="w-full min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none mb-4"
    />

    <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Durasi</p>
    <select v-model="durasi" class="w-full min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none mb-5">
      <option value="minggu">1 minggu (sisa minggu ini)</option>
      <option v-for="n in 12" :key="n" :value="String(n)">{{ n }} bulan</option>
    </select>

    <button
      type="button"
      class="w-full min-h-[44px] rounded-full bg-[var(--g)] text-white font-bold mb-3 disabled:opacity-50 dark:bg-white dark:text-black"
      :disabled="busy"
      @click="jalankan"
    >
      {{ busy ? 'Mengisi…' : 'Jalankan isi otomatis' }}
    </button>

    <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { isiOtomatisRonda } from '@shared/services/ronda.js'

const perMalam = ref(2)
const durasi = ref('1')
const busy = ref(false)
const msg = ref('')
const msgOk = ref(true)

async function jalankan() {
  busy.value = true
  msg.value = ''
  const res = await isiOtomatisRonda({
    keluarga_per_malam: perMalam.value || 2,
    durasi: durasi.value,
  })
  busy.value = false
  msgOk.value = !!res.ok
  if (res.ok) {
    const d = res.data || {}
    const parts = []
    if (d.dibuat) parts.push(`${d.dibuat} slot dibuat`)
    parts.push(`${d.diisi ?? 0} malam diisi`)
    if (d.dilewati) parts.push(`${d.dilewati} dilewati`)
    parts.push(`${d.total_kk ?? 0} KK`)
    msg.value = 'Selesai: ' + parts.join(' · ')
  } else {
    msg.value = res.error || 'Gagal isi otomatis'
  }
}
</script>
