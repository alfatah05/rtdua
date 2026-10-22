<template>
  <div>
    <AppBackHeader title="Angkat pengurus" />
    <form class="space-y-4" @submit.prevent="onSave">
      <!-- Cari anggota -->
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Cari anggota (nama / alamat)</label>
        <input
          v-model="cari"
          type="search"
          autocomplete="off"
          placeholder="Ketik nama atau alamat rumah…"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          @input="onCariInput"
        />
        <p v-if="cariLoading" class="text-[12px] text-[var(--mut)] mt-1.5 m-0">Mencari…</p>
        <p v-else-if="cariErr" class="text-[12px] text-red-600 mt-1.5 m-0">{{ cariErr }}</p>
      </div>

      <!-- Hasil keluarga -->
      <div v-if="hasilKeluarga.length && !selected" class="bg-[var(--card)] border border-[var(--line)] rounded-[14px] overflow-hidden">
        <button
          v-for="k in hasilKeluarga"
          :key="k.id"
          type="button"
          class="w-full text-left px-3 py-3 border-b border-[var(--line)] last:border-0 active:bg-[var(--search)]"
          @click="pilihKeluarga(k)"
        >
          <p class="font-bold text-[14px] m-0 leading-tight">{{ k.alamat || k.alamat_label || '—' }}</p>
          <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">{{ k.nama || 'Keluarga' }}</p>
        </button>
      </div>
      <p v-else-if="cari.trim().length >= 2 && !cariLoading && !hasilKeluarga.length && !selected" class="text-[13px] text-[var(--mut)] text-center py-2 m-0">
        Tidak ada keluarga cocok
      </p>

      <!-- Daftar anggota setelah pilih keluarga -->
      <div v-if="loadingAnggota" class="text-[13px] text-[var(--mut)] text-center py-2">Memuat anggota…</div>
      <div v-else-if="anggotaList.length && !selected" class="space-y-1">
        <p class="text-[13px] font-semibold text-[var(--mut)] m-0">Pilih anggota di {{ selectedKeluargaLabel }}</p>
        <button
          v-for="a in anggotaList"
          :key="a.id"
          type="button"
          class="w-full text-left px-3 py-3 rounded-[12px] bg-[var(--search)] active:opacity-80"
          @click="pilihAnggota(a)"
        >
          <p class="font-bold text-[14px] m-0">{{ a.nama }}</p>
          <p class="text-[12px] text-[var(--mut)] m-0">{{ a.hubungan || 'Anggota' }}</p>
        </button>
        <button type="button" class="text-[13px] font-semibold text-[var(--g)] mt-1" @click="batalPilihKeluarga">← Ganti keluarga</button>
      </div>

      <!-- Anggota terpilih -->
      <div v-if="selected" class="bg-[var(--gd)] rounded-[14px] px-4 py-3 flex items-start justify-between gap-2">
        <div class="min-w-0">
          <p class="text-[12px] text-[var(--mut)] m-0">Anggota terpilih</p>
          <p class="font-bold text-[15px] m-0 leading-tight">{{ selected.nama }}</p>
          <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">{{ selected.alamat }} · {{ selected.hubungan || 'Anggota' }}</p>
        </div>
        <button type="button" class="text-[13px] font-semibold text-[var(--g)] shrink-0" @click="batalSemua">Ganti</button>
      </div>

      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Username</label>
        <input v-model="username" type="text" autocomplete="off" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Password (min 8)</label>
        <input v-model="password" type="text" autocomplete="new-password" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Jabatan</label>
        <input v-model="jabatan" type="text" placeholder="Bendahara / Sekretaris / …" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nomor HP (opsional)</label>
        <input v-model="nomorHp" type="tel" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <label class="flex items-center gap-3 min-h-[44px]">
        <input v-model="tampilBantuan" type="checkbox" class="w-5 h-5 accent-[var(--g)]" />
        <span class="text-[15px] font-semibold">Tampil di menu Bantuan</span>
      </label>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-60" :disabled="saving">
        {{ saving ? 'Menyimpan…' : 'Angkat pengurus' }}
      </button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
      <p class="text-[12px] text-[var(--mut)] text-center m-0">Hanya akun <b>ketua</b> yang bisa mengangkat pengurus.</p>
    </form>
  </div>
