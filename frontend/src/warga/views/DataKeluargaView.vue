<template>
  <div>
    <AppBackHeader title="Data keluarga">
      <template #actions>
        <button
          type="button"
          class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]"
          aria-label="Tambah anggota"
          @click="openTambah"
        >
          <PlusCircle :size="20" />
        </button>
      </template>
    </AppBackHeader>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else-if="data">
      <div class="flex flex-col items-center text-center mb-6 pt-2">
        <div
          class="w-24 h-24 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center text-3xl font-bold overflow-hidden mb-3"
        >
          <img
            v-if="fotoUrl"
            :src="fotoUrl"
            alt=""
            class="w-full h-full object-cover"
            @error="fotoUrl = ''"
          />
          <span v-else>{{ inisial(data.nama) }}</span>
        </div>
        <p class="text-xl font-extrabold m-0 leading-tight">{{ data.nama || '—' }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-1">{{ data.alamat }}</p>
      </div>

      <p class="text-[13px] font-semibold text-[var(--mut)] mb-1 px-1">Anggota keluarga</p>

      <div class="px-1">
        <div
          v-for="a in data.anggota || []"
          :key="a.id"
          class="flex items-center gap-3 py-3"
          :class="a.status && a.status !== 'aktif' ? 'opacity-60' : ''"
        >
          <div
            class="w-10 h-10 rounded-full bg-[var(--card2)] text-[var(--text)] grid place-items-center text-[12px] font-bold shrink-0"
          >
            {{ inisial(a.nama) }}
          </div>
          <p class="flex-1 min-w-0 font-semibold text-[15px] m-0 truncate">{{ a.nama }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 shrink-0 text-right max-w-[40%]">
            {{ a.hubungan || '—' }}
          </p>
        </div>
        <p
          v-if="!(data.anggota || []).length"
          class="text-[13px] text-[var(--mut)] text-center py-4"
        >Belum ada anggota</p>
      </div>
    </template>

    <!-- Sheet tambah anggota -->
    <div
      v-if="showForm"
      class="fixed inset-0 z-50 flex items-end justify-center bg-black/40"
      @click.self="closeForm"
    >
      <div
        class="w-full max-w-[560px] rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))] shadow-lg"
      >
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[17px] font-extrabold m-0">Tambah anggota</h2>
          <button
            type="button"
            class="text-[13px] font-semibold text-[var(--mut)]"
            @click="closeForm"
          >Batal</button>
        </div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nama lengkap</label>
        <input
          v-model="form.nama"
          type="text"
          class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] border-0 outline-none text-[15px] mb-3"
          placeholder="Contoh: Siti Aminah"
          autocomplete="name"
        />
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Hubungan</label>
        <input
          v-model="form.hubungan"
          type="text"
          class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] border-0 outline-none text-[15px] mb-2"
          placeholder="Contoh: istri, anak pertama"
        />
        <p class="text-[12px] text-[var(--mut)] m-0 mb-4">Hanya untuk keluarga Anda. Tidak bisa menambah keluarga baru.</p>
        <p v-if="formErr" class="text-[13px] text-red-600 text-center mb-3">{{ formErr }}</p>
        <button
          type="button"
          class="w-full min-h-12 rounded-full font-bold text-white bg-[var(--g)] active:scale-[0.98] transition disabled:opacity-60"
          :disabled="saving"
          @click="submitTambah"
        >
          {{ saving ? 'Menyimpan…' : 'Simpan anggota' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { ref, onMounted } from 'vue'
import { PlusCircle } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { portalKeluargaSaya, portalTambahAnggota } from '@shared/services/warga.js'
import { mediaUrl } from '@shared/services/upload.js'
import { waitImages } from '@shared/utils/waitImages.js'
import { useToast } from '@shared/composables/useToast.js'

const { show } = useToast()
const loading = ref(true)
const err = ref('')
const data = ref(null)
const fotoUrl = ref('')

const showForm = ref(false)
const saving = ref(false)
const formErr = ref('')
const form = ref({ nama: '', hubungan: '' })

function inisial(nama) {
  const p = String(nama || '').trim().split(/\s+/).filter(Boolean)
  if (!p.length) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await portalKeluargaSaya()
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    loading.value = false
    return
  }
  data.value = res.data
  fotoUrl.value = res.data?.foto ? mediaUrl(res.data.foto) : ''
  if (fotoUrl.value) await waitImages([fotoUrl.value])
  loading.value = false
}

function openTambah() {
  form.value = { nama: '', hubungan: '' }
  formErr.value = ''
  showForm.value = true
}

function closeForm() {
  if (saving.value) return
  showForm.value = false
}

async function submitTambah() {
  formErr.value = ''
  const nama = form.value.nama.trim()
  if (!nama) {
    formErr.value = 'Nama wajib diisi'
    return
  }
  saving.value = true
  const res = await portalTambahAnggota({
    nama,
    hubungan: form.value.hubungan.trim() || 'Anggota',
  })
  saving.value = false
  if (!res.ok) {
    formErr.value = res.error || 'Gagal menyimpan'
    return
  }
  showForm.value = false
  show('Anggota ditambahkan')
  await load()
}

onMounted(load)
</script>
