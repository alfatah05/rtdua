<template>
  <div>
    <AppBackHeader title="Notifikasi" />
    <button v-if="daftar.length" type="button" class="text-[13px] font-semibold text-[var(--g)] mb-3" @click="bacaSemua">Tandai semua dibaca</button>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!daftar.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada notifikasi</p>
    <div v-else class="space-y-1">
      <button v-for="n in daftar" :key="n.id" type="button"
        class="w-full text-left px-2 py-3 border-b border-[var(--line)]"
        :class="n.dibaca ? 'opacity-60' : ''"
        @click="baca(n)">
        <p class="font-bold text-[14px] m-0">{{ n.judul }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0">{{ n.isi }}</p>
        <p class="text-[11px] text-[var(--mut)] m-0 mt-1">{{ n.dibuat_pada || n.created_at }}</p>
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listNotifikasi, bacaNotifikasi, bacaSemuaNotifikasi } from '@shared/services/notifikasi.js'
const daftar = ref([])
const loading = ref(true)
const err = ref('')
async function load() {
  loading.value = true
  const res = await listNotifikasi()
  loading.value = false
  if (!res.ok) { err.value = res.error || 'Gagal'; return }
  const d = res.data
  daftar.value = Array.isArray(d) ? d : (d?.items || [])
}
async function baca(n) {
  if (!n.dibaca) await bacaNotifikasi(n.id)
  await load()
}
async function bacaSemua() {
  await bacaSemuaNotifikasi()
  await load()
}
onMounted(load)
</script>
