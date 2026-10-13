<template>
  <div>
    <AppBackHeader title="Rekening pembayaran">
      <template #actions>
        <button
          type="button"
          class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]"
          aria-label="Tambah"
          @click="openForm()"
        >
          <PlusCircle :size="20" />
        </button>
      </template>
    </AppBackHeader>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <p class="text-[13px] text-[var(--mut)] m-0 mb-4">
        Atur QRIS, e-wallet, dan rekening bank yang bisa dipilih warga saat bayar iuran.
      </p>

      <div v-for="g in groups" :key="g.jenis" class="mb-5">
        <h2 class="text-[13px] font-bold text-[var(--mut)] mb-2 px-1">{{ g.label }}</h2>
        <div v-if="g.items.length" class="space-y-2">
          <div
            v-for="r in g.items"
            :key="r.id"
            class="rounded-[16px] border border-[var(--line)] bg-[var(--card)] p-4"
          >
            <div class="flex items-start gap-3">
              <div class="flex-1 min-w-0">
                <p class="font-bold m-0 text-[15px]">{{ r.nama }}</p>
                <p v-if="r.jenis !== 'qris'" class="text-[13px] text-[var(--mut)] m-0 mt-0.5">
                  {{ r.nomor || '—' }}
                  <span v-if="r.atas_nama"> · {{ r.atas_nama }}</span>
                </p>
                <p v-else class="text-[13px] text-[var(--mut)] m-0 mt-0.5">
                  {{ r.qris_file ? 'QRIS terpasang' : 'Belum ada gambar QRIS' }}
                </p>
                <span
                  class="inline-block mt-2 text-[11px] font-bold px-2 py-0.5 rounded-full"
                  :class="r.aktif ? 'bg-[var(--gd)] text-[var(--gm)]' : 'bg-[var(--card2)] text-[var(--mut)]'"
                >{{ r.aktif ? 'Aktif' : 'Nonaktif' }}</span>
              </div>
              <div class="flex flex-col gap-1 shrink-0">
                <button type="button" class="text-[13px] font-semibold text-[var(--g)]" @click="openForm(r)">Ubah</button>
                <button type="button" class="text-[13px] font-semibold text-red-600" @click="hapus(r)">Hapus</button>
              </div>
            </div>
          </div>
        </div>
        <p v-else class="text-[13px] text-[var(--mut)] px-1">Belum ada. Ketuk + untuk menambah.</p>
      </div>
    </template>

    <div
      v-if="showForm"
      class="fixed inset-0 z-50 flex items-end justify-center bg-black/40"
      @click.self="showForm = false"
    >
      <div class="w-full max-w-[560px] rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))] max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[17px] font-extrabold m-0">{{ form.id ? 'Ubah metode' : 'Tambah metode' }}</h2>
          <button type="button" class="text-[13px] font-semibold text-[var(--mut)]" @click="showForm = false">Batal</button>
        </div>

        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Jenis</label>
        <div class="flex gap-2 mb-3">
          <button
            v-for="j in jenisOpts"
            :key="j.value"
            type="button"
            class="flex-1 min-h-10 rounded-full text-[13px] font-bold border"
            :class="form.jenis === j.value ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="form.jenis = j.value"
          >{{ j.label }}</button>
        </div>

        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nama</label>
        <input v-model="form.nama" type="text" class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3" :placeholder="form.jenis === 'qris' ? 'QRIS RT' : 'BCA / OVO / GoPay'" />

        <template v-if="form.jenis !== 'qris'">
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nomor / VA</label>
          <input v-model="form.nomor" type="text" class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3" placeholder="Nomor rekening atau nomor HP" />
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Atas nama</label>
          <input v-model="form.atas_nama" type="text" class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3" placeholder="Nama pemilik" />
        </template>

        <template v-else>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Gambar QRIS</label>
          <div v-if="qrisPreview" class="h-40 rounded-[16px] overflow-hidden mb-2 bg-[var(--search)]">
            <img :src="qrisPreview" class="w-full h-full object-contain" alt="" />
          </div>
          <label class="inline-flex min-h-11 px-4 rounded-full bg-[var(--card2)] font-semibold items-center cursor-pointer mb-3">
            {{ uploading ? 'Mengunggah…' : 'Pilih / ganti gambar' }}
            <input type="file" accept="image/*" class="hidden" :disabled="uploading" @change="onQris" />
          </label>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Atas nama (opsional)</label>
          <input v-model="form.atas_nama" type="text" class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3" />
        </template>

        <label class="flex items-center gap-2 mb-4">
          <input v-model="form.aktif" type="checkbox" class="accent-[var(--g)] w-5 h-5" />
          <span class="text-[14px] font-semibold">Aktif (tampil di app warga)</span>
        </label>

        <p v-if="formErr" class="text-[13px] text-red-600 text-center mb-3">{{ formErr }}</p>
        <button
          type="button"
          class="w-full min-h-12 rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-60"
          :disabled="saving || uploading"
          @click="simpan"
        >{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, computed, onMounted } from 'vue'
