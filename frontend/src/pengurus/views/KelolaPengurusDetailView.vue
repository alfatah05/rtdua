<template>
  <div>
    <AppBackHeader title="Kelola pengurus" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <form v-else class="space-y-4" @submit.prevent="onSave">
      <p class="font-bold text-[16px] m-0">{{ item.nama }}</p>
      <p class="text-[13px] text-[var(--mut)] m-0">@{{ item.username }} · {{ item.role }}</p>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Jabatan</label>
        <input v-model="jabatan" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nomor HP</label>
        <input v-model="nomorHp" type="tel" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Password baru (opsional, min 8)</label>
        <input v-model="password" type="text" autocomplete="new-password" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <label class="flex items-center gap-3 min-h-[44px]">
        <input v-model="tampilBantuan" type="checkbox" class="w-5 h-5 accent-[var(--g)]" />
        <span class="text-[15px] font-semibold">Tampil di Bantuan</span>
      </label>
      <label class="flex items-center gap-3 min-h-[44px]">
        <input v-model="aktif" type="checkbox" class="w-5 h-5 accent-[var(--g)]" />
        <span class="text-[15px] font-semibold">Aktif</span>
      </label>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listPengurus, updatePengurus } from '@shared/services/pengurus.js'

const route = useRoute()
const loading = ref(true)
const err = ref('')
const item = ref({})
const jabatan = ref('')
const nomorHp = ref('')
const password = ref('')
const tampilBantuan = ref(false)
const aktif = ref(true)
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)

onMounted(async () => {
  const res = await listPengurus()
  loading.value = false
  if (!res.ok) { err.value = res.error || 'Gagal'; return }
  const found = (res.data || []).find((p) => String(p.id) === String(route.params.id))
  if (!found) { err.value = 'Pengurus tidak ditemukan'; return }
  item.value = found
  jabatan.value = found.jabatan || ''
  nomorHp.value = found.nomor_hp || ''
  tampilBantuan.value = !!found.tampil_di_bantuan
  aktif.value = found.aktif !== false
})

async function onSave() {
  saving.value = true
  msg.value = ''
  const payload = {
    jabatan: jabatan.value,
    nomor_hp: nomorHp.value,
    tampil_di_bantuan: tampilBantuan.value,
    aktif: aktif.value,
  }
  if (password.value.length >= 8) payload.password = password.value
  const res = await updatePengurus(route.params.id, payload)
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Disimpan' : (res.error || 'Gagal')
}
</script>
