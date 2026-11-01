<template>
  <div class="min-h-full pb-[calc(24px+env(safe-area-inset-bottom,0px))]">
    <AppBackHeader :title="detail?.nama || 'Tarif khusus'">
      <template #right>
        <button
          type="button"
          class="w-9 h-9 rounded-full flex items-center justify-center active:scale-95"
          style="background: rgba(34,197,94,.15); color: var(--g)"
          @click="openKomponenForm(null)"
        >
          <PlusCircle :size="20" />
        </button>
      </template>
    </AppBackHeader>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center px-4 py-6">{{ err }}</p>
    <template v-else-if="detail">
      <div class="px-4 pt-3 space-y-4">
        <!-- Total -->
        <div
          class="flex items-center justify-between px-4"
          style="height: 44px; border-radius: 16px; background: rgba(245, 158, 11, 0.16)"
        >
          <span class="text-[13px] font-bold" style="color: #B45309">Total / bulan</span>
          <span class="text-[15px] font-extrabold tabular-nums" style="color: #D97706">{{ rp(detail.total) }}</span>
        </div>

        <!-- Komponen -->
        <section>
          <h2 class="text-[13px] font-bold text-[var(--mut)] uppercase tracking-wide m-0 mb-2">Komponen</h2>
          <div class="rounded-[16px] bg-[var(--card)] border border-[var(--line)] overflow-hidden">
            <div
              v-for="c in (detail.komponen || []).filter((x) => x.aktif !== false)"
              :key="c.id"
              class="flex items-center gap-3 px-3 py-2.5 border-b border-[var(--line)] last:border-0"
            >
              <div class="flex-1 min-w-0">
                <p class="text-[14px] font-semibold m-0 truncate">{{ c.nama }}</p>
                <div class="flex gap-2 mt-0.5">
                  <button type="button" class="text-[12px] font-semibold text-[var(--g)]" @click="openKomponenForm(c)">Ubah</button>
                  <button type="button" class="text-[12px] font-semibold text-red-600" @click="hapusKomponen(c)">Hapus</button>
                </div>
              </div>
              <span class="text-[14px] font-bold tabular-nums shrink-0">{{ rp(c.nominal) }}</span>
            </div>
            <p v-if="!(detail.komponen || []).filter((x) => x.aktif !== false).length" class="text-[13px] text-[var(--mut)] text-center py-5 m-0">
              Belum ada komponen. Ketuk + atau salin dari utama.
            </p>
          </div>
          <button
            v-if="utamaKomponen.length"
            type="button"
            class="mt-2 w-full min-h-[40px] rounded-full border border-[var(--line)] text-[13px] font-semibold text-[var(--g)]"
            @click="showSalin = true"
          >
            Salin komponen dari tarif utama
          </button>
        </section>

        <!-- Anggota -->
        <section>
          <div class="flex items-center justify-between mb-2">
            <h2 class="text-[13px] font-bold text-[var(--mut)] uppercase tracking-wide m-0">Keluarga</h2>
            <span class="text-[12px] font-semibold text-[var(--mut)]">{{ (detail.anggota || []).length }} KK</span>
          </div>
          <div class="rounded-[16px] bg-[var(--card)] border border-[var(--line)] overflow-hidden mb-2">
            <div
              v-for="a in detail.anggota || []"
              :key="a.keluarga_id"
              class="flex items-center gap-3 px-3 py-2.5 border-b border-[var(--line)] last:border-0"
            >
              <div class="flex-1 min-w-0">
                <p class="text-[14px] font-semibold m-0 truncate">{{ a.nama_kepala || '—' }}</p>
                <p class="text-[12px] text-[var(--mut)] m-0 truncate">{{ [a.blok, a.alamat].filter(Boolean).join(' · ') }}</p>
              </div>
              <button type="button" class="text-[12px] font-semibold text-red-600 shrink-0" @click="keluarkan(a)">Keluarkan</button>
            </div>
            <p v-if="!(detail.anggota || []).length" class="text-[13px] text-[var(--mut)] text-center py-5 m-0">
              Belum ada keluarga di tarif ini.
            </p>
          </div>
          <button
            type="button"
            class="w-full min-h-[44px] rounded-full bg-[var(--g)] text-white font-bold active:scale-[0.98]"
            @click="openPilihKeluarga"
          >
            Tambah keluarga
          </button>
        </section>

        <label class="flex items-start gap-3 p-3 rounded-[16px] bg-[var(--card)] border border-[var(--line)]">
          <input v-model="terapkanBulanIni" type="checkbox" class="mt-1 w-4 h-4 accent-[var(--g)]" />
          <span>
            <span class="block text-[14px] font-semibold">Terapkan ke tagihan bulan ini</span>
            <span class="block text-[12px] text-[var(--mut)]">Saat menambah/mengeluarkan keluarga, update nominal tagihan kas {{ bulanIni }}.</span>
          </span>
        </label>
      </div>
    </template>

    <!-- Form komponen -->
    <div v-if="showKomponenForm" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40" @click.self="showKomponenForm = false">
      <div class="w-full max-w-[560px] rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[17px] font-extrabold m-0">{{ form.id ? 'Ubah komponen' : 'Tambah komponen' }}</h2>
          <button type="button" class="text-[13px] font-semibold text-[var(--mut)]" @click="showKomponenForm = false">Batal</button>
        </div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nama</label>
        <input v-model="form.nama" type="text" class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3" placeholder="Keamanan, …" />
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">Nominal / bulan</label>
        <input v-model="form.nominal" type="text" inputmode="numeric" class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] outline-none mb-3" placeholder="15000" />
        <p v-if="formErr" class="text-[13px] text-red-600 text-center mb-2">{{ formErr }}</p>
        <button type="button" class="w-full min-h-12 rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50" :disabled="formSaving" @click="simpanKomponen">
          {{ formSaving ? 'Menyimpan…' : 'Simpan' }}
        </button>
      </div>
    </div>

    <!-- Salin dari utama -->
    <div v-if="showSalin" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40" @click.self="showSalin = false">
      <div class="w-full max-w-[560px] max-h-[70vh] flex flex-col rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-[17px] font-extrabold m-0">Salin dari utama</h2>
          <button type="button" class="text-[13px] font-semibold text-[var(--mut)]" @click="showSalin = false">Tutup</button>
        </div>
        <div class="overflow-y-auto flex-1 space-y-2">
          <button
            v-for="c in utamaKomponen"
            :key="c.id"
            type="button"
            class="w-full flex items-center justify-between px-3 py-2.5 rounded-[14px] bg-[var(--card)] border border-[var(--line)] text-left active:scale-[0.99]"
            @click="salinKomponen(c)"
          >
            <span class="text-[14px] font-semibold">{{ c.nama }}</span>
            <span class="text-[13px] font-bold tabular-nums text-[var(--mut)]">{{ rp(c.nominal) }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Pilih keluarga -->
    <div v-if="showPilih" class="fixed inset-0 z-50 flex flex-col bg-[var(--bg)]">
      <AppBackHeader title="Pilih keluarga" @back="showPilih = false">
        <template #right>
          <span class="text-[12px] font-semibold text-[var(--mut)]">{{ selectedIds.length }} dipilih</span>
        </template>
      </AppBackHeader>
      <div class="px-4 pt-2 pb-2">
        <input
          v-model="q"
          type="search"
          class="w-full min-h-11 px-4 rounded-full bg-[var(--search)] outline-none text-[14px]"
          placeholder="Cari nama / alamat…"
        />
      </div>
      <div class="flex-1 overflow-y-auto px-4 pb-4">
        <button
          v-for="k in filteredKeluarga"
          :key="k.id"
          type="button"
          class="w-full flex items-center gap-3 px-3 py-2.5 mb-1.5 rounded-[14px] border text-left"
          :class="k._locked ? 'opacity-55 border-[var(--line)] bg-[var(--card)]' : selectedSet.has(k.id) ? 'border-[var(--g)] bg-[rgba(34,197,94,.08)]' : 'border-[var(--line)] bg-[var(--card)]'"
          :disabled="k._locked"
          @click="toggleSelect(k)"
        >
          <div class="flex-1 min-w-0">
            <p class="text-[14px] font-semibold m-0 truncate">{{ k.nama_kepala || k.kepala || '—' }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0 truncate">{{ k.alamat_lengkap || k.alamat || '' }}</p>
          </div>
          <span
            v-if="k._locked"
            class="shrink-0 text-[11px] font-bold px-2 py-0.5 rounded-full"
            style="background: rgba(245,158,11,.18); color: #B45309"
          >{{ k._tarifNama }}</span>
          <span
            v-else-if="selectedSet.has(k.id)"
            class="shrink-0 w-5 h-5 rounded-full bg-[var(--g)] text-white text-[12px] flex items-center justify-center"
          >✓</span>
        </button>
      </div>
      <div class="p-4 border-t border-[var(--line)] pb-[calc(16px+env(safe-area-inset-bottom,0px))]">
        <button
          type="button"
          class="w-full min-h-12 rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50"
          :disabled="savingAnggota"
          @click="simpanAnggota"
        >
          {{ savingAnggota ? 'Menyimpan…' : 'Simpan anggota' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { PlusCircle } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import {
  detailTarifIuran,
  buatIuranKomponen,
  ubahIuranKomponen,
  hapusIuranKomponen,
  listIuranKomponen,
  setAnggotaTarif,
  hapusAnggotaTarif,
  mapKeluargaTarif,
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
const form = ref({ id: null, nama: '', nominal: '' })

const showSalin = ref(false)
const utamaKomponen = ref([])

const showPilih = ref(false)
const q = ref('')
const allKeluarga = ref([])
const mapTarif = ref({})
const selectedIds = ref([])
const savingAnggota = ref(false)

const selectedSet = computed(() => new Set(selectedIds.value))

const filteredKeluarga = computed(() => {
  const s = q.value.trim().toLowerCase()
  let list = allKeluarga.value
  if (s) {
    list = list.filter((k) => {
      const blob = [k.nama_kepala, k.kepala, k.alamat, k.alamat_lengkap, k.blok].filter(Boolean).join(' ').toLowerCase()
      return blob.includes(s)
    })
  }
  return list
})

function rp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}
function toNum(v) {
  return parseInt(String(v || '').replace(/\D/g, ''), 10) || 0
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await detailTarifIuran(tarifId.value)
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  detail.value = res.data
  if (detail.value?.is_utama) {
    router.replace('/pengaturan-warga')
  }
}

async function loadUtamaKomponen() {
  const res = await listIuranKomponen()
  if (res.ok) {
    utamaKomponen.value = Array.isArray(res.data?.items) ? res.data.items.filter((i) => i.aktif !== false) : []
  }
}

function openKomponenForm(row) {
  formErr.value = ''
  form.value = row
    ? { id: row.id, nama: row.nama || '', nominal: String(row.nominal || '') }
    : { id: null, nama: '', nominal: '' }
  showKomponenForm.value = true
}

async function simpanKomponen() {
  formErr.value = ''
  const nama = form.value.nama.trim()
  const nominal = toNum(form.value.nominal)
  if (!nama) { formErr.value = 'Nama wajib'; return }
  if (nominal < 1) { formErr.value = 'Nominal wajib'; return }
  formSaving.value = true
  const res = form.value.id
    ? await ubahIuranKomponen(form.value.id, { nama, nominal })
    : await buatIuranKomponen({ nama, nominal, tarif_id: tarifId.value })
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

async function salinKomponen(c) {
  const res = await buatIuranKomponen({
    nama: c.nama,
    nominal: c.nominal,
    tarif_id: tarifId.value,
  })
  if (!res.ok) { error(res.error || 'Gagal salin'); return }
  success('Disalin: ' + c.nama)
  showSalin.value = false
  await load()
}

async function openPilihKeluarga() {
  q.value = ''
  const [mk, lk] = await Promise.all([mapKeluargaTarif(), listKeluarga({ status: 'aktif' })])
  mapTarif.value = mk.ok ? (mk.data?.map || {}) : {}
  const items = lk.ok ? (Array.isArray(lk.data) ? lk.data : (lk.data?.items || [])) : []
  allKeluarga.value = items.map((k) => {
    const id = Number(k.id || k.keluarga_id)
    const m = mapTarif.value[id]
    const locked = !!(m && m.tarif_id !== tarifId.value)
    return {
      ...k,
      id,
      _locked: locked,
      _tarifNama: m?.nama || 'Tarif lain',
    }
  })
  selectedIds.value = (detail.value?.anggota || []).map((a) => a.keluarga_id)
  showPilih.value = true
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
  const res = await hapusAnggotaTarif(tarifId.value, a.keluarga_id, {
    terapkan_bulan_ini: terapkanBulanIni.value,
  })
  if (!res.ok) { error(res.error || 'Gagal'); return }
  success('Dikeluarkan')
  await load()
}

onMounted(async () => {
  await load()
  await loadUtamaKomponen()
})
</script>
