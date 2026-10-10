<template>
  <div>
    <AppBackHeader title="Isi otomatis" />

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <template v-else>
      <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] px-4 py-4 mb-4 shadow-[var(--sh)]">
        <p class="text-[14px] font-bold m-0 mb-2">Cara kerja</p>
        <ul class="text-[13px] text-[var(--mut)] m-0 pl-4 space-y-1.5 list-disc">
          <li>Mengisi <b class="text-[var(--tx)]">jadwal tetap</b> yang masih kosong saja.</li>
          <li>KK aktif diurutkan blok & nomor, dibagi merata ke kelompok (hari aktif × 4 minggu).</li>
          <li>Kelompok A selalu di <b class="text-[var(--tx)]">awal bulan</b> — tidak tergantung kapan tombol ini ditekan.</li>
          <li>Contoh: 2 hari/minggu → 8 kelompok; kelompok 1 = hari ronda pertama bulan, dst.</li>
          <li>Jika ada <b class="text-[var(--tx)]">jadwal khusus</b> di hari itu, kelompok tetap tidak ronda (diganti khusus, tidak digeser).</li>
          <li>Bulan berikutnya pola mengulang dari kelompok A lagi.</li>
        </ul>
      </div>

      <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card2)] px-4 py-3 mb-5">
        <p class="text-[13px] font-semibold m-0 mb-1 text-[var(--mut)]">Ringkasan saat ini</p>
        <p class="text-[14px] m-0">
          Hari aktif:
          <b>{{ hariAktifLabel }}</b>
        </p>
        <p class="text-[14px] m-0 mt-1">
          Estimasi siklus: <b>{{ estimasiSlot }} slot / bulan</b>
        </p>
        <p v-if="!adaHari" class="text-[13px] text-red-600 m-0 mt-2">
          Atur hari ronda di Jadwal tetap dulu.
        </p>
      </div>

      <button
        type="button"
        class="w-full min-h-[44px] rounded-full bg-[var(--g)] text-white font-bold mb-3 disabled:opacity-50 dark:bg-white dark:text-black"
        :disabled="busy || !adaHari"
        @click="jalankan"
      >
        {{ busy ? 'Mengisi…' : 'Jalankan isi otomatis' }}
      </button>

      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listJadwalTetap, isiOtomatisRonda } from '@shared/services/ronda.js'

const hariLabel = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
const loading = ref(true)
const busy = ref(false)
const msg = ref('')
const msgOk = ref(true)
const hariAktif = ref([])

const adaHari = computed(() => hariAktif.value.length > 0)
const hariAktifLabel = computed(() => {
  if (!hariAktif.value.length) return '—'
  return hariAktif.value.map((h) => hariLabel[h]).join(', ')
})
const estimasiSlot = computed(() => (adaHari.value ? hariAktif.value.length * 4 : 0))

async function load() {
  loading.value = true
  const j = await listJadwalTetap()
  const days = []
  if (j.ok && Array.isArray(j.data)) {
    for (const row of j.data) {
      const h = Number(row.hari)
      if (h >= 0 && h <= 6 && !days.includes(h)) days.push(h)
    }
    days.sort((a, b) => a - b)
  }
  hariAktif.value = days
  loading.value = false
}

async function jalankan() {
  busy.value = true
  msg.value = ''
  const res = await isiOtomatisRonda({})
  busy.value = false
  msgOk.value = !!res.ok
  if (res.ok) {
    const d = res.data || {}
    const parts = []
    parts.push(`${d.diisi ?? 0} malam diisi`)
    if (d.dilewati) parts.push(`${d.dilewati} dilewati`)
    parts.push(`${d.total_kk ?? 0} KK`)
    if (d.jumlah_kelompok) parts.push(`${d.jumlah_kelompok} kelompok`)
    msg.value = 'Selesai: ' + parts.join(' · ')
  } else {
    msg.value = res.error || 'Gagal isi otomatis'
  }
}

onMounted(load)
</script>
