<template>
  <div>
    <AppBackHeader title="Tarif iuran & denda" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-8">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <form v-else class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal kas / bulan</label>
        <input v-model="kas" type="text" inputmode="numeric" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Denda ronda / ketidakhadiran</label>
        <input v-model="denda" type="text" inputmode="numeric" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
        <p class="text-[12px] text-[var(--mut)] mt-1 m-0">Denda yang sudah terbit tidak diubah; berlaku untuk denda baru.</p>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Jam ronda default</label>
        <div class="flex gap-2">
          <input v-model="jamMulai" type="time" class="flex-1 min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
          <input v-model="jamSelesai" type="time" class="flex-1 min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
        </div>
      </div>
      <label class="flex items-start gap-3 p-3 rounded-[16px] bg-[var(--card)] border border-[var(--line)]">
        <input v-model="terapkanBulanIni" type="checkbox" class="mt-1 w-4 h-4" />
        <span>
          <span class="block text-[14px] font-semibold">Terapkan juga ke bulan ini</span>
          <span class="block text-[12px] text-[var(--mut)]">Ubah nominal tagihan kas periode {{ bulanIni }} untuk semua KK yang sudah punya tagihan.</span>
        </span>
      </label>
      <div v-if="pratinjau" class="p-3 rounded-[16px] bg-[var(--search)] text-[13px]">
        <p class="font-semibold m-0 mb-1">Pratinjau dampak</p>
        <p class="m-0 text-[var(--mut)]">Keluarga terdampak: <b class="text-[var(--text)]">{{ pratinjau.keluarga_terdampak }}</b></p>
        <p class="m-0 text-[var(--mut)]">Berpotensi kelebihan bayar: <b class="text-[var(--text)]">{{ pratinjau.jadi_kelebihan }}</b></p>
      </div>
      <button type="button" class="w-full min-h-[44px] rounded-full bg-[var(--card2)] font-semibold" :disabled="saving" @click="onPratinjau">
        {{ saving ? '…' : 'Pratinjau dampak' }}
      </button>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">
        {{ saving ? 'Menyimpan…' : 'Simpan' }}
      </button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getPengaturan, updateWarga } from '@shared/services/pengaturan.js'
import { ubahNominal } from '@shared/services/keuangan.js'

const loading = ref(true)
const saving = ref(false)
const err = ref('')
const msg = ref('')
const msgOk = ref(true)
const kas = ref('')
const denda = ref('')
const jamMulai = ref('21:00')
const jamSelesai = ref('00:00')
const terapkanBulanIni = ref(false)
const pratinjau = ref(null)
const bulanIni = new Date().toISOString().slice(0, 7)

function toNum(v) {
  return String(v || '').replace(/[^\d]/g, '')
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await getPengaturan()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  const d = res.data || {}
  kas.value = String(d.nominal_kas ?? '')
  denda.value = String(d.denda_ronda ?? '')
  jamMulai.value = (d.jam_ronda_mulai || '21:00').slice(0, 5)
  jamSelesai.value = (d.jam_ronda_selesai || '00:00').slice(0, 5)
}

async function onPratinjau() {
  saving.value = true
  msg.value = ''
  pratinjau.value = null
  const res = await ubahNominal({
    nominal_kas: parseInt(toNum(kas.value), 10) || 0,
    denda_ronda: parseInt(toNum(denda.value), 10) || 0,
    terapkan_bulan_ini: terapkanBulanIni.value,
    pratinjau_saja: true,
  })
  saving.value = false
  if (!res.ok) {
    msgOk.value = false
    msg.value = res.error || 'Gagal pratinjau'
    return
  }
  pratinjau.value = res.data?.pratinjau || { keluarga_terdampak: 0, jadi_kelebihan: 0 }
  msgOk.value = true
  msg.value = 'Pratinjau siap'
}

async function onSave() {
  saving.value = true
  msg.value = ''
  const jamRes = await updateWarga({
    jam_ronda_mulai: jamMulai.value,
    jam_ronda_selesai: jamSelesai.value,
  })
  if (!jamRes.ok) {
    saving.value = false
    msgOk.value = false
    msg.value = jamRes.error || 'Gagal simpan jam'
    return
  }
  const res = await ubahNominal({
    nominal_kas: parseInt(toNum(kas.value), 10) || 0,
    denda_ronda: parseInt(toNum(denda.value), 10) || 0,
    terapkan_bulan_ini: terapkanBulanIni.value,
    pratinjau_saja: false,
  })
  saving.value = false
  msgOk.value = !!res.ok
  if (res.ok) {
    pratinjau.value = res.data?.pratinjau || pratinjau.value
    msg.value = terapkanBulanIni.value
      ? `Disimpan. Tagihan bulan ini diubah (${pratinjau.value?.keluarga_terdampak ?? 0} KK).`
      : 'Pengaturan disimpan'
  } else {
    msg.value = res.error || 'Gagal menyimpan'
  }
}

onMounted(load)
</script>
