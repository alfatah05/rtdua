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
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { formatRp } from '@shared/utils/format.js'
import { useToast } from '@shared/composables/useToast.js'
import { listKasPos, buatKasPos, ubahKasPos, nonaktifKasPos } from '@shared/services/keuangan.js'

const { success } = useToast()
const loading = ref(true)
const err = ref('')
const items = ref([])
const editId = ref(null)
const formNama = ref('')
const formJatah = ref('')
const formErr = ref('')
const saving = ref(false)

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

onMounted(load)
</script>