</template>
<script setup>
import { ref, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listKeluarga, getKeluarga } from '@shared/services/warga.js'
import { angkatPengurus } from '@shared/services/pengurus.js'

const router = useRouter()

const cari = ref('')
const cariLoading = ref(false)
const cariErr = ref('')
const hasilKeluarga = ref([])
const selectedKeluargaLabel = ref('')
const anggotaList = ref([])
const loadingAnggota = ref(false)
const selected = ref(null)

const username = ref('')
const password = ref('')
const jabatan = ref('Pengurus')
const nomorHp = ref('')
const tampilBantuan = ref(true)
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)

let debounceTimer = null
let cariSeq = 0

function onCariInput() {
  selected.value = null
  anggotaList.value = []
  selectedKeluargaLabel.value = ''
  clearTimeout(debounceTimer)
  const q = cari.value.trim()
  if (q.length < 2) {
    hasilKeluarga.value = []
    cariErr.value = ''
    cariLoading.value = false
    return
  }
  debounceTimer = setTimeout(() => doCari(q), 320)
}

async function doCari(q) {
  const seq = ++cariSeq
  cariLoading.value = true
  cariErr.value = ''
  const res = await listKeluarga({ status: 'aktif', q })
  if (seq !== cariSeq) return
  cariLoading.value = false
  if (!res.ok) {
    cariErr.value = res.error || 'Gagal mencari'
    hasilKeluarga.value = []
    return
  }
  hasilKeluarga.value = (res.data || []).slice(0, 30)
}

async function pilihKeluarga(k) {
  hasilKeluarga.value = []
  selectedKeluargaLabel.value = k.alamat || k.alamat_label || k.nama || '—'
  loadingAnggota.value = true
  anggotaList.value = []
  const d = await getKeluarga(k.id)
  loadingAnggota.value = false
  if (!d.ok) {
    cariErr.value = d.error || 'Gagal memuat anggota'
    return
  }
  const alamat = d.data?.alamat || d.data?.alamat_label || selectedKeluargaLabel.value
  selectedKeluargaLabel.value = alamat
  anggotaList.value = (d.data?.anggota || [])
    .filter((a) => String(a.status || '').toLowerCase() === 'aktif')
    .map((a) => ({
      id: a.id,
      nama: a.nama,
      hubungan: a.hubungan,
      alamat,
    }))
  if (!anggotaList.value.length) {
    cariErr.value = 'Tidak ada anggota aktif di keluarga ini'
  }
}

function pilihAnggota(a) {
  selected.value = a
  anggotaList.value = []
  cari.value = ''
  // saran username dari nama (huruf kecil, tanpa spasi)
  if (!username.value) {
    const base = String(a.nama || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '')
      .slice(0, 16)
    if (base) username.value = base
  }
}

function batalPilihKeluarga() {
  anggotaList.value = []
  selectedKeluargaLabel.value = ''
  cariErr.value = ''
  if (cari.value.trim().length >= 2) doCari(cari.value.trim())
}

function batalSemua() {
  selected.value = null
  anggotaList.value = []
  selectedKeluargaLabel.value = ''
  hasilKeluarga.value = []
  cari.value = ''
  cariErr.value = ''
}

async function onSave() {
  msg.value = ''
  if (!selected.value?.id) {
    msgOk.value = false
    msg.value = 'Pilih anggota dulu (cari nama/alamat)'
    return
  }
  if (!username.value.trim() || password.value.length < 8) {
    msgOk.value = false
    msg.value = 'Username dan password (min 8) wajib'
    return
  }
  saving.value = true
  const res = await angkatPengurus({
    warga_id: Number(selected.value.id),
    username: username.value.trim().toLowerCase(),
    password: password.value,
    jabatan: jabatan.value || 'Pengurus',
    nomor_hp: nomorHp.value || null,
    tampil_di_bantuan: tampilBantuan.value,
    role: 'pengurus',
  })
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Pengurus diangkat' : (res.error || 'Gagal')
  if (res.ok) setTimeout(() => router.replace('/kelola-pengurus'), 700)
}

onBeforeUnmount(() => clearTimeout(debounceTimer))
</script>
