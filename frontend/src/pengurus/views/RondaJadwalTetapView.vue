<template>
  <div>
    <AppBackHeader title="Jadwal tetap" />
    <p class="text-[13px] text-[var(--mut)] mb-4">Pilih hari ronda RT (mis. hanya Sabtu). Hari yang tidak diatur tidak ikut isi otomatis / generate. Kosongkan keluarga lalu simpan untuk menghapus hari.</p>
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <div v-else class="space-y-3 mb-6">
      <div v-for="h in 7" :key="h" class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4">
        <div class="flex justify-between items-center mb-2">
          <p class="font-bold m-0">{{ hariLabel[h - 1] }}</p>
          <button type="button" class="text-[13px] font-semibold text-[var(--g)]" @click="editHari(h - 1)">Atur</button>
        </div>
        <p class="text-[13px] text-[var(--mut)] m-0">
          <template v-if="byHari[h - 1]">
            {{ jam(byHari[h - 1].jam_mulai) }}–{{ jam(byHari[h - 1].jam_selesai) }} ·
            {{ (byHari[h - 1].keluarga || []).map(k => k.alamat).join(', ') || 'belum ada keluarga' }}
          </template>
          <template v-else>Belum diatur (tidak ada ronda)</template>
        </p>
      </div>
    </div>
    <div v-if="showForm" class="fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4" @click.self="showForm = false">
      <div class="bg-[var(--bg)] rounded-[20px] p-5 w-full max-w-md max-h-[85vh] overflow-y-auto">
        <p class="font-bold text-[16px] m-0 mb-3">{{ hariLabel[editHariIdx] }}</p>
        <div class="flex gap-2 mb-3">
          <input v-model="formJamMulai" type="time" class="flex-1 min-h-[44px] px-3 rounded-[12px] bg-[var(--search)]" />
          <input v-model="formJamSelesai" type="time" class="flex-1 min-h-[44px] px-3 rounded-[12px] bg-[var(--search)]" />
        </div>
        <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Pilih keluarga</p>
        <div class="max-h-48 overflow-y-auto space-y-1 mb-4">
          <label v-for="k in semuaKeluarga" :key="k.id" class="flex items-center gap-2 min-h-[40px] px-1">
            <input type="checkbox" class="w-4 h-4 accent-[var(--g)]" :value="k.id" v-model="formKelIds" />
            <span class="text-[14px]">{{ k.alamat }} · {{ k.nama }}</span>
          </label>
        </div>
        <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold mb-2" :disabled="saving" @click="simpan">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
        <button type="button" class="w-full min-h-[44px] rounded-full bg-[var(--card2)] font-semibold" @click="showForm = false">Batal</button>
        <p v-if="formMsg" class="text-[13px] text-center mt-2" :class="formOk ? 'text-[var(--g)]' : 'text-red-600'">{{ formMsg }}</p>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listJadwalTetap, simpanJadwalTetap } from '@shared/services/ronda.js'
import { listKeluarga } from '@shared/services/warga.js'

const hariLabel = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
const loading = ref(true)
const err = ref('')
const jadwal = ref([])
const semuaKeluarga = ref([])
const showForm = ref(false)
const editHariIdx = ref(0)
const formJamMulai = ref('21:00')
const formJamSelesai = ref('00:00')
const formKelIds = ref([])
const saving = ref(false)
const formMsg = ref('')
const formOk = ref(true)

const byHari = computed(() => {
  const m = {}
  for (const j of jadwal.value) m[j.hari] = j
  return m
})

function jam(j) {
  return j ? String(j).slice(0, 5) : '—'
}

function editHari(h) {
  editHariIdx.value = h
  const ex = byHari.value[h]
  formJamMulai.value = ex?.jam_mulai ? String(ex.jam_mulai).slice(0, 5) : '21:00'
  formJamSelesai.value = ex?.jam_selesai ? String(ex.jam_selesai).slice(0, 5) : '00:00'
  formKelIds.value = (ex?.keluarga || []).map((k) => k.keluarga_id)
  formMsg.value = ''
  showForm.value = true
}

async function load() {
  loading.value = true
  const [j, k] = await Promise.all([listJadwalTetap(), listKeluarga({ status: 'aktif' })])
  loading.value = false
  if (!j.ok) { err.value = j.error || 'Gagal'; return }
  jadwal.value = Array.isArray(j.data) ? j.data : []
  if (k.ok) {
    semuaKeluarga.value = (k.data || []).map((x) => ({
      id: x.id,
      nama: x.nama,
      alamat: x.alamat,
    }))
  }
}

async function simpan() {
  saving.value = true
  formMsg.value = ''
  const res = await simpanJadwalTetap({
    hari: editHariIdx.value,
    jam_mulai: formJamMulai.value + ':00',
    jam_selesai: formJamSelesai.value + ':00',
    keluarga_ids: formKelIds.value,
  })
  saving.value = false
  formOk.value = !!res.ok
  formMsg.value = res.ok ? 'Disimpan' : (res.error || 'Gagal')
  if (res.ok) {
    showForm.value = false
    await load()
  }
}

onMounted(load)
</script>
