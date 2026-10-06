<template>
  <div>
    <AppBackHeader title="Iuran khusus" />
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4" @click="showForm = true">
      + Buat iuran khusus
    </button>

    <div v-if="!daftar.length">
      <EmptyState title="Belum ada iuran khusus" desc="Buat iuran untuk event atau kebutuhan tertentu." />
    </div>
    <div v-else class="space-y-2">
      <div v-for="i in daftar" :key="i.id" class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
        <p class="font-bold text-[15px] m-0">{{ i.nama }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0">{{ i.nominal }} · {{ i.periode }}</p>
      </div>
    </div>

    <!-- Form inline sederhana -->
    <div v-if="showForm" class="fixed inset-0 z-50 bg-black/40 flex items-end justify-center" @click.self="showForm = false">
      <div class="w-full max-w-[560px] bg-[var(--bg)] rounded-t-[24px] p-5 pb-[calc(20px+env(safe-area-inset-bottom))]">
        <h2 class="text-lg font-bold mb-4">Buat iuran khusus</h2>
        <div class="space-y-3">
          <input v-model="form.nama" type="text" placeholder="Nama (contoh: 17 Agustus)" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
          <input v-model="form.nominal" type="text" inputmode="numeric" placeholder="Nominal per KK" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
          <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" @click="buat">Buat</button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { useToast } from '@shared/composables/useToast.js'
const { success } = useToast()
const showForm = ref(false)
const daftar = ref([])
const form = ref({ nama: '', nominal: '' })
function buat() {
  if (!form.value.nama || !form.value.nominal) return
  daftar.value.push({ id: Date.now(), nama: form.value.nama, nominal: 'Rp ' + Number(form.value.nominal).toLocaleString('id-ID'), periode: 'Oktober 2026' })
  showForm.value = false
  form.value = { nama: '', nominal: '' }
  success('Iuran khusus dibuat')
}
</script>
