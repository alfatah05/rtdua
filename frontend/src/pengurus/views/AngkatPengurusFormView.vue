<template>
  <div>
    <AppBackHeader title="Buat akun pengurus" />

    <div v-if="!wargaId" class="text-center py-8">
      <p class="text-[13px] text-[var(--mut)] m-0 mb-3">Belum pilih warga</p>
      <button type="button" class="text-[14px] font-semibold text-[var(--g)]" @click="$router.replace('/kelola-pengurus/angkat')">
        ← Kembali pilih warga
      </button>
    </div>

    <form v-else class="space-y-4" @submit.prevent="onSave">
      <div class="bg-[var(--gd)] rounded-[14px] px-4 py-3">
        <p class="text-[12px] text-[var(--mut)] m-0">Anggota dipilih</p>
        <p class="font-bold text-[16px] m-0 leading-tight mt-0.5">{{ nama }}</p>
        <p class="text-[12px] text-[var(--mut)] m-0 mt-0.5">{{ alamat }}{{ hubungan ? ' · ' + hubungan : '' }}</p>
        <button type="button" class="text-[13px] font-semibold text-[var(--g)] mt-2" @click="$router.replace('/kelola-pengurus/angkat')">
          Ganti warga
        </button>
      </div>

      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Username</label>
        <input
          v-model="username"
          type="text"
          autocomplete="off"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
        />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Password (min 8)</label>
        <input
          v-model="password"
          type="text"
          autocomplete="new-password"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
        />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Jabatan</label>
        <input
          v-model="jabatan"
          type="text"
          placeholder="Bendahara / Sekretaris / …"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
        />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nomor HP (opsional)</label>
        <input
          v-model="nomorHp"
          type="tel"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
        />
      </div>
      <label class="flex items-center gap-3 min-h-[44px]">
        <input v-model="tampilBantuan" type="checkbox" class="w-5 h-5 accent-[var(--g)]" />
        <span class="text-[15px] font-semibold">Tampil di menu Bantuan</span>
      </label>

      <button
        type="submit"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-60"
        :disabled="saving"
      >{{ saving ? 'Menyimpan…' : 'Angkat pengurus' }}</button>

      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
      <p class="text-[12px] text-[var(--mut)] text-center m-0">Hanya akun <b>ketua</b> yang bisa mengangkat pengurus.</p>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { angkatPengurus } from '@shared/services/pengurus.js'

const route = useRoute()
const router = useRouter()

const wargaId = ref(0)
const nama = ref('')
const alamat = ref('')
const hubungan = ref('')

const username = ref('')
const password = ref('')
const jabatan = ref('Pengurus')
const nomorHp = ref('')
const tampilBantuan = ref(true)
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)

onMounted(() => {
  wargaId.value = Number(route.query.warga_id || 0)
  nama.value = String(route.query.nama || '')
  alamat.value = String(route.query.alamat || '')
  hubungan.value = String(route.query.hubungan || '')

  if (nama.value && !username.value) {
    const base = nama.value
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '')
      .slice(0, 16)
    if (base) username.value = base
  }
})

async function onSave() {
  msg.value = ''
  if (!wargaId.value) {
    msgOk.value = false
    msg.value = 'Pilih warga dulu'
    return
  }
  if (!username.value.trim() || password.value.length < 8) {
    msgOk.value = false
    msg.value = 'Username dan password (min 8) wajib'
    return
  }
  saving.value = true
  const res = await angkatPengurus({
    warga_id: wargaId.value,
    username: username.value.trim().toLowerCase(),
    password: password.value,
    jabatan: jabatan.value || 'Pengurus',
    nomor_hp: nomorHp.value || null,
    tampil_di_bantuan: tampilBantuan.value,
    role: 'pengurus',
  })
  saving.value = false
  if (!res.ok) {
    msgOk.value = false
    msg.value = res.error || 'Gagal mengangkat'
    return
  }
  msgOk.value = true
  msg.value = 'Berhasil diangkat'
  setTimeout(() => router.replace('/kelola-pengurus'), 700)
}
</script>
