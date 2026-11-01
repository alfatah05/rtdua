<template>
  <div>
    <AppBackHeader title="Detail aktivitas" />

    <AppPageSpinner v-if="loading" class="!py-10" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-6">{{ err }}</p>
    <template v-else-if="row">
      <!-- Header jenis -->
      <div
        class="rounded-[20px] p-5 mb-4 relative overflow-hidden"
        :style="{ background: headerBg }"
      >
        <div class="relative z-10 flex items-start gap-3">
          <div class="w-12 h-12 rounded-[16px] grid place-items-center shrink-0 bg-white/20 text-white">
            <component :is="meta.icon" :size="24" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[13px] font-semibold m-0 text-white/85">{{ kategori }}</p>
            <p class="text-[18px] font-extrabold m-0 mt-1 text-white leading-snug">{{ judul }}</p>
            <p class="text-[13px] m-0 mt-2 text-white/80">{{ formatWaktu(row.waktu) }}</p>
          </div>
        </div>
        <div class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full bg-white/10 pointer-events-none" />
      </div>

      <!-- Info utama -->
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 mb-3 space-y-3">
        <div>
          <p class="text-[12px] text-[var(--mut)] m-0 mb-0.5">Pelaku</p>
          <p class="font-bold text-[15px] m-0">{{ row.pelaku }}</p>
          <p v-if="row.jabatan" class="text-[13px] text-[var(--mut)] m-0 mt-0.5">{{ row.jabatan }}</p>
        </div>
        <div>
          <p class="text-[12px] text-[var(--mut)] m-0 mb-0.5">Aksi</p>
          <p class="font-bold text-[15px] m-0">{{ judul }}</p>
        </div>
        <div v-if="row.objek">
          <p class="text-[12px] text-[var(--mut)] m-0 mb-0.5">Objek</p>
          <p class="font-bold text-[15px] m-0">
            {{ row.objek }}<span v-if="row.objek_id"> #{{ row.objek_id }}</span>
          </p>
        </div>
      </div>

      <!-- Ringkasan khusus jenis -->
      <div v-if="highlights.length" class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 mb-3">
        <p class="text-[13px] font-bold m-0 mb-2">Ringkasan</p>
        <div class="space-y-2">
          <div v-for="(h, i) in highlights" :key="i" class="flex justify-between gap-3">
            <span class="text-[13px] text-[var(--mut)]">{{ h.label }}</span>
            <span class="text-[14px] font-semibold text-right">{{ h.value }}</span>
          </div>
        </div>
      </div>

      <!-- Sebelum / sesudah -->
      <div v-if="sebelumText" class="mb-3">
        <p class="text-[13px] font-bold m-0 mb-1.5 px-0.5">Sebelum</p>
        <pre class="m-0 text-[13px] leading-relaxed whitespace-pre-wrap bg-[var(--search)] rounded-[16px] p-4 text-[var(--text)]">{{ sebelumText }}</pre>
      </div>
      <div v-if="sesudahText" class="mb-3">
        <p class="text-[13px] font-bold m-0 mb-1.5 px-0.5">Sesudah</p>
        <pre class="m-0 text-[13px] leading-relaxed whitespace-pre-wrap bg-[var(--search)] rounded-[16px] p-4 text-[var(--text)]">{{ sesudahText }}</pre>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { detailAktivitas } from '@shared/services/aktivitas.js'
import { labelAksi, iconMeta, formatWaktu, formatJsonBlock } from '@shared/utils/aktivitas.js'

const route = useRoute()
const loading = ref(true)
const err = ref('')
const row = ref(null)

const meta = computed(() => iconMeta(row.value?.aksi))
const judul = computed(() => (row.value ? labelAksi(row.value) : ''))

const kategori = computed(() => {
  const a = String(row.value?.aksi || '')
  if (a.includes('kas') || a.includes('pembayaran') || a.includes('transfer') || a.includes('iuran') || a.includes('sub_pos') || a.includes('denda') || a.includes('rekening')) {
    return 'Keuangan'
  }
  if (a.includes('keluarga') || a.includes('anggota') || a.includes('kepala') || a.includes('pindah') || a.includes('pin')) {
    return 'Warga'
  }
  if (a.includes('pengurus')) return 'Pengurus'
  if (a.includes('reward')) return 'Reward'
  if (a.includes('pengumuman') || a.includes('program') || a.includes('konten') || a.includes('album') || a.includes('galeri')) {
    return 'Konten'
  }
  if (a === 'login' || a === 'logout' || a.includes('pengaturan') || a.includes('profil') || a.includes('kredensial')) {
    return 'Sistem'
  }
  return 'Aktivitas'
})

const headerBg = computed(() => {
  const c = meta.value.color || '#64748B'
  // solid-ish gradient from icon color
  return `radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, ${c}, ${c})`
})

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}

const highlights = computed(() => {
  if (!row.value) return []
  const a = String(row.value.aksi || '')
  const s = row.value.sesudah || {}
  const b = row.value.sebelum || {}
  const out = []

  if (a === 'kas_alokasi_pos') {
    if (s.dari_pos_nama) out.push({ label: 'Dari', value: s.dari_pos_nama })
    if (s.ke_pos_nama) out.push({ label: 'Ke', value: s.ke_pos_nama })
    if (s.nominal != null) out.push({ label: 'Nominal', value: rp(s.nominal) })
  } else if (a.startsWith('kas_') || a.includes('pembayaran') || a.includes('transfer') || a.includes('sub_pos')) {
    if (s.nominal != null) out.push({ label: 'Nominal', value: rp(s.nominal) })
    if (s.metode) out.push({ label: 'Metode', value: s.metode })
    if (s.kategori) out.push({ label: 'Kategori', value: s.kategori })
    if (s.keterangan) out.push({ label: 'Keterangan', value: s.keterangan })
    if (s.alasan) out.push({ label: 'Alasan', value: s.alasan })
  } else if (a.includes('keluarga') || a.includes('anggota')) {
    if (s.nama) out.push({ label: 'Nama', value: s.nama })
    if (s.alamat) out.push({ label: 'Alamat', value: s.alamat })
    if (s.status) out.push({ label: 'Status', value: s.status })
  } else if (a.includes('reward')) {
    if (s.judul) out.push({ label: 'Event', value: s.judul })
    if (s.batch_id) out.push({ label: 'Batch', value: '#' + s.batch_id })
  }

  // generic nominal
  if (!out.length && s.nominal != null) out.push({ label: 'Nominal', value: rp(s.nominal) })
  if (!out.length && b.nominal != null) out.push({ label: 'Nominal (sebelum)', value: rp(b.nominal) })

  return out
})

const sebelumText = computed(() => formatJsonBlock(row.value?.sebelum))
const sesudahText = computed(() => formatJsonBlock(row.value?.sesudah))

async function load() {
  loading.value = true
  err.value = ''
  const id = Number(route.params.id)
  if (!id) {
    err.value = 'ID tidak valid'
    loading.value = false
    return
  }
  const res = await detailAktivitas(id)
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  row.value = res.data
}

onMounted(load)
</script>
