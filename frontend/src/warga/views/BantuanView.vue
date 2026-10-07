<template>
  <div>
    <AppBackHeader title="Bantuan" />
    <p class="text-[13px] text-[var(--mut)] mb-4">Hubungi pengurus yang tampil di bawah.</p>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!list.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada kontak bantuan</p>
    <div v-else class="space-y-2">
      <a v-for="p in list" :key="p.id"
        :href="waLink(p.nomor_hp)"
        class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 no-underline text-[var(--text)]">
        <div class="min-w-0 flex-1">
          <p class="font-bold m-0">{{ p.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0">{{ p.jabatan }} · {{ p.nomor_hp || '—' }}</p>
        </div>
      </a>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getBantuan } from '@shared/services/struktur.js'
const list = ref([])
const loading = ref(true)
const err = ref('')
function waLink(hp) {
  const n = String(hp || '').replace(/\D/g, '').replace(/^0/, '')
  return n ? 'https://wa.me/62' + n : '#'
}
onMounted(async () => {
  const res = await getBantuan()
  loading.value = false
  if (!res.ok) err.value = res.error || 'Gagal'
  else list.value = res.data || []
})
</script>
