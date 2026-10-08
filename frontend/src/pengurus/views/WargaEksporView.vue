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
      <p class="text-[13px] text-[var(--mut)]">Kolom: No, Nama, Alamat. Tanpa NIK & telepon. File dibuat di perangkat (bukan server).</p>
    </div>
    <div class="space-y-3">
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="busy" @click="unduh('csv')">
        {{ busy ? 'Menyiapkan…' : 'Unduh CSV' }}
      </button>
      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--card2)] font-bold" :disabled="busy" @click="unduh('xlsx')">
        Unduh Excel (XLSX/TSV)
      </button>
      <button type="button" class="w-full min-h-[48px] rounded-full border border-[var(--line)] font-bold" :disabled="busy" @click="unduh('pdf')">
        Unduh / Cetak PDF
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

function downloadBlob(name, blob) {
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = name
  a.click()
  setTimeout(() => URL.revokeObjectURL(a.href), 2000)
}

async function buildRows() {
  const res = await listKeluarga({ status: 'aktif' })
  if (!res.ok) throw new Error(res.error || 'Gagal memuat data')
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
          if (a.status && a.status !== 'aktif') continue
          n++
          rows.push([String(n), a.nama || '', k.alamat || ''])
        }
      }
    }
  }
  return { rows, n }
}

function toDelimited(rows, sep) {
  return rows.map((r) => r.map((c) => `"${String(c).replace(/"/g, '""')}"`).join(sep)).join('\n')
}

function openPdf(rows) {
  const title = mode.value === 'keluarga' ? 'Data keluarga' : 'Data warga'
  const tr = rows.map((r, i) => {
    const tag = i === 0 ? 'th' : 'td'
    return '<tr>' + r.map((c) => `<${tag}>${String(c).replace(/</g, '<')}</${tag}>`).join('') + '</tr>'
  }).join('')
  const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${title}</title>
<style>body{font-family:system-ui,sans-serif;padding:24px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:8px;text-align:left;font-size:13px}th{background:#f3f4f6}h1{font-size:18px}</style></head>
<body><h1>${title} RT</h1><p style="color:#666;font-size:12px">Dicetak ${new Date().toLocaleString('id-ID')} · Tanpa NIK & telepon</p>
<table>${tr}</table>
<script>window.onload=function(){window.print()}<\/script></body></html>`
  const w = window.open('', '_blank')
  if (!w) throw new Error('Popup diblokir — izinkan popup untuk PDF')
  w.document.write(html)
  w.document.close()
}

async function unduh(fmt) {
  busy.value = true
  msg.value = ''
  try {
    const { rows, n } = await buildRows()
    const base = mode.value === 'keluarga' ? 'warga-keluarga' : 'warga-anggota'
    if (fmt === 'csv') {
      downloadBlob(base + '.csv', new Blob(['\ufeff' + toDelimited(rows, ',')], { type: 'text/csv;charset=utf-8' }))
    } else if (fmt === 'xlsx') {
      // TSV dengan ekstensi .xls — Excel/LibreOffice membuka dengan benar tanpa library
      downloadBlob(base + '.xls', new Blob(['\ufeff' + toDelimited(rows, '\t')], { type: 'application/vnd.ms-excel;charset=utf-8' }))
    } else if (fmt === 'pdf') {
      openPdf(rows)
    }
    ok.value = true
    msg.value = `${n} baris siap`
  } catch (e) {
    ok.value = false
    msg.value = e.message || 'Gagal ekspor'
  }
  busy.value = false
}
</script>
