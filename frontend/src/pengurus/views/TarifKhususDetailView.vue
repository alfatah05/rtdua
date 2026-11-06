<template>
  <div class="pb-24">
    <AppBackHeader :title="detail?.nama || 'Tarif khusus'">
      <template #actions>
        <button type="button" class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]" aria-label="Ubah nama" @click="openRename"><Pencil :size="18" /></button>
        <button type="button" class="w-10 h-10 grid place-items-center rounded-full text-red-600" aria-label="Hapus tarif" @click="hapusTarif"><Trash2 :size="18" /></button>
        <button type="button" class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)]" aria-label="Tambah komponen" @click="openKomponenForm(null)"><PlusCircle :size="20" /></button>
      </template>
    </AppBackHeader>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-6 m-0">{{ err }}</p>
    <template v-else-if="detail">
      <h2 class="text-[15px] font-bold m-0 mb-2">Rincian iuran tetap</h2>
      <div class="rounded-[20px] border border-[var(--line)] bg-[var(--card)] shadow-[var(--sh)] mb-4" style="padding: 5px">
        <div class="flex items-center justify-between px-3" style="height: 30px; border-radius: 15px; background: rgba(34, 184, 99, 0.14)">
          <span class="text-[12px] font-semibold text-[var(--gm)]">Komponen</span>
          <span class="text-[12px] font-semibold text-[var(--gm)]">Biaya</span>
        </div>
        <template v-if="komponenAktif.length">
          <div v-for="c in komponenAktif" :key="c.id" class="flex items-center gap-3 py-2.5" style="padding-left: 10px; padding-right: 10px">
            <div class="w-9 h-9 rounded-full grid place-items-center shrink-0" style="background: rgba(59,130,246,.16); color: #2563EB"><Home :size="16" /></div>
            <div class="flex-1 min-w-0">
              <p class="text-[14px] font-semibold m-0 truncate">{{ c.nama }}</p>
              <div class="flex gap-2 mt-0.5">
                <button type="button" class="text-[12px] font-semibold text-[var(--g)]" @click="openKomponenForm(c)">Ubah</button>
                <button type="button" class="text-[12px] font-semibold text-red-600" @click="hapusKomponen(c)">Hapus</button>
              </div>
            </div>
            <span class="text-[14px] font-bold tabular-nums shrink-0 whitespace-nowrap">{{ rp(c.nominal) }}</span>
          </div>
          <div class="flex justify-end" style="padding: 2px 0 4px">
            <div class="inline-flex items-center justify-between gap-4 px-3 min-w-[48%]" style="height: 30px; border-radius: 15px; background: rgba(245, 158, 11, 0.16)">
              <span class="text-[12px] font-bold" style="color: #B45309">Total / bulan</span>
              <span class="text-[13px] font-extrabold tabular-nums" style="color: #D97706">{{ rp(detail.total) }}</span>
            </div>
          </div>
        </template>
        <p v-else class="text-[13px] text-[var(--mut)] text-center py-5 m-0">Belum ada komponen. Ketuk + untuk menambah.</p>
      </div>
