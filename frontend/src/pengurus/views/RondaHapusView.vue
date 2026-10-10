<template>
  <div>
    <AppBackHeader title="Hapus jadwal" />

    <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] px-4 py-4 mb-4 shadow-[var(--sh)]">
      <p class="text-[14px] font-bold m-0 mb-2">Hapus massal — seluruh jadwal</p>
      <p class="text-[13px] text-[var(--mut)] m-0">
        Menghapus <b class="text-[var(--tx)]">semua</b> jadwal tetap dan khusus: lampau, hari ini, ke depan —
        termasuk yang sudah absen.
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
      Slot malam tetap ada, hanya daftar keluarga yang dikosongkan (semua tanggal, tetap + khusus).
    </p>

    <button
      type="button"
      class="w-full min-h-[44px] rounded-full bg-red-600 text-white text-[14px] font-bold mb-3 disabled:opacity-50"
      :disabled="busy"
      @click="jalankan('slot')"
    >
      {{ busy === 'slot' ? 'Menghapus…' : 'Hapus semua slot + absen + khusus' }}
    </button>
    <p class="text-[12px] text-[var(--mut)] m-0 mb-4 -mt-1 px-1">
      Menghapus seluruh malam (tetap & khusus), penugasan, absen, dan definisi jadwal khusus.
      Setelah ini perlu Buat jadwal lagi.
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
  const label =
    mode === 'slot'
      ? 'Hapus SEMUA jadwal (tetap + khusus, termasuk lampau & absen)?'
      : 'Kosongkan penugasan KK di SEMUA malam (tetap + khusus)?'
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
    if (d.absen_dihapus) parts.push(`${d.absen_dihapus} absen dihapus`)
    if (d.khusus_dihapus) parts.push(`${d.khusus_dihapus} jadwal khusus dihapus`)
    msg.value = parts.length ? parts.join(' · ') : 'Tidak ada yang dihapus'
  } else {
    msg.value = res.error || 'Gagal menghapus'
  }
}
</script>
