<template>
  <div>
    <AppBackHeader title="Pengumuman" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada pengumuman</p>
    <div v-else class="space-y-3">
      <div
        v-for="p in daftar"
        :key="p.id"
        class="rounded-[20px] overflow-hidden relative text-white"
        :style="cardStyle(p.aktif)"
      >
        <div class="p-5 relative z-10">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="flex items-center gap-2 text-[13px] font-semibold text-white/90">
              <Megaphone :size="16" />
              {{ p.aktif ? 'Pengumuman' : 'Riwayat' }}
              <span
                v-if="p.lampiran_file"
                class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white/20"
              >📎</span>
            </span>
            <span class="text-[12px] font-semibold px-3 py-1 rounded-full bg-white/20">
              {{ formatTgl(p.diterbitkan_pada) }}
            </span>
          </div>
          <h2 class="text-[17px] font-bold leading-snug mt-3 mb-1 text-white">{{ p.judul }}</h2>
          <p class="text-[13px] m-0 text-white/90">{{ ringkas(p.isi) }}</p>
          <button
            type="button"
            class="inline-flex items-center gap-1 mt-3.5 min-h-[44px] px-4 rounded-full font-bold text-[15px]"
            :class="p.aktif ? 'bg-white text-[#0B6B34]' : 'bg-white/90 text-[#374151]'"
            @click="$router.push('/pengumuman/' + p.id)"
          >
            Lihat detail <ChevronRight :size="18" />
          </button>
        </div>
        <div class="absolute -right-12 -bottom-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
      </div>
    </div>
  </div>
</template>
<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'

import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { Megaphone, ChevronRight } from 'lucide-vue-next'
import { listPengumuman } from '@shared/services/konten.js'
import { plainFromMarkdown } from '@shared/utils/markdown.js'

const daftar = ref([])
const loading = ref(true)
const err = ref('')

const styleAktif = {
  background: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)',
}
const styleSelesai = {
  background: 'radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.18), transparent 55%), linear-gradient(145deg, #9CA3AF, #6B7280 55%, #4B5563)',
}

function cardStyle(aktif) {
  return aktif ? styleAktif : styleSelesai
}

function isAktifTampil(p) {
  const now = Date.now()
  if (!p.tampil_selesai) return false
  const t1 = new Date(String(p.tampil_selesai).replace(' ', 'T')).getTime()
  if (isNaN(t1) || now > t1) return false
  if (p.tampil_mulai) {
    const t0 = new Date(String(p.tampil_mulai).replace(' ', 'T')).getTime()
    if (!isNaN(t0) && now < t0) return false
  }
  return true
}

function formatTgl(s) {
  if (!s) return ''
  const d = new Date(String(s).replace(' ', 'T'))
  return isNaN(d.getTime()) ? String(s).slice(0, 10) : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}

function ringkas(isi) {
  return plainFromMarkdown(isi, 120)
}

onMounted(async () => {
  const res = await listPengumuman()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    return
  }
  const rows = (res.data || []).map((p) => ({
    ...p,
    aktif: isAktifTampil(p),
  }))
  rows.sort((a, b) => {
    if (a.aktif !== b.aktif) return a.aktif ? -1 : 1
    return String(b.diterbitkan_pada || '').localeCompare(String(a.diterbitkan_pada || ''))
  })
  daftar.value = rows
})
</script>
