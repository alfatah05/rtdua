<template>
  <div>
    <AppBackHeader title="Angkat pengurus" />
    <form class="space-y-4" @submit.prevent="onSave">
      <div class="relative">
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Cari anggota (nama / alamat)</label>
        <input
          v-model="cari"
          type="search"
          autocomplete="off"
          placeholder="Ketik nama (utama) atau alamat…"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
          @input="onCariInput"
          @focus="showDrop = hasil.length > 0"
        />
        <p v-if="cariLoading" class="text-[12px] text-[var(--mut)] mt-1 m-0">Mencari…</p>
        <p v-else-if="cariErr" class="text-[12px] text-red-600 mt-1 m-0">{{ cariErr }}</p>

        <div
          v-if="showDrop && hasil.length && !selected"
          class="absolute left-0 right-0 top-[calc(100%-2px)] z-30 max-h-[240px] overflow-y-auto rounded-[12px] border border-[var(--line)] bg-[var(--card)] shadow-lg"
        >
          <button
            v-for="a in hasil"
            :key="a.id"
            type="button"
            class="w-full text-left px-3 py-2.5 border-b border-[var(--line)] last:border-0 active:bg-[var(--search)]"
            @mousedown.prevent="pilihAnggota(a)"
          >
            <p class="font-bold text-[14px] m-0 leading-tight">{{ a.nama }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">{{ a.alamat }} · {{ a.hubungan || 'Anggota' }}</p>
          </button>
        </div>
        <p
          v-else-if="showDrop && !cariLoading && cari.trim().length >= 2 && !hasil.length && !selected"
          class="absolute left-0 right-0 top-[calc(100%-2px)] z-30 px-3 py-2.5 rounded-[12px] border border-[var(--line)] bg-[var(--card)] shadow-lg text-[13px] text-[var(--mut)] m-0"
        >
          Tidak ada anggota cocok
        </p>
      </div>

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
import { cariAnggota } from '@shared/services/warga.js'
import { angkatPengurus } from '@shared/services/pengurus.js'

const router = useRouter()

const cari = ref('')
const cariLoading = ref(false)
const cariErr = ref('')
const hasil = ref([])
const showDrop = ref(false)
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
  clearTimeout(debounceTimer)
  const q = cari.value.trim()
  if (q.length < 2) {
    hasil.value = []
    showDrop.value = false
    cariErr.value = ''
    cariLoading.value = false
    return
  }
  debounceTimer = setTimeout(() => doCari(q), 300)
}

async function doCari(q) {
  const seq = ++cariSeq
  cariLoading.value = true
  cariErr.value = ''
  const res = await cariAnggota(q, 40)
  if (seq !== cariSeq) return
  cariLoading.value = false
  if (!res.ok) {
    cariErr.value = res.error || 'Gagal mencari'
    hasil.value = []
    showDrop.value = false
    return
  }
  hasil.value = res.data || []
  showDrop.value = true
}

function pilihAnggota(a) {
  selected.value = a
  hasil.value = []
  showDrop.value = false
  cari.value = a.nama || ''
  if (!username.value) {
    const base = String(a.nama || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '')
      .slice(0, 16)
    if (base) username.value = base
  }
}

function batalSemua() {
  selected.value = null
  hasil.value = []
  showDrop.value = false
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