import { PlusCircle } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listRekening, createRekening, updateRekening, deleteRekening } from '@shared/services/keuangan.js'
import { uploadGambar, mediaUrl } from '@shared/services/upload.js'
import { useToast } from '@shared/composables/useToast.js'

const { show } = useToast()
const loading = ref(true)
const err = ref('')
const items = ref([])
const showForm = ref(false)
const saving = ref(false)
const uploading = ref(false)
const formErr = ref('')
const form = ref(emptyForm())
const qrisPreview = ref('')

const jenisOpts = [
  { value: 'qris', label: 'QRIS' },
  { value: 'ewallet', label: 'E-wallet' },
  { value: 'bank', label: 'Bank' },
]

function emptyForm() {
  return { id: null, jenis: 'qris', nama: '', nomor: '', atas_nama: '', qris_file: '', aktif: true }
}

const groups = computed(() => [
  { jenis: 'qris', label: 'QRIS', items: items.value.filter((i) => i.jenis === 'qris') },
  { jenis: 'ewallet', label: 'E-wallet', items: items.value.filter((i) => i.jenis === 'ewallet') },
  { jenis: 'bank', label: 'Bank', items: items.value.filter((i) => i.jenis === 'bank') },
])

async function load() {
  loading.value = true
  const res = await listRekening()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    return
  }
  items.value = Array.isArray(res.data) ? res.data : []
}

function openForm(row) {
  formErr.value = ''
  if (row) {
    form.value = {
      id: row.id,
      jenis: row.jenis,
      nama: row.nama || '',
      nomor: row.nomor || '',
      atas_nama: row.atas_nama || '',
      qris_file: row.qris_file || '',
      aktif: !!row.aktif,
    }
    qrisPreview.value = row.qris_file ? mediaUrl(row.qris_file) : ''
  } else {
    form.value = emptyForm()
    qrisPreview.value = ''
  }
  showForm.value = true
}

async function onQris(e) {
  const file = e.target.files?.[0]
  e.target.value = ''
  if (!file) return
  uploading.value = true
  const res = await uploadGambar(file, 'bukti')
  uploading.value = false
  if (!res.ok) {
    formErr.value = res.error || 'Upload gagal'
    return
  }
  form.value.qris_file = res.data?.path || res.data?.file || ''
  qrisPreview.value = res.data?.url || mediaUrl(form.value.qris_file)
}

async function simpan() {
  formErr.value = ''
  if (!form.value.nama.trim()) {
    formErr.value = 'Nama wajib'
    return
  }
  saving.value = true
  const payload = {
    jenis: form.value.jenis,
    nama: form.value.nama.trim(),
    nomor: form.value.nomor || null,
    atas_nama: form.value.atas_nama || null,
    qris_file: form.value.qris_file || null,
    aktif: form.value.aktif,
  }
  const res = form.value.id
    ? await updateRekening(form.value.id, payload)
    : await createRekening(payload)
  saving.value = false
  if (!res.ok) {
    formErr.value = res.error || 'Gagal simpan'
    return
  }
  showForm.value = false
  show('Tersimpan')
  await load()
}

async function hapus(r) {
  if (!confirm('Hapus ' + r.nama + '?')) return
  const res = await deleteRekening(r.id)
  if (!res.ok) {
    show(res.error || 'Gagal hapus')
    return
  }
  show('Dihapus')
  await load()
}

onMounted(load)
</script>
