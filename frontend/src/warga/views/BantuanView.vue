<template>
  <div>
    <AppBackHeader title="Bantuan" />
    <p class="text-[13px] text-[var(--mut)] mb-4">Ketuk untuk membuka WhatsApp</p>
    <div class="space-y-2">
      <a v-for="p in pengurus" :key="p.id" :href="waLink(p)" target="_blank" rel="noopener"
        class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 shadow-[var(--sh)] no-underline text-[var(--text)] active:scale-[0.98] transition">
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm flex-none">{{ inisial(p.nama) }}</div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0">{{ p.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ p.jabatan }}</p>
        </div>
        <MessageCircle :size="20" class="text-[#22C55E]" />
      </a>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { MessageCircle } from 'lucide-vue-next'
import { useAuth } from '@shared/composables/useAuth.js'
import { getBantuan } from '@shared/services/struktur.js'
const { user } = useAuth()
const pengurus = ref([])
function inisial(nama) {
  return String(nama || '?').split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()
}
onMounted(async () => {
  const res = await getBantuan()
  if (res.ok) pengurus.value = res.data || []
})
function waLink(p) {
  const hp = String(p.nomor_hp || p.hp || '').replace(/\D/g, '')
  const nama = user.value?.nama || 'Warga'
  const text = encodeURIComponent(`Halo ${p.nama}, saya ${nama} dari RT.`)
  return `https://wa.me/${hp}?text=${text}`
}
</script>
