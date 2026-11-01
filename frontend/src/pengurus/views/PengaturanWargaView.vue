<template>
  <div>
    <AppBackHeader title="Tarif iuran">
      <template #actions>
        <button
          type="button"
          class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]"
          aria-label="Tambah komponen"
          @click="openForm()"
        >
          <PlusCircle :size="20" />
        </button>
      </template>
    </AppBackHeader>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <h2 class="text-[15px] font-bold m-0 mb-2">Rincian iuran tetap</h2>
      <div
        class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] shadow-[var(--sh)] mb-4"
        style="padding: 5px"
      >
        <div
          class="flex items-center justify-between px-3"
          style="height: 30px; border-radius: 15px; background: rgba(34, 184, 99, 0.14)"
        >
          <span class="text-[12px] font-semibold text-[var(--gm)]">Komponen</span>
          <span class="text-[12px] font-semibold text-[var(--gm)]">Biaya</span>
        </div>

        <template v-if="items.length">
          <div
            v-for="c in items"
            :key="c.id"
            class="flex items-center gap-3 py-2.5"
            style="padding-left: 10px; padding-right: 10px"
          >
            <div
              class="w-9 h-9 rounded-full grid place-items-center shrink-0"
              style="background: rgba(59,130,246,.16); color: #2563EB"
            >
              <Home :size="16" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[14px] font-semibold m-0 truncate">{{ c.nama }}</p>
              <div class="flex gap-2 mt-0.5">
                <button type="button" class="text-[12px] font-semibold text-[var(--g)]" @click="openForm(c)">Ubah</button>
                <button type="button" class="text-[12px] font-semibold text-red-600" @click="hapus(c)">Hapus</button>
              </div>
            </div>
            <span class="text-[14px] font-bold tabular-nums shrink-0 whitespace-nowrap">
              {{ rp(c.nominal) }}
            </span>
          </div>

          <div class="flex justify-end" style="padding: 2px 0 4px">
            <div
              class="inline-flex items-center justify-between gap-4 px-3 min-w-[48%]"
              style="height: 30px; border-radius: 15px; background: rgba(245, 158, 11, 0.16)"
            >
              <span class="text-[12px] font-bold" style="color: #B45309">Total / bulan</span>
              <span class="text-[13px] font-extrabold tabular-nums" style="color: #D97706">
                {{ rp(total) }}
              </span>
            </div>
          </div>
        </template>
        <p v-else class="text-[13px] text-[var(--mut)] text-center py-5 m-0">
          Belum ada komponen. Ketuk + untuk menambah.
        </p>
      </div>

      <!-- Tarif khusus — card baris seperti menu beranda warga -->
      <div class="mt-5 mb-3">
        <div class="flex items-center justify-between mb-2 px-0.5">
          <h2 class="text-[15px] font-bold m-0">Tarif khusus</h2>
          <button type="button" class="text-[13px] font-bold text-[var(--g)]" @click="buatTarif">+ Tarif baru</button>
        </div>
        <div class="space-y-2.5">
          <button
            v-for="t in tarifKhusus"
            :key="t.id"
            type="button"
            class="menu-row-tarif w-full text-left relative overflow-hidden touch-manipulation"
            :style="{ background: MENU.iuranKhusus }"
            @click="$router.push('/tarif-khusus/' + t.id)"
          >
            <div class="relative z-10 flex items-center gap-3 px-3.5 min-h-16">
              <div class="w-11 h-11 rounded-[0.875rem] grid place-items-center shrink-0 bg-white/20 text-white">
                <Users :size="20" />
              </div>
              <div class="flex-1 min-w-0 py-2.5">
                <p class="text-sm font-bold m-0 leading-tight text-white truncate">{{ t.nama }}</p>
                <p class="text-xs font-semibold m-0 mt-0.5 leading-tight text-white/85 truncate">
                  {{ t.jumlah_keluarga || 0 }} Keluarga
                </p>
              </div>
              <div class="shrink-0 text-right pr-1">
                <p class="text-sm font-extrabold m-0 tabular-nums text-white leading-tight">{{ rp(t.total) }}</p>
              </div>
              <ChevronRight :size="16" class="shrink-0 text-white/70" />
            </div>
          </button>
        </div>
        <p v-if="!tarifKhusus.length" class="text-[13px] text-[var(--mut)] text-center py-6 m-0">
          Belum ada tarif khusus.
        </p>
      </div>

      <label class="flex items-start gap-3 p-3 rounded-[16px] bg-[var(--card)] border border-[var(--line)] mb-3">
        <input v-model="terapkanBulanIni" type="checkbox" class="mt-1 w-4 h-4 accent-[var(--g)]" />
        <span>
          <span class="block text-[14px] font-semibold">Terapkan total ke tagihan bulan ini</span>
          <span class="block text-[12px] text-[var(--mut)]">
            Ubah nominal tagihan kas periode {{ bulanIni }} sesuai total di atas (untuk KK yang sudah punya tagihan).
          </span>
        </span>
      </label>

      <button
        type="button"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50 active:scale-[0.98] transition"
        :disabled="saving || !items.length"
        @click="terapkan"
      >
        {{ saving ? 'Menyimpan…' : 'Simpan & terapkan' }}
      </button>
      <p v-if="msg" class="text-[13px] text-center mt-3" :class="msgOk ? 'text-[var(--gm)]' : 'text-red-600'">{{ msg }}</p>
    </template>

    <div
      v-if="showForm"
      class="fixed inset-0 z-50 flex items-end justify-center bg-black/40"
      @click.self="showForm = false"
    >
      <div class="w-full max-w-[560px] rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[17px] font-extrabold m-0">{{ form.id ? 'Ubah komponen' : 'Tambah komponen' }}</h2>
          <button type="button" class="text-[13px] font-semibold text-[var(--mut)]" @click="showForm = false">Batal</button>
        </div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nama</label>
        <input
          v-model="form.nama"
          type="text"
          class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3"
          placeholder="Keamanan, Kebersihan, …"
        />
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nominal / bulan</label>
        <input
          v-model="form.nominal"
          type="text"
          inputmode="numeric"
          class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3"
          placeholder="15000"
        />
        <p v-if="formErr" class="text-[13px] text-red-600 text-center mb-2">{{ formErr }}</p>
        <button
          type="button"
          class="w-full min-h-12 rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50"
          :disabled="formSaving"
          @click="simpan"
        >
          {{ formSaving ? 'Menyimpan…' : 'Simpan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { PlusCircle, Home } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import {
  listIuranKomponen,
  buatIuranKomponen,
  ubahIuranKomponen,
  hapusIuranKomponen,
  terapkanKomponenBulanIni,
  listTarifIuran,
  buatTarifKhusus,
} from '@shared/services/keuangan.js'
import { useToast } from '@shared/composables/useToast.js'
import { MENU } from '@shared/constants/menuColors.js'
import { useRouter } from 'vue-router'

const router = useRouter()
const { success, error } = useToast()
const loading = ref(true)
const err = ref('')
const items = ref([])
const total = ref(0)
const tarifKhusus = ref([])
const terapkanBulanIni = ref(false)
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)
const showForm = ref(false)
const formSaving = ref(false)
const formErr = ref('')
const form = ref({ id: null, nama: '', nominal: '' })
const bulanIni = new Date().toISOString().slice(0, 7)

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}

