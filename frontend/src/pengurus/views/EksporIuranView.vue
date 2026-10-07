<template>
  <div>
    <AppBackHeader title="Ekspor iuran" />
    <p class="text-[13px] text-[var(--mut)] mb-4">Unduh daftar status iuran keluarga (CSV).</p>
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="busy" @click="unduh">
      {{ busy ? 'Menyiapkan…' : 'Unduh CSV' }}
    </button>
    <p v-if="msg" class="text-[13px] text-center mt-3" :class="ok ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { daftarIuran } from '@shared/services/keuangan.js'

const busy = ref(false)
const msg = ref('')
const ok = ref(true)

async function unduh() {
  busy.value = true
  msg.value = ''
  const res = await daftarIuran({})
  busy.value = false
  if (!res.ok) { ok.value = false; msg.value = res.error || 'Gagal'; return }
  const rows = [['No', 'Nama', 'Alamat', 'Total', 'Status']]
  let n = 0
  for (const r of res.data || []) {
    n++
    rows.push([String(n), r.nama || '', r.alamat || '', String(r.total ?? 0), r.status || ''])
  }
  const text = rows.map((r) => r.map((c) => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n')
  const blob = new Blob(['\ufeff' + text], { type: 'text/csv;charset=utf-8' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = 'iuran.csv'
  a.click()
  ok.value = true
  msg.value = `${n} baris diunduh`
}
</script>
