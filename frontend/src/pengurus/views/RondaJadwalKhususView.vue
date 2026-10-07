<template>
  <div>
    <AppBackHeader title="Jadwal khusus" />
    <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-4" @click="openForm">+ Tambah jadwal khusus</button>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <p v-else-if="!list.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada jadwal khusus</p>
    <div v-else class="space-y-2">
      <div v-for="j in list" :key="j.id" class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
        <p class="font-bold m-0">{{ j.tanggal }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0">{{ jam(j.jam_mulai) }}–{{ jam(j.jam_selesai) }} · {{ j.keterangan || '—' }}</p>
      </div>
    </div>
    <div v-if="showForm" class="fixed inset-0 z-50 bg-black/40 flex items-end justify-center p-4" @click.self="showForm = false">
      <div class="bg-[var(--bg)] rounded-[20px] p-5 w-full max-w-md">
        <p class="font-bold mb-3">Jadwal khusus</p>
        <input v-model="form.tanggal" type="date" class="w-full min-h-[44px] px-3 rounded-[12px] bg-[var(--search)] mb-2" />
        <div class="flex gap-2 mb-2">
          <input v-model="form.jam_mulai" type="time" class="flex-1 min-h-[44px] px-3 rounded-[12px] bg-[var(--search)]" />
          <input v-model="form.jam_selesai" type="time" class="flex-1 min-h-[44px] px-3 rounded-[12px] bg-[var(--search)]" />
        </div>
        <input v-model="form.keterangan" type="text" placeholder="Keterangan" class="w-full min-h-[44px] px-3 rounded-[12px] bg-[var(--search)] mb-3" />
        <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-2" :disabled="saving" @click="simpan">Simpan</button>
        <button type="button" class="w-full min-h-[44px] rounded-full bg-[var(--card2)] font-semibold" @click="showForm = false">Batal</button>
        <p v-if="formMsg" class="text-[13px] text-center mt-2" :class="formOk ? 'text-[var(--g)]' : 'text-red-600'">{{ formMsg }}</p>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listJadwalKhusus, simpanJadwalKhusus } from '@shared/services/ronda.js'
const loading = ref(true)
const err = ref('')
const list = ref([])
const showForm = ref(false)
const saving = ref(false)
const formMsg = ref('')
const formOk = ref(true)
const form = ref({ tanggal: '', jam_mulai: '21:00', jam_selesai: '00:00', keterangan: '', keluarga_ids: [] })
function jam(j) { return j ? String(j).slice(0, 5) : '—' }
function openForm() {
  form.value = { tanggal: '', jam_mulai: '21:00', jam_selesai: '00:00', keterangan: '', keluarga_ids: [] }
  formMsg.value = ''
  showForm.value = true
}
async function load() {
  loading.value = true
  const j = await listJadwalKhusus()
  loading.value = false
  if (!j.ok) { err.value = j.error || 'Gagal'; return }
  list.value = Array.isArray(j.data) ? j.data : []
}
async function simpan() {
  if (!form.value.tanggal) { formOk.value = false; formMsg.value = 'Tanggal wajib'; return }
  saving.value = true
  const res = await simpanJadwalKhusus({
    tanggal: form.value.tanggal,
    jam_mulai: form.value.jam_mulai + ':00',
    jam_selesai: form.value.jam_selesai + ':00',
    keterangan: form.value.keterangan,
    keluarga_ids: form.value.keluarga_ids,
  })
  saving.value = false
  formOk.value = !!res.ok
  formMsg.value = res.ok ? 'Disimpan' : (res.error || 'Gagal')
  if (res.ok) { showForm.value = false; await load() }
}
onMounted(load)
</script>
