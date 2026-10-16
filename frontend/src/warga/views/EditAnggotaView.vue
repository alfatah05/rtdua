<template>
  <div>
    <AppBackHeader title="Edit anggota" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <p class="text-[13px] text-[var(--mut)] mb-4 m-0">
        Ketuk nama anggota untuk membuka form. Kepala keluarga: hubungan tidak bisa diubah.
      </p>

      <div class="space-y-2 pb-8">
        <div
          v-for="a in anggota"
          :key="a.id"
          class="rounded-[16px] border border-[var(--line)] overflow-hidden bg-[var(--card)]"
        >
          <button
            type="button"
            class="w-full flex items-center gap-3 px-4 py-3 text-left"
            @click="toggle(a.id)"
          >
            <div
              class="w-10 h-10 rounded-full bg-[var(--card2)] text-[var(--text)] grid place-items-center text-[12px] font-bold shrink-0"
            >
              {{ inisial(forms[a.id]?.nama || a.nama) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-[15px] m-0 truncate">{{ forms[a.id]?.nama || a.nama }}</p>
              <p class="text-[12px] text-[var(--mut)] m-0">{{ forms[a.id]?.hubungan || a.hubungan }}</p>
            </div>
            <ChevronDown
              :size="18"
              class="text-[var(--mut)] shrink-0 transition-transform"
              :class="openId === a.id ? 'rotate-180' : ''"
            />
          </button>

          <div v-if="openId === a.id" class="px-4 pb-4 space-y-3 border-t border-[var(--line)] pt-3">
            <UiInput v-model="forms[a.id].nama" label="Nama lengkap" />
            <div>
              <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Hubungan</label>
              <select
                v-model="forms[a.id].hubungan"
                class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
                :disabled="isKepala(a)"
              >
                <option v-if="isKepala(a)" value="Kepala keluarga">Kepala keluarga</option>
                <option v-for="h in hubunganOpts" :key="h" :value="h">{{ h }}</option>
              </select>
              <p v-if="isKepala(a)" class="text-[11px] text-[var(--mut)] m-0 mt-1">Hubungan kepala dikunci.</p>
            </div>
            <UiInput
              v-model="forms[a.id].nik"
              :label="a.punya_nik ? 'NIK baru (opsional, ganti)' : 'NIK (opsional)'"
              placeholder="16 digit"
              inputmode="numeric"
              maxlength="16"
            />
            <div>
              <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Jenis kelamin</label>
              <select
                v-model="forms[a.id].jenis_kelamin"
                class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
              >
                <option value="">—</option>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>
            <UiInput v-model="forms[a.id].tempat_lahir" label="Tempat lahir (opsional)" />
            <div>
              <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal lahir (opsional)</label>
              <input
                v-model="forms[a.id].tanggal_lahir"
                type="date"
                class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
              />
            </div>
            <UiInput v-model="forms[a.id].agama" label="Agama (opsional)" />
            <UiInput v-model="forms[a.id].pekerjaan" label="Pekerjaan (opsional)" />

            <p v-if="saveErr[a.id]" class="text-[13px] text-red-600 text-center m-0">{{ saveErr[a.id] }}</p>
            <button
              type="button"
              class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-60"
              :disabled="!!saving[a.id]"
              @click="simpan(a.id)"
            >
              {{ saving[a.id] ? 'Menyimpan…' : 'Simpan perubahan' }}
            </button>
          </div>
        </div>

        <p v-if="!anggota.length" class="text-[13px] text-[var(--mut)] text-center py-6">Belum ada anggota</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import UiInput from '@shared/components/UiInput.vue'
import { portalKeluargaSaya, portalUpdateAnggota } from '@shared/services/warga.js'
import { useToast } from '@shared/composables/useToast.js'

const { success } = useToast()
const loading = ref(true)
const err = ref('')
const anggota = ref([])
const openId = ref(null)
const forms = reactive({})
const saving = reactive({})
const saveErr = reactive({})

const hubunganOpts = [
  'Istri', 'Suami', 'Anak', 'Menantu', 'Cucu', 'Orang tua', 'Mertua', 'Famili lain', 'Anggota',
]

function inisial(nama) {
  const p = String(nama || '').trim().split(/\s+/).filter(Boolean)
  if (!p.length) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

function isKepala(a) {
  return String(a.hubungan || '').toLowerCase().includes('kepala')
}

function toggle(id) {
  openId.value = openId.value === id ? null : id
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
  const list = (res.data?.anggota || []).filter((a) => a.status === 'aktif' || !a.status)
  anggota.value = list
  for (const a of list) {
    forms[a.id] = {
      nama: a.nama || '',
      hubungan: a.hubungan || 'Anggota',
      nik: '',
      jenis_kelamin: a.jenis_kelamin || '',
      tempat_lahir: a.tempat_lahir || '',
      tanggal_lahir: a.tanggal_lahir || '',
      agama: a.agama || '',
      pekerjaan: a.pekerjaan || '',
    }
  }
  loading.value = false
}

async function simpan(id) {
  saveErr[id] = ''
  const f = forms[id]
  if (!f || !String(f.nama).trim()) {
    saveErr[id] = 'Nama wajib diisi'
    return
  }
  const nik = String(f.nik || '').replace(/\D/g, '')
  if (nik && nik.length !== 16) {
    saveErr[id] = 'NIK harus 16 digit jika diisi'
    return
  }
  saving[id] = true
  const payload = {
    nama: String(f.nama).trim(),
    hubungan: f.hubungan || 'Anggota',
    jenis_kelamin: f.jenis_kelamin || null,
    tempat_lahir: f.tempat_lahir || null,
    tanggal_lahir: f.tanggal_lahir || null,
    agama: f.agama || null,
    pekerjaan: f.pekerjaan || null,
  }
  if (nik) payload.nik = nik
  const res = await portalUpdateAnggota(id, payload)
  saving[id] = false
  if (!res.ok) {
    saveErr[id] = res.error || 'Gagal menyimpan'
    return
  }
  success('Perubahan disimpan')
  await load()
  openId.value = id
}

onMounted(load)
</script>
