<template>
  <div>
    <AppBackHeader title="Ekspor data warga" />
    <div class="space-y-4 mb-6">
      <div>
        <p class="text-[13px] font-bold text-[var(--mut)] mb-2">Mode</p>
        <div class="flex gap-2">
          <button type="button" class="flex-1 min-h-[44px] rounded-full text-[13px] font-semibold border"
            :class="mode === 'keluarga' ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="mode = 'keluarga'">Per keluarga</button>
          <button type="button" class="flex-1 min-h-[44px] rounded-full text-[13px] font-semibold border"
            :class="mode === 'warga' ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="mode = 'warga'">Per warga</button>
        </div>
      </div>
      <p class="text-[13px] text-[var(--mut)]">Kolom: No, Nama, Alamat. Tanpa NIK & telepon.</p>
    </div>
    <div class="space-y-3">
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="busy" @click="unduh('csv')">
        {{ busy ? 'Menyiapkan…' : 'Unduh CSV' }}
      </button>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--card2)] font-bold" :disabled="busy" @click="unduh('tsv')">
        Unduh Excel (TSV)
      </button>
    </div>
    <p v-if="msg" class="text-[13px] mt-4 text-center" :class="ok ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listKeluarga, getKeluarga } from '@shared/services/warga.js'

const mode = ref('keluarga')
const busy = ref(false)
const msg = ref('')
const ok = ref(true)

function download(name, text) {
  const blob = new Blob(['\ufeff' + text], { type: 'text/csv;charset=utf-8' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = name
  a.click()
}

async function unduh(fmt) {
  busy.value = true
  msg.value = ''
  const res = await listKeluarga({ status: 'aktif' })
  if (!res.ok) {
    busy.value = false
    ok.value = false
    msg.value = res.error || 'Gagal memuat data'
    return
  }
  const rows = [['No', 'Nama', 'Alamat']]
  let n = 0
  if (mode.value === 'keluarga') {
    for (const k of res.data || []) {
      n++
      rows.push([String(n), k.nama || '', k.alamat || ''])
    }
  } else {
    for (const k of res.data || []) {
      const d = await getKeluarga(k.id)
      const anggota = d.ok ? (d.data?.anggota || []) : []
      if (!anggota.length) {
        n++
        rows.push([String(n), k.nama || '', k.alamat || ''])
      } else {
        for (const a of anggota) {
          n++
          rows.push([String(n), a.nama || '', k.alamat || ''])
        }
      }
    }
  }
  const sep = fmt === 'tsv' ? '\t' : ','
  const text = rows.map((r) => r.map((c) => `"${String(c).replace(/"/g, '""')}"`).join(sep)).join('\n')
  const ext = fmt === 'tsv' ? 'xls' : 'csv'
  download(mode.value === 'keluarga' ? `warga-keluarga.${ext}` : `warga-anggota.${ext}`, text)
  busy.value = false
  ok.value = true
  msg.value = `${n} baris diunduh`
}
</script>
