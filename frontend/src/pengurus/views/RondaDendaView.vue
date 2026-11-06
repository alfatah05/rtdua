<template>
  <div>
    <AppBackHeader title="Pengganti kehadiran" />

    <p class="text-[13px] text-[var(--mut)] m-0 mb-4 leading-snug">
      Terbitkan pengganti kehadiran untuk keluarga yang bertugas di bulan lalu tetapi tidak absen.
      Setelah diterbitkan, absensi bulan tersebut terkunci.
    </p>

    <!-- Tarif -->
    <div class="rounded-[16px] border border-[var(--line)] bg-[var(--card)] p-4 mb-5">
      <p class="text-[13px] font-semibold m-0 mb-1">Tarif pengganti kehadiran</p>
      <p class="text-[12px] text-[var(--mut)] m-0 mb-3">
        Nominal per kali tidak absen. Dipakai saat menerbitkan tagihan.
      </p>
      <div class="flex items-center gap-2">
        <span class="text-[14px] font-semibold text-[var(--mut)] shrink-0">Rp</span>
        <input
          v-model="tarifInput"
          type="text"
          inputmode="numeric"
          class="flex-1 min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none text-[15px] font-semibold"
          placeholder="25000"
          @blur="formatTarifDisplay"
        />
      </div>
      <button
        type="button"
        class="w-full min-h-[40px] mt-3 rounded-full border border-[var(--line)] text-[13px] font-bold active:scale-[0.98] disabled:opacity-50"
        :disabled="busyTarif || !tarifBerubah"
        @click="simpanTarif"
      >
        {{ busyTarif ? 'Menyimpan…' : 'Simpan tarif' }}
      </button>
      <p v-if="msgTarif" class="text-[12px] text-center m-0 mt-2" :class="msgTarifOk ? 'text-[var(--g)]' : 'text-red-600'">
        {{ msgTarif }}
      </p>
    </div>

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

    <p class="text-[12px] text-[var(--mut)] m-0 mb-3 text-center">
      Tarif aktif: <span class="font-bold text-[var(--text)]">Rp {{ formatRp(tarifSaved) }}</span> / kali
    </p>

    <button
      type="button"
      class="w-full min-h-[44px] rounded-full bg-[var(--g)] text-white font-bold mb-3 disabled:opacity-50 dark:bg-white dark:text-black"
      :disabled="busy"
      @click="jalankan"
    >
      {{ busy ? 'Memproses…' : 'Terbitkan' }}
    </button>

    <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { terbitkanDendaRonda, getDendaRonda, setDendaRonda } from '@shared/services/ronda.js'

function ymOffset(monthsBack) {
  const d = new Date()
  d.setDate(1)
  d.setMonth(d.getMonth() - monthsBack)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

function parseRp(v) {
  const n = Number(String(v ?? '').replace(/[^\d]/g, ''))
  return Number.isFinite(n) ? n : 0
}

function formatRp(n) {
  return Number(n || 0).toLocaleString('id-ID')
}

const periodeLalu = ref(ymOffset(1))
const periodeTagihan = ref(ymOffset(0))
const busy = ref(false)
const msg = ref('')
const msgOk = ref(true)

const tarifSaved = ref(25000)
const tarifInput = ref('25.000')
const busyTarif = ref(false)
const msgTarif = ref('')
const msgTarifOk = ref(true)

const tarifBerubah = computed(() => parseRp(tarifInput.value) !== Number(tarifSaved.value))

function formatTarifDisplay() {
  const n = parseRp(tarifInput.value)
  tarifInput.value = n ? formatRp(n) : ''
}

async function loadTarif() {
  const res = await getDendaRonda()
  if (res.ok) {
    const n = Number(res.data?.denda_ronda ?? 25000) || 25000
    tarifSaved.value = n
    tarifInput.value = formatRp(n)
  }
}

async function simpanTarif() {
  const n = parseRp(tarifInput.value)
  if (n < 1) {
    msgTarifOk.value = false
    msgTarif.value = 'Nominal minimal Rp 1'
    return
  }
  busyTarif.value = true
  msgTarif.value = ''
  const res = await setDendaRonda(n)
  busyTarif.value = false
  msgTarifOk.value = !!res.ok
  if (res.ok) {
    const saved = Number(res.data?.denda_ronda ?? n)
    tarifSaved.value = saved
    tarifInput.value = formatRp(saved)
    msgTarif.value = 'Tarif disimpan'
  } else {
    msgTarif.value = res.error || 'Gagal simpan tarif'
  }
}

async function jalankan() {
  // Auto-simpan tarif jika diubah sebelum terbitkan
  if (tarifBerubah.value) {
    await simpanTarif()
    if (!msgTarifOk.value) return
  }
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
    msg.value = `Pengganti terbit: ${d.denda ?? 0} keluarga · total Rp ${(d.total_nominal ?? 0).toLocaleString('id-ID')} (Rp ${(d.nominal_satuan ?? tarifSaved.value).toLocaleString('id-ID')}/kali)`
  } else {
    msg.value = res.error || 'Gagal terbitkan'
  }
}

onMounted(loadTarif)
</script>
