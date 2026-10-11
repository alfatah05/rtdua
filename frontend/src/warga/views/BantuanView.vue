<template>
  <div>
    <AppBackHeader title="Bantuan" />

    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!list.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada kontak bantuan</p>

    <div v-else class="px-1">
      <div
        v-for="p in list"
        :key="p.id"
        class="flex items-center gap-3 py-3.5"
      >
        <div
          class="w-11 h-11 rounded-full bg-[var(--card2)] text-[var(--text)] grid place-items-center text-[13px] font-bold overflow-hidden shrink-0"
        >
          <img
            v-if="p.foto"
            :src="mediaUrl(p.foto)"
            alt=""
            class="w-full h-full object-cover"
          />
          <span v-else>{{ inisial(p.nama) }}</span>
        </div>

        <div class="flex-1 min-w-0">
          <p class="font-bold text-[15px] m-0 leading-snug truncate">{{ p.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5 truncate">{{ p.jabatan || 'Pengurus' }}</p>
        </div>

        <a
          :href="waLink(p.nomor_hp)"
          class="inline-flex items-center gap-1.5 min-h-[40px] px-3.5 rounded-full bg-[var(--g)] text-white text-[13px] font-bold no-underline shrink-0 active:scale-95 transition-transform"
        >
          <MessageCircle :size="16" />
          Hubungi
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { MessageCircle } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getBantuan } from '@shared/services/struktur.js'
import { useAuth } from '@shared/composables/useAuth.js'
import { mediaUrl } from '@shared/services/upload.js'

const { user } = useAuth()
const list = ref([])
const loading = ref(true)
const err = ref('')

function inisial(nama) {
  const p = String(nama || '').trim().split(/\s+/).filter(Boolean)
  if (!p.length) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

function waLink(hp) {
  const n = String(hp || '').replace(/\D/g, '').replace(/^0/, '')
  if (!n) return '#'
  const nama = user.value?.nama || user.value?.username || 'warga'
  const alamat = user.value?.alamat || user.value?.username || ''
  const text = encodeURIComponent('Assalamualaikum, saya ' + nama + (alamat ? ' (' + alamat + ')' : '') + '.')
  return 'https://wa.me/62' + n + '?text=' + text
}

onMounted(async () => {
  const res = await getBantuan()
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else list.value = res.data || []
})
</script>
