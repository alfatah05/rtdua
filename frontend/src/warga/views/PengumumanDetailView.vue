<template>
  <div>
    <AppBackHeader title="Detail pengumuman" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="item">
      <p class="text-[12px] text-[var(--mut)] m-0 mb-1">{{ formatTgl(item.diterbitkan_pada) }}</p>
      <h1 class="text-[20px] font-extrabold m-0 mb-3">{{ item.judul }}</h1>
      <p class="text-[15px] leading-relaxed whitespace-pre-wrap m-0 mb-4">{{ item.isi }}</p>

      <!-- Lampiran gambar -->
      <div v-if="isImage" class="mb-4">
        <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Lampiran</p>
        <a :href="lampiranUrl" target="_blank" rel="noopener" class="block rounded-[16px] overflow-hidden border border-[var(--line)] bg-[var(--search)]">
          <img :src="lampiranUrl" alt="Lampiran" class="w-full max-h-[420px] object-contain bg-white" @error="imgError = true" />
        </a>
        <p v-if="imgError" class="text-[13px] text-red-600 mt-2">Gagal memuat gambar. Coba buka tautan di bawah.</p>
        <a :href="lampiranUrl" target="_blank" rel="noopener" class="inline-block mt-2 text-[13px] font-semibold text-[var(--g)]">Buka gambar penuh →</a>
      </div>

      <!-- Lampiran PDF / file lain -->
      <div v-else-if="hasLampiran" class="mb-4">
        <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Lampiran</p>
        <a
          :href="lampiranUrl"
          target="_blank"
          rel="noopener"
          class="flex items-center gap-3 p-4 rounded-[16px] border border-[var(--line)] bg-[var(--card)] no-underline active:scale-[0.99] transition"
        >
          <span class="w-12 h-12 rounded-full bg-red-50 text-red-600 grid place-items-center font-bold text-[13px] shrink-0">PDF</span>
          <div class="min-w-0 flex-1">
            <p class="font-bold text-[15px] m-0 text-[var(--text)]">{{ lampiranNama }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0">Ketuk untuk membuka / unduh</p>
          </div>
        </a>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { detailPengumuman } from '@shared/services/konten.js'
import { mediaUrl } from '@shared/services/upload.js'

const route = useRoute()
const item = ref(null)
const loading = ref(true)
const err = ref('')
const imgError = ref(false)

const hasLampiran = computed(() => !!(item.value && item.value.lampiran_file))
const lampiranUrl = computed(() => (hasLampiran.value ? mediaUrl(item.value.lampiran_file) : ''))
const lampiranNama = computed(() => {
  if (!hasLampiran.value) return ''
  const p = String(item.value.lampiran_file)
  return p.split('/').pop() || 'Lampiran'
})
const isImage = computed(() => {
  if (!hasLampiran.value) return false
  const tipe = String(item.value.lampiran_tipe || '').toLowerCase()
  const path = String(item.value.lampiran_file || '').toLowerCase()
  if (tipe.startsWith('image/')) return true
  return /\.(jpe?g|png|webp|gif)$/i.test(path)
})

function formatTgl(s) {
  if (!s) return ''
  const d = new Date(String(s).replace(' ', 'T'))
  return isNaN(d) ? s : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

onMounted(async () => {
  const res = await detailPengumuman(route.params.id)
  loading.value = false
  if (!res.ok) err.value = res.error || 'Tidak ditemukan'
  else item.value = res.data
})
</script>
