<template>
  <div>
    <AppBackHeader title="Pengumuman" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada pengumuman</p>
    <div v-else class="space-y-3">
      <div
        v-for="p in daftar"
        :key="p.id"
        class="rounded-[20px] overflow-hidden relative bg-[var(--card)] border border-[var(--line)] shadow-[var(--sh)]"
      >
        <div class="p-5 relative z-10">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="flex items-center gap-2 text-[13px] font-semibold text-[var(--mut)]">
              <Megaphone :size="16" />
              Pengumuman
              <span
                v-if="p.lampiran_file"
                class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[var(--search)] text-[var(--mut)]"
              >📎</span>
            </span>
            <span class="text-[12px] font-semibold px-3 py-1 rounded-full bg-[var(--search)] text-[var(--mut)]">
              {{ formatTgl(p.diterbitkan_pada) }}
            </span>
          </div>
          <h2 class="text-[17px] font-bold leading-snug mt-3 mb-1">{{ p.judul }}</h2>
          <p class="text-[13px] m-0 text-[var(--mut)]">{{ ringkas(p.isi) }}</p>
          <button
            type="button"
            class="inline-flex items-center gap-1 mt-3.5 min-h-[44px] px-4 rounded-full font-bold text-[15px] bg-[var(--card2)] text-[var(--text)]"
            @click="$router.push('/pengumuman/' + p.id)"
          >
            Lihat detail <ChevronRight :size="18" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { Megaphone, ChevronRight } from 'lucide-vue-next'
import { listPengumuman } from '@shared/services/konten.js'

const daftar = ref([])
const loading = ref(true)
const err = ref('')

function formatTgl(s) {
  if (!s) return ''
  const d = new Date(String(s).replace(' ', 'T'))
  return isNaN(d.getTime()) ? String(s).slice(0, 10) : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}

function ringkas(isi) {
  const t = String(isi || '').replace(/\s+/g, ' ').trim()
  return t.length > 120 ? t.slice(0, 120) + '…' : t
}

onMounted(async () => {
  const res = await listPengumuman()
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else daftar.value = res.data || []
})
</script>