<div class="flex items-center justify-between mb-1 px-1">
        <h2 class="text-[15px] font-bold m-0">Keluarga</h2>
        <span class="text-[13px] text-[var(--mut)]">{{ (detail.anggota || []).length }} Keluarga</span>
      </div>
      <div class="px-1 space-y-0.5 mb-3">
        <div v-for="a in detail.anggota || []" :key="a.keluarga_id" class="w-full flex flex-row items-center gap-3 px-2 py-3">
          <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm shrink-0">{{ inisial(a.nama_kepala || a.nama) }}</div>
          <div class="min-w-0 flex-1">
            <p class="font-bold text-[15px] m-0 leading-tight">{{ a.nama_kepala || a.nama || '—' }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ a.alamat || '' }}</p>
          </div>
          <button type="button" class="text-[12px] font-semibold text-red-600 shrink-0" @click="keluarkan(a)">Keluarkan</button>
        </div>
        <p v-if="!(detail.anggota || []).length" class="text-[13px] text-[var(--mut)] text-center py-6 m-0">Belum ada keluarga di tarif ini.</p>
      </div>

      <button type="button" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold active:scale-[0.98] transition mb-4" @click="openPilihKeluarga">Tambah keluarga</button>

      <label class="flex items-start gap-3 p-3 rounded-[16px] bg-[var(--card)] border border-[var(--line)]">
        <input v-model="terapkanBulanIni" type="checkbox" class="mt-1 w-4 h-4 accent-[var(--g)]" />
        <span>
          <span class="block text-[14px] font-semibold">Terapkan ke tagihan bulan ini</span>
          <span class="block text-[12px] text-[var(--mut)]">Saat menambah/mengeluarkan, update nominal tagihan kas {{ bulanIni }}.</span>
        </span>
      </label>
    </template>

    <div v-if="showRename" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40" @click.self="showRename = false">
      <div class="w-full max-w-[560px] rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[17px] font-extrabold m-0">Ubah nama tarif</h2>
          <button type="button" class="text-[13px] font-semibold text-[var(--mut)]" @click="showRename = false">Batal</button>
        </div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nama</label>
        <input v-model="renameNama" type="text" class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3" placeholder="Nama tarif khusus" />
        <p v-if="renameErr" class="text-[13px] text-red-600 text-center mb-2">{{ renameErr }}</p>
        <button type="button" class="w-full min-h-12 rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50" :disabled="renameSaving" @click="simpanNama">{{ renameSaving ? 'Menyimpan…' : 'Simpan' }}</button>
      </div>
    </div>

    <div v-if="showKomponenForm" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40" @click.self="showKomponenForm = false">
      <div class="w-full max-w-[560px] rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[17px] font-extrabold m-0">{{ form.id ? 'Ubah komponen' : 'Tambah komponen' }}</h2>
          <button type="button" class="text-[13px] font-semibold text-[var(--mut)]" @click="showKomponenForm = false">Batal</button>
        </div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nama</label>
        <input v-model="form.nama" type="text" class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3" placeholder="Keamanan, Kebersihan, …" />
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nominal / bulan</label>
        <input v-model="form.nominal" type="text" inputmode="numeric" class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3" placeholder="15000" />
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Sub pos tujuan</label>
        <select v-model="form.sub_pos_id" class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3 text-[14px]">
          <option value="">— Pilih sub pos —</option>
          <option v-for="sp in subPosList" :key="sp.id" :value="String(sp.id)">{{ sp.judul }}</option>
        </select>
        <p v-if="formErr" class="text-[13px] text-red-600 text-center mb-2">{{ formErr }}</p>
        <button type="button" class="w-full min-h-12 rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50" :disabled="formSaving" @click="simpanKomponen">{{ formSaving ? 'Menyimpan…' : 'Simpan' }}</button>
      </div>
    </div>

    <!-- Pilih keluarga: 1 scroll, floating transparan (pola RincianIuran warga) -->
    <div v-if="showPilih" class="fixed inset-0 z-50 bg-[var(--bg)] flex flex-col overflow-hidden">
      <header class="shrink-0 flex items-center bg-[var(--bg)]" :style="{ paddingTop: 'max(8px, env(safe-area-inset-top, 0px))', paddingBottom: '10px', paddingLeft: '8px', paddingRight: '16px' }">
        <button type="button" class="w-10 h-10 grid place-items-center rounded-full text-[var(--text)] shrink-0" aria-label="Kembali" @click="showPilih = false">
          <ChevronLeft :size="24" :stroke-width="2.25" />
        </button>
        <h1 class="flex-1 text-xl font-extrabold m-0 text-[var(--text)] leading-tight truncate pl-1">Pilih keluarga</h1>
      </header>

      <div class="px-4 mb-3 shrink-0">
        <div class="flex items-center gap-2.5 bg-[var(--search)] rounded-full px-[18px] h-12">
          <Search :size="18" class="text-[var(--mut)] shrink-0" />
          <input v-model="q" type="search" autocomplete="off" placeholder="Cari nama atau blok/nomor rumah" class="flex-1 min-w-0 bg-transparent outline-none text-[15px] text-[var(--text)] placeholder:text-[var(--mut)]" />
        </div>
      </div>

      <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 pb-[calc(88px+env(safe-area-inset-bottom,0px))]">
        <AppPageSpinner v-if="loadingKeluarga" />
        <template v-else>
          <p class="text-[13px] text-[var(--mut)] mb-1 px-2 m-0">{{ filteredKeluarga.length }} keluarga</p>
          <div class="px-1 space-y-0.5">
            <button
              v-for="k in filteredKeluarga"
              :key="k.id"
              type="button"
              class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left rounded-[14px] transition-colors"
              :class="k._locked ? 'opacity-50' : selectedSet.has(k.id) ? 'bg-[var(--gd)]' : 'active:bg-[var(--search)]'"
              :disabled="k._locked"
              @click="toggleSelect(k)"
            >
              <div class="w-11 h-11 rounded-full grid place-items-center font-bold text-sm shrink-0" :class="selectedSet.has(k.id) && !k._locked ? 'bg-[var(--g)] text-white' : 'bg-[var(--gd)] text-[var(--gm)]'">{{ k.inisial }}</div>
              <div class="min-w-0 flex-1">
                <p class="font-bold text-[15px] m-0 leading-tight">{{ k.nama }}</p>
                <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ k.alamat }}</p>
              </div>
              <span v-if="k._locked" class="shrink-0 text-[11px] font-bold px-2.5 py-1 rounded-full bg-[var(--search)] text-[var(--mut)]">{{ k._tarifNama }}</span>
              <div v-else-if="selectedSet.has(k.id)" class="w-5 h-5 rounded-full bg-[var(--g)] grid place-items-center shrink-0"><span class="text-white text-[11px] font-bold">✓</span></div>
            </button>
            <p v-if="!filteredKeluarga.length" class="text-[13px] text-[var(--mut)] text-center py-6 m-0">Tidak ada keluarga cocok</p>
          </div>
        </template>
      </div>

      <div class="fixed bottom-0 left-0 right-0 z-30 pointer-events-none">
        <div class="max-w-[560px] mx-auto px-4 pt-2 pb-[max(12px,env(safe-area-inset-bottom,0px))]">
          <p class="text-[12px] text-[var(--mut)] text-center m-0 mb-2">{{ selectedIds.length }} dipilih</p>
          <button type="button" class="pointer-events-auto w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-40 active:scale-[0.98] transition-transform" :disabled="savingAnggota" @click="simpanAnggota">{{ savingAnggota ? 'Menyimpan…' : 'Simpan anggota' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Search, Home, PlusCircle, Pencil, Trash2, ChevronLeft } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import {
  detailTarifIuran, ubahTarifIuran, hapusTarifKhusus, buatIuranKomponen, ubahIuranKomponen, listSubPos,
  hapusIuranKomponen, setAnggotaTarif, hapusAnggotaTarif, mapKeluargaTarif,
} from '@shared/services/keuangan.js'
import { listKeluarga } from '@shared/services/warga.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const router = useRouter()
const { success, error } = useToast()
const tarifId = computed(() => Number(route.params.id))

const loading = ref(true)
const err = ref('')
const detail = ref(null)
const terapkanBulanIni = ref(false)
const bulanIni = new Date().toISOString().slice(0, 7)

const showKomponenForm = ref(false)
const formSaving = ref(false)
const formErr = ref('')
const form = ref({ id: null, nama: '', nominal: '', sub_pos_id: '' })
const subPosList = ref([])


const showPilih = ref(false)
const q = ref('')
const allKeluarga = ref([])
const loadingKeluarga = ref(false)
const selectedIds = ref([])
const savingAnggota = ref(false)

const selectedSet = computed(() => new Set(selectedIds.value))
const komponenAktif = computed(() => (detail.value?.komponen || []).filter((x) => x.aktif !== false))

const filteredKeluarga = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return allKeluarga.value
  return allKeluarga.value.filter(
    (k) => String(k.nama || '').toLowerCase().includes(s) || String(k.alamat || '').toLowerCase().includes(s)
  )
})

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}
function toNum(v) {
  return parseInt(String(v || '').replace(/\D/g, ''), 10) || 0
}
function inisial(nama) {
  const p = String(nama || '').trim().split(/\s+/)
  if (!p[0]) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

async function loadSubPos() {
  const sp = await listSubPos()
  if (sp.ok) {
    const raw = Array.isArray(sp.data) ? sp.data : (sp.data?.items || [])
    subPosList.value = raw.filter((x) => x.aktif !== false && x.jenis === 'komponen')
  }
}

async function load() {
  loading.value = true
  err.value = ''
  loadSubPos()
  const res = await detailTarifIuran(tarifId.value)
  loading.value = false
  if (!res.ok) { err.value = res.error || 'Gagal memuat'; return }
  detail.value = res.data
  if (detail.value?.is_utama) router.replace('/pengaturan-warga')
}


const showRename = ref(false)
const renameNama = ref('')
const renameErr = ref('')
const renameSaving = ref(false)

function openRename() {
  renameNama.value = detail.value?.nama || ''
  renameErr.value = ''
  showRename.value = true
}

async function simpanNama() {
  renameErr.value = ''
  const n = renameNama.value.trim()
  if (!n) { renameErr.value = 'Nama wajib'; return }
  renameSaving.value = true
  const res = await ubahTarifIuran(tarifId.value, { nama: n })
  renameSaving.value = false
  if (!res.ok) { renameErr.value = res.error || 'Gagal simpan'; return }
  showRename.value = false
  success('Nama disimpan')
  await load()
}

async function hapusTarif() {
  if (!confirm('Hapus tarif khusus ini? Keluarga akan kembali ke tarif utama.')) return
  const res = await hapusTarifKhusus(tarifId.value)
  if (!res.ok) { error(res.error || 'Gagal hapus'); return }
  success('Tarif dihapus')
  router.replace('/pengaturan-warga')
}

function openKomponenForm(row) {
  formErr.value = ''
  form.value = row
    ? { id: row.id, nama: row.nama || '', nominal: String(row.nominal || ''), sub_pos_id: row.sub_pos_id ? String(row.sub_pos_id) : '' }
    : { id: null, nama: '', nominal: '', sub_pos_id: '' }
  showKomponenForm.value = true
}

async function simpanKomponen() {
  formErr.value = ''
  const nama = form.value.nama.trim()
  const nominal = toNum(form.value.nominal)
  if (!nama) { formErr.value = 'Nama wajib'; return }
  if (nominal < 1) { formErr.value = 'Nominal wajib'; return }
  const subPosId = Number(form.value.sub_pos_id || 0)
  if (subPosId < 1) { formErr.value = 'Pilih sub pos tujuan'; return }
  formSaving.value = true
  const res = form.value.id
    ? await ubahIuranKomponen(form.value.id, { nama, nominal, sub_pos_id: subPosId })
    : await buatIuranKomponen({ nama, nominal, tarif_id: tarifId.value, sub_pos_id: subPosId })
  formSaving.value = false
  if (!res.ok) { formErr.value = res.error || 'Gagal'; return }
  showKomponenForm.value = false
  success('Tersimpan')
  await load()
}

async function hapusKomponen(c) {
  if (!confirm('Hapus komponen "' + c.nama + '"?')) return
  const res = await hapusIuranKomponen(c.id)
  if (!res.ok) { error(res.error || 'Gagal'); return }
  success('Dihapus')
  await load()
}


async function openPilihKeluarga() {
  q.value = ''
  showPilih.value = true
  loadingKeluarga.value = true
  const [mk, lk] = await Promise.all([mapKeluargaTarif(), listKeluarga({ status: 'aktif' })])
  loadingKeluarga.value = false
  const map = mk.ok ? (mk.data?.map || {}) : {}
  const raw = lk.ok ? (Array.isArray(lk.data) ? lk.data : (lk.data?.items || [])) : []
  allKeluarga.value = raw.map((k) => {
    const id = Number(k.id || k.keluarga_id)
    const m = map[id]
    const locked = !!(m && Number(m.tarif_id) !== tarifId.value)
    const nama = k.nama || k.nama_kepala || 'Keluarga'
    return { id, nama, alamat: k.alamat || k.alamat_label || '', inisial: inisial(nama), _locked: locked, _tarifNama: m?.nama || 'Tarif lain' }
  })
  selectedIds.value = (detail.value?.anggota || []).map((a) => Number(a.keluarga_id))
}

function toggleSelect(k) {
  if (k._locked) return
  const set = new Set(selectedIds.value)
  if (set.has(k.id)) set.delete(k.id)
  else set.add(k.id)
  selectedIds.value = [...set]
}

async function simpanAnggota() {
  savingAnggota.value = true
  const res = await setAnggotaTarif(tarifId.value, {
    keluarga_ids: selectedIds.value,
    terapkan_bulan_ini: terapkanBulanIni.value,
  })
  savingAnggota.value = false
  if (!res.ok) { error(res.error || 'Gagal simpan'); return }
  success('Anggota disimpan')
  showPilih.value = false
  await load()
}

async function keluarkan(a) {
  if (!confirm('Keluarkan keluarga ini dari tarif khusus?')) return
  const res = await hapusAnggotaTarif(tarifId.value, a.keluarga_id, { terapkan_bulan_ini: terapkanBulanIni.value })
  if (!res.ok) { error(res.error || 'Gagal'); return }
  success('Dikeluarkan')
  await load()
}

onMounted(async () => {
  await load()
})
</script>
