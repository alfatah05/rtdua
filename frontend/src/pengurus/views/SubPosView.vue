<template>
  <div class="px-4 pb-28 pt-2">
    <AppBackHeader title="Sub pos" />

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-8 m-0">{{ err }}</p>
    <template v-else>
      <div class="rounded-[16px] border border-[var(--line)] bg-[var(--card)] p-4 mb-4">
        <p class="text-[13px] text-[var(--mut)] m-0">Total belum disetor</p>
        <p class="text-[22px] font-extrabold m-0 mt-0.5">{{ formatRp(totalSaldo) }}</p>
        <p class="text-[12px] text-[var(--mut)] m-0 mt-2 leading-snug">
          Buat sub pos di sini, lalu tautkan dari komponen iuran (pengaturan tarif).
        </p>
      </div>

      <div class="flex items-center justify-between mb-2 px-0.5">
        <h2 class="text-[15px] font-bold m-0">Daftar sub pos</h2>
        <button type="button" class="text-[13px] font-bold text-[var(--g)]" @click="openForm(null)">+ Sub pos</button>
      </div>

      <div v-if="items.length" class="space-y-2">
        <div
          v-for="p in items"
          :key="p.id"
          class="rounded-[16px] border border-[var(--line)] bg-[var(--card)] p-4"
        >
          <button
            type="button"
            class="w-full text-left active:opacity-90"
            @click="$router.push('/keuangan/sub-pos/' + p.id)"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="font-bold text-[15px] m-0 leading-tight">{{ p.judul }}</p>
                <p class="text-[12px] text-[var(--mut)] m-0 mt-1">
                  {{ labelJenis(p.jenis) }}
                  <span v-if="!p.aktif"> · nonaktif</span>
                </p>
              </div>
              <div class="text-right shrink-0">
                <p class="font-extrabold text-[15px] m-0" :class="p.saldo > 0 ? 'text-[var(--g)]' : ''">
                  {{ formatRp(p.saldo) }}
                </p>
                <p class="text-[11px] text-[var(--mut)] m-0 mt-0.5">Detail →</p>
              </div>
            </div>
          </button>
          <div v-if="p.jenis !== 'denda'" class="flex gap-2 mt-3 pt-2 border-t border-[var(--line)]">
            <button type="button" class="text-[12px] font-bold text-[var(--g)]" @click="openForm(p)">Ubah</button>
            <button type="button" class="text-[12px] font-bold text-red-600" @click="hapus(p)">Hapus</button>
          </div>
        </div>
      </div>
      <EmptyState v-else title="Belum ada sub pos" description="Ketuk + Sub pos untuk membuat." />
    </template>

    <!-- Form modal sederhana -->
    <div
      v-if="showForm"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40"
      @click.self="showForm = false"
    >
      <div class="w-full max-w-md rounded-t-2xl sm:rounded-2xl bg-[var(--card)] p-4 pb-8 shadow-xl">
        <p class="text-[16px] font-bold m-0 mb-3">{{ form.id ? 'Ubah sub pos' : 'Sub pos baru' }}</p>
        <label class="block text-[13px] font-semibold mb-1">Nama</label>
        <input
          v-model="form.judul"
          type="text"
          class="w-full min-h-[44px] rounded-xl border border-[var(--line)] bg-[var(--bg)] px-3 text-[14px] mb-3"
          placeholder="Contoh: Keamanan"
        />
        <label v-if="form.id" class="flex items-center gap-2 mb-3 text-[13px]">
          <input v-model="form.aktif" type="checkbox" class="accent-[var(--g)]" />
          Aktif
        </label>
        <p v-if="formErr" class="text-[12px] text-red-600 m-0 mb-2">{{ formErr }}</p>
        <div class="flex gap-2">
          <button type="button" class="flex-1 min-h-[44px] rounded-full border border-[var(--line)] font-bold" @click="showForm = false">Batal</button>
          <button
            type="button"
            class="flex-1 min-h-[44px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-60"
            :disabled="formSaving"
            @click="simpan"
          >
            {{ formSaving ? 'Menyimpan…' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { listSubPos, buatSubPos, ubahSubPos, hapusSubPos } from '@shared/services/keuangan.js'
import { useToast } from '@shared/composables/useToast.js'

const { success, error } = useToast()
const loading = ref(true)
const err = ref('')
const items = ref([])
const showForm = ref(false)
const formSaving = ref(false)
const formErr = ref('')
const form = ref({ id: null, judul: '', aktif: true })

const totalSaldo = computed(() =>
  items.value.reduce((s, p) => s + (Number(p.saldo) || 0), 0)
)

function labelJenis(j) {
  if (j === 'komponen') return 'Komponen iuran'
  if (j === 'khusus') return 'Iuran khusus'
  if (j === 'denda') return 'Denda ronda'
  return j || '—'
}

function openForm(row) {
  formErr.value = ''
  if (row) {
    form.value = { id: row.id, judul: row.judul || '', aktif: row.aktif !== false }
  } else {
    form.value = { id: null, judul: '', aktif: true }
  }
  showForm.value = true
}

async function simpan() {
  formErr.value = ''
  const judul = String(form.value.judul || '').trim()
  if (!judul) {
    formErr.value = 'Nama wajib'
    return
  }
  formSaving.value = true
  const res = form.value.id
    ? await ubahSubPos(form.value.id, { judul, aktif: form.value.aktif })
    : await buatSubPos({ judul })
  formSaving.value = false
  if (!res.ok) {
    formErr.value = res.error || 'Gagal simpan'
    return
  }
  showForm.value = false
  success('Tersimpan')
  await load()
}

async function hapus(p) {
  if (!confirm('Hapus sub pos "' + p.judul + '"? Hanya bisa jika tidak tertaut komponen dan saldo 0.')) return
  const res = await hapusSubPos(p.id)
  if (!res.ok) {
    error(res.error || 'Gagal hapus')
    return
  }
  success('Dihapus')
  await load()
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await listSubPos()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  const data = res.data
  items.value = Array.isArray(data) ? data : (data?.items || [])
}

onMounted(load)
</script>