function toNum(v) {
  return parseInt(String(v || '').replace(/\D/g, ''), 10) || 0
}

async function load() {
  loading.value = true
  err.value = ''
  const [res, tr] = await Promise.all([listIuranKomponen(), listTarifIuran()])
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  const d = res.data || {}
  items.value = Array.isArray(d.items) ? d.items.filter((i) => i.aktif !== false) : []
  total.value = Number(d.total ?? items.value.reduce((s, i) => s + Number(i.nominal || 0), 0))
  if (tr.ok) {
    const all = Array.isArray(tr.data?.items) ? tr.data.items : []
    tarifKhusus.value = all.filter((x) => !x.is_utama)
  }
}

async function buatTarif() {
  const nama = prompt('Nama tarif khusus (contoh: Iuran setengah)')
  if (nama == null) return
  const n = String(nama).trim()
  if (!n) return
  const res = await buatTarifKhusus({ nama: n })
  if (!res.ok) {
    error(res.error || 'Gagal buat tarif')
    return
  }
  success('Tarif dibuat')
  const id = res.data?.id
  if (id) router.push('/tarif-khusus/' + id)
  else await load()
}

function openForm(row) {
  formErr.value = ''
  if (row) {
    form.value = { id: row.id, nama: row.nama || '', nominal: String(row.nominal || '') }
  } else {
    form.value = { id: null, nama: '', nominal: '' }
  }
  showForm.value = true
}

async function simpan() {
  formErr.value = ''
  const nama = form.value.nama.trim()
  const nominal = toNum(form.value.nominal)
  if (!nama) {
    formErr.value = 'Nama wajib'
    return
  }
  if (nominal < 1) {
    formErr.value = 'Nominal wajib'
    return
  }
  formSaving.value = true
  const res = form.value.id
    ? await ubahIuranKomponen(form.value.id, { nama, nominal })
    : await buatIuranKomponen({ nama, nominal })
  formSaving.value = false
  if (!res.ok) {
    formErr.value = res.error || 'Gagal simpan'
    return
  }
  showForm.value = false
  success('Tersimpan')
  await load()
}

async function hapus(c) {
  if (!confirm('Hapus komponen "' + c.nama + '"?')) return
  const res = await hapusIuranKomponen(c.id)
  if (!res.ok) {
    error(res.error || 'Gagal hapus')
    return
  }
  success('Dihapus')
  await load()
}

async function terapkan() {
  if (!terapkanBulanIni.value) {
    msgOk.value = true
    msg.value = 'Total sudah tersimpan otomatis saat menambah/ubah komponen. Centang opsi di atas jika ingin ubah tagihan bulan ini.'
    return
  }
  saving.value = true
  msg.value = ''
  const res = await terapkanKomponenBulanIni()
  saving.value = false
  msgOk.value = !!res.ok
  if (res.ok) {
    const n = res.data?.keluarga_terdampak ?? 0
    msg.value = `Total Rp ${Number(res.data?.total || total.value).toLocaleString('id-ID')} diterapkan ke ${n} tagihan kas bulan ini.`
    success(msg.value)
    await load()
  } else {
    msg.value = res.error || 'Gagal menerapkan'
    error(msg.value)
  }
}

onMounted(load)
</script>

<style scoped>
.menu-row-tarif {
  border-radius: 1.125rem;
  min-height: 4rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}
</style>
