<template>
  <div>
    <AppBackHeader title="Bantuan" />
    <p class="text-[13px] text-[var(--mut)] mb-4">Ketuk untuk membuka WhatsApp</p>
    <div class="space-y-2">
      <a
        v-for="p in pengurus"
        :key="p.id"
        :href="waLink(p)"
        target="_blank"
        rel="noopener"
        class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 shadow-[var(--sh)] no-underline text-[var(--text)] active:scale-[0.98] transition"
      >
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm flex-none">
          {{ p.inisial }}
        </div>
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
import { MessageCircle } from 'lucide-vue-next'
import { useAuth } from '@shared/composables/useAuth.js'

const { user } = useAuth()

const pengurus = [
  { id: 1, nama: 'Budi Santoso', jabatan: 'Ketua RT', inisial: 'BS', hp: '6281234567890' },
  { id: 2, nama: 'Ani Wijaya', jabatan: 'Bendahara', inisial: 'AW', hp: '6281234567891' },
  { id: 3, nama: 'Rina Marlina', jabatan: 'Sekretaris', inisial: 'RM', hp: '6281234567892' },
]

function waLink(p) {
  const text = encodeURIComponent(`Halo ${p.nama}, saya ${user.value?.nama || 'warga'} dari ${user.value?.username || 'rumah'}.`)
  return `https://wa.me/${p.hp}?text=${text}`
}
</script>
