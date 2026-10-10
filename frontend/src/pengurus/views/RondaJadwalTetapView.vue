<template>
  <div>
    <AppBackHeader title="Jadwal tetap" />

    <p class="text-[13px] text-[var(--mut)] mb-4">
      Pilih hari ronda (tombol bulat), atur periode, lalu isi otomatis supaya semua KK aktif dibagi merata ke malam-malam itu.
    </p>

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Hari ronda</p>
      <div class="flex justify-between gap-1.5 mb-2">
        <button
          v-for="(label, h) in hariPendek"
          :key="h"
          type="button"
          class="w-11 h-11 rounded-full text-[12px] font-bold grid place-items-center shrink-0 transition-transform active:scale-95 border"
          :class="selected[h]
            ? 'bg-[var(--g)] text-white border-[var(--g)]'
            : 'bg-[var(--card)] text-[var(--mut)] border-[var(--line)]'"
          :disabled="toggling"
          @click="toggleHari(h)"
        >
          {{ label }}
        </button>
      </div>
      <p class="text-[12px] text-[var(--mut)] mb-5">
        {{ hariAktifLabel || 'Belum ada hari dipilih' }}
      </p>

      <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Jam default</p>
      <div class="flex gap-2 mb-5">
        <input v-model="jamMulai" type="time" class="flex-1 min-h-[44px] px-3 rounded-[12px] bg-[var(--search)]" />
        <input v-model="jamSelesai" type="time" class="flex-1 min-h-[44px] px-3 rounded-[12px] bg-[var(--search)]" />
      </div>

      <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Periode isi otomatis</p>
      <select v-model="durasi" class="w-full min-h-[44px] px-3 rounded-[12px] bg-[var(--search)] mb-2">
        <option value="minggu">1 minggu (sisa minggu ini)</option>
        <option v-for="n in 12" :key="n" :value="String(n)">{{ n }} bulan</option>
      </select>
      <p class="text-[12px] text-[var(--mut)] mb-5">
        Contoh: 1 bulan + Sabtu saja ≈ 4 malam; semua KK dibagi ke 4 malam itu.
      </p>

      <button
        type="button"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-2 disabled:opacity-50"
        :disabled="isiBusy || !adaHari"
        @click="jalankanIsi"
      >
        {{ isiBusy ? 'Mengisi…' : 'Isi otomatis' }}
      </button>
      <p v-if="msg" class="text-[13px] text-center mt-2" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listJadwalTetap, simpanJadwalTetap, isiOtomatisRonda } from '@shared/services/ronda.js'

const hariPendek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']
const hariLabel = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']

const loading = ref(true)
const err = ref('')
const selected = ref([false, false, false, false, false, false, false])
const jamMulai = ref('21:00')
const jamSelesai = ref('00:00')
const durasi = ref('1')
const toggling = ref(false)
const isiBusy = ref(false)
const msg = ref('')
const msgOk = ref(true)

const adaHari = computed(() => selected.value.some(Boolean))
const hariAktifLabel = computed(() => {
  const names = []
  selected.value.forEach((on, h) => { if (on) names.push(hariLabel[h]) })
  return names.join(', ')
})

async function load() {
  loading.value = true
  err.value = ''
  const j = await listJadwalTetap()
  loading.value = false
  if (!j.ok) {
    err.value = j.error || 'Gagal memuat jadwal tetap'
    return
  }
  const next = [false, false, false, false, false, false, false]
  let jamSet = false
  for (const row of j.data || []) {
    const h = Number(row.hari)
    if (h >= 0 && h <= 6) {
      next[h] = true
      if (!jamSet && row.jam_mulai) {
        jamMulai.value = String(row.jam_mulai).slice(0, 5)
        jamSelesai.value = String(row.jam_selesai || '00:00').slice(0, 5)
        jamSet = true
      }
    }
  }
  selected.value = next
}

async function toggleHari(h) {
  if (toggling.value) return
  toggling.value = true
  msg.value = ''
  const on = !selected.value[h]
  const payload = on
    ? {
        hari: h,
        jam_mulai: jamMulai.value + ':00',
        jam_selesai: jamSelesai.value + ':00',
        keluarga_ids: [],
      }
    : { hari: h, hapus: true }

  const res = await simpanJadwalTetap(payload)
  toggling.value = false
  if (!res.ok) {
    msgOk.value = false
    msg.value = res.error || 'Gagal menyimpan hari'
    return
  }
  const copy = selected.value.slice()
  copy[h] = on
  selected.value = copy
}

async function jalankanIsi() {
  if (!adaHari.value) {
    msgOk.value = false
    msg.value = 'Pilih minimal satu hari ronda'
    return
  }
  isiBusy.value = true
  msg.value = ''
  for (let h = 0; h < 7; h++) {
    if (!selected.value[h]) continue
    await simpanJadwalTetap({
      hari: h,
      jam_mulai: jamMulai.value + ':00',
      jam_selesai: jamSelesai.value + ':00',
      keluarga_ids: [],
    })
  }
  const res = await isiOtomatisRonda({
    durasi: durasi.value,
    keluarga_per_malam: 0,
  })
  isiBusy.value = false
  msgOk.value = !!res.ok
  if (res.ok) {
    const d = res.data || {}
    msg.value = `Selesai: ${d.dibuat ?? 0} malam · ${d.total_kk ?? 0} KK · ~${d.keluarga_per_malam ?? '—'} KK/malam`
  } else {
    msg.value = res.error || 'Gagal isi otomatis'
  }
}

onMounted(load)
</script>
