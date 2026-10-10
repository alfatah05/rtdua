<template>
  <div>
    <AppBackHeader title="Hapus jadwal" />

    <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] px-4 py-4 mb-4 shadow-[var(--sh)]">
      <p class="text-[14px] font-bold m-0 mb-2">Hapus massal (ke depan)</p>
      <p class="text-[13px] text-[var(--mut)] m-0">
        Hanya mempengaruhi jadwal <b class="text-[var(--tx)]">tetap</b> dari hari ini ke depan.
        Malam yang sudah ada absen tidak diubah. Jadwal khusus tidak ikut.
      </p>
    </div>

    <button
      type="button"
      class="w-full min-h-[44px] rounded-full bg-[var(--card2)] text-[14px] font-bold mb-3 disabled:opacity-50"
      :disabled="busy"
      @click="jalankan('penugasan')"
    >
      {{ busy === 'penugasan' ? 'Menghapus…' : 'Kosongkan penugasan KK' }}
    </button>
    <p class="text-[12px] text-[var(--mut)] m-0 mb-4 -mt-1 px-1">
      Slot malam tetap ada, hanya daftar keluarga yang dikosongkan. Cocok sebelum isi otomatis ulang.
    </p>

    <button
      type="button"
      class="w-full min-h-[44px] rounded-full bg-red-600 text-white text-[14px] font-bold mb-3 disabled:opacity-50"
      :disabled="busy"
      @click="jalankan('slot')"
    >
      {{ busy === 'slot' ? 'Menghapus…' : 'Hapus slot jadwal tetap' }}
    </button>
    <p class="text-[12px] text-[var(--mut)] m-0 mb-4 -mt-1 px-1">
      Menghapus malam tetap + penugasannya. Setelah ini perlu Buat jadwal lagi di Jadwal tetap.
    </p>

    <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { hapusJadwalRonda } from '@shared/services/ronda.js'

const busy = ref('')
const msg = ref('')
const msgOk = ref(true)

async function jalankan(mode) {
  const label = mode === 'slot' ? 'Hapus SEMUA slot tetap ke depan?' : 'Kosongkan penugasan KK di slot tetap ke depan?'
  if (!confirm(label)) return
  busy.value = mode
  msg.value = ''
  const res = await hapusJadwalRonda({ mode })
  busy.value = ''
  msgOk.value = !!res.ok
  if (res.ok) {
    const d = res.data || {}
    const parts = []
    if (d.penugasan_dihapus) parts.push(`${d.penugasan_dihapus} penugasan dibersihkan`)
    if (d.slot_dihapus) parts.push(`${d.slot_dihapus} slot dihapus`)
    if (d.dilewati_ada_absen) parts.push(`${d.dilewati_ada_absen} dilewati (ada absen)`)
    msg.value = parts.length ? parts.join(' · ') : 'Tidak ada yang dihapus'
  } else {
    msg.value = res.error || 'Gagal menghapus'
  }
}
</script>
