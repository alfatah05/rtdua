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
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Jam ronda default</label>
        <div class="flex gap-2">
          <input v-model="jamMulai" type="time" class="flex-1 min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
          <input v-model="jamSelesai" type="time" class="flex-1 min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
        </div>
      </div>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getPengaturan, updateWarga } from '@shared/services/pengaturan.js'

const loading = ref(true)
const saving = ref(false)
const err = ref('')
const msg = ref('')
const msgOk = ref(true)
const kas = ref('')
const denda = ref('')
const jamMulai = ref('21:00')
const jamSelesai = ref('00:00')

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

async function onSave() {
  saving.value = true
  msg.value = ''
  const res = await updateWarga({
    nominal_kas: parseInt(toNum(kas.value), 10) || 0,
    denda_ronda: parseInt(toNum(denda.value), 10) || 0,
    jam_ronda_mulai: jamMulai.value,
    jam_ronda_selesai: jamSelesai.value,
  })
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Pengaturan disimpan' : (res.error || 'Gagal menyimpan')
}

onMounted(load)
</script>
