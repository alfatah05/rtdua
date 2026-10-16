<template>
  <div>
    <AppBackHeader title="Pos kas" />

    <p class="text-[13px] text-[var(--mut)] m-0 mb-4 px-1">
      Kas bisa lebih dari satu (Kas RT, sosial, pembangunan, …). Jatah bulanan = target isi dari iuran tiap bulan (bisa diubah kapan saja).
    </p>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <div class="space-y-2 mb-6">
        <div
          v-for="p in items"
          :key="p.id"
          class="rounded-[16px] border border-[var(--line)] bg-[var(--card)] p-4"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="font-bold text-[15px] m-0">{{ p.nama }}</p>
              <p class="text-[13px] text-[var(--mut)] m-0 mt-0.5">
                Saldo {{ formatRp(p.saldo) }}
                <span v-if="!p.aktif"> · nonaktif</span>
              </p>
              <p class="text-[13px] m-0 mt-1">
                Jatah bulanan: <span class="font-semibold">{{ formatRp(p.jatah_bulanan) }}</span>
              </p>
            </div>
            <div class="flex flex-col gap-1 shrink-0">
              <button
                type="button"
                class="text-[12px] font-semibold text-[var(--gm)] min-h-[36px] px-3"
                @click="mulaiEdit(p)"
              >
                Ubah
              </button>
              <button
                v-if="p.aktif"
                type="button"
                class="text-[12px] font-semibold text-red-600 min-h-[36px] px-3"
                @click="onNonaktif(p)"
              >
                Nonaktif
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Alokasi antar pos -->
      <div
        v-if="aktifItems.length >= 2"
        class="rounded-[16px] border border-[var(--line)] bg-[var(--card)] p-4 space-y-3 mb-6"
      >
        <p class="font-bold text-[15px] m-0">Alokasi antar pos</p>
        <p class="text-[12px] text-[var(--mut)] m-0">
          Pindah dana dari satu pos ke pos lain. Total saldo RT tidak berubah. Tercatat di Aktivitas.
        </p>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Dari pos</label>
          <select
            v-model="alokasiDari"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          >
            <option :value="null" disabled>Pilih pos sumber</option>
            <option v-for="p in aktifItems" :key="'d' + p.id" :value="p.id">
              {{ p.nama }} ({{ formatRp(p.saldo) }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Ke pos</label>
          <select
            v-model="alokasiKe"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          >
            <option :value="null" disabled>Pilih pos tujuan</option>
            <option
              v-for="p in aktifItems.filter((x) => x.id !== alokasiDari)"
              :key="'k' + p.id"
              :value="p.id"
            >
              {{ p.nama }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal (Rp)</label>
          <input
            v-model="alokasiNominal"
            type="text"
            inputmode="numeric"
            placeholder="200000"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          />
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Keterangan (opsional)</label>
          <input
            v-model="alokasiKet"
            type="text"
            placeholder="Mis. isi kas sosial bulan ini"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          />
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal</label>
          <input
            v-model="alokasiTanggal"
            type="date"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          />
        </div>
        <p v-if="alokasiErr" class="text-[13px] text-red-600 m-0">{{ alokasiErr }}</p>
        <button
          type="button"
          class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-60"
          :disabled="alokasiSaving"
          @click="onAlokasi"
        >
          {{ alokasiSaving ? 'Memproses…' : 'Alokasikan' }}
        </button>
      </div>

      <div class="rounded-[16px] border border-[var(--line)] bg-[var(--card)] p-4 space-y-3">
        <p class="font-bold text-[15px] m-0">{{ editId ? 'Ubah pos' : 'Tambah pos' }}</p>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nama</label>
          <input
            v-model="formNama"
            type="text"
            placeholder="Contoh: Kas kemanusiaan"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          />
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Jatah bulanan (Rp)</label>
          <input
            v-model="formJatah"
            type="text"
            inputmode="numeric"
            placeholder="500000"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          />
        </div>
        <p v-if="formErr" class="text-[13px] text-red-600 m-0">{{ formErr }}</p>
        <div class="flex gap-2">
          <button
            v-if="editId"
            type="button"
            class="flex-1 min-h-[48px] rounded-full border border-[var(--line)] font-semibold"
            @click="batalEdit"
          >
            Batal
          </button>
          <button
            type="button"
            class="flex-1 min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-60"
            :disabled="saving"
            @click="onSimpan"
          >
            {{ saving ? 'Menyimpan…' : 'Simpan' }}
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { formatRp } from '@shared/utils/format.js'
import { useToast } from '@shared/composables/useToast.js'
import {
  listKasPos,
  buatKasPos,
  ubahKasPos,
  nonaktifKasPos,
  alokasiKasPos,
} from '@shared/services/keuangan.js'

const { success } = useToast()
const loading = ref(true)
const err = ref('')
const items = ref([])
const editId = ref(null)
const formNama = ref('')
const formJatah = ref('')
const formErr = ref('')
const saving = ref(false)

const alokasiDari = ref(null)
const alokasiKe = ref(null)
const alokasiNominal = ref('')
const alokasiKet = ref('')
const alokasiTanggal = ref(new Date().toISOString().slice(0, 10))
const alokasiErr = ref('')
const alokasiSaving = ref(false)

const aktifItems = computed(() => items.value.filter((p) => p.aktif !== false))

async function load() {
  loading.value = true
  err.value = ''
  const res = await listKasPos()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  items.value = Array.isArray(res.data) ? res.data : []
}

function mulaiEdit(p) {
  editId.value = p.id
  formNama.value = p.nama
  formJatah.value = String(p.jatah_bulanan || 0)
  formErr.value = ''
}

function batalEdit() {
  editId.value = null
  formNama.value = ''
  formJatah.value = ''
  formErr.value = ''
}

async function onSimpan() {
  formErr.value = ''
  const nama = formNama.value.trim()
  if (!nama) {
    formErr.value = 'Nama wajib.'
    return
  }
  const jatah = parseInt(String(formJatah.value).replace(/\D/g, ''), 10) || 0
  saving.value = true
  let res
  if (editId.value) {
    res = await ubahKasPos(editId.value, { nama, jatah_bulanan: jatah, aktif: true })
  } else {
    res = await buatKasPos({ nama, jatah_bulanan: jatah })
  }
  saving.value = false
  if (!res.ok) {
    formErr.value = res.error || 'Gagal menyimpan'
    return
  }
  success(editId.value ? 'Pos diperbarui' : 'Pos ditambah')
  batalEdit()
  await load()
}

async function onNonaktif(p) {
  if (!confirm(`Nonaktifkan "${p.nama}"?`)) return
  const res = await nonaktifKasPos(p.id)
  if (!res.ok) {
    err.value = res.error || 'Gagal'
    return
  }
  success('Pos dinonaktifkan')
  await load()
}

async function onAlokasi() {
  alokasiErr.value = ''
  const dari = alokasiDari.value
  const ke = alokasiKe.value
  const n = parseInt(String(alokasiNominal.value).replace(/\D/g, ''), 10) || 0
  if (!dari || !ke) {
    alokasiErr.value = 'Pilih pos sumber dan tujuan.'
    return
  }
  if (dari === ke) {
    alokasiErr.value = 'Pos sumber dan tujuan harus berbeda.'
    return
  }
  if (n < 1) {
    alokasiErr.value = 'Nominal wajib diisi.'
    return
  }
  alokasiSaving.value = true
  const res = await alokasiKasPos({
    dari_pos_id: dari,
    ke_pos_id: ke,
    nominal: n,
    keterangan: alokasiKet.value.trim() || null,
    tanggal: alokasiTanggal.value,
  })
  alokasiSaving.value = false
  if (!res.ok) {
    alokasiErr.value = res.error || 'Gagal alokasi'
    return
  }
  success('Alokasi berhasil')
  alokasiNominal.value = ''
  alokasiKet.value = ''
  await load()
}

onMounted(load)
</script>
