<template>
  <div>
    <AppBackHeader title="Angkat pengurus" />
    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Pilih anggota warga</label>
        <select v-model="wargaId" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
          <option value="">— pilih —</option>
          <option v-for="a in anggota" :key="a.id" :value="a.id">{{ a.label }}</option>
        </select>
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
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Angkat pengurus' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listKeluarga, getKeluarga } from '@shared/services/warga.js'
import { angkatPengurus } from '@shared/services/pengurus.js'

const router = useRouter()
const anggota = ref([])
const wargaId = ref('')
const username = ref('')
const password = ref('')
const jabatan = ref('Pengurus')
const nomorHp = ref('')
const tampilBantuan = ref(true)
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)

onMounted(async () => {
  const res = await listKeluarga({ status: 'aktif' })
  if (!res.ok) return
  const out = []
  for (const k of (res.data || []).slice(0, 80)) {
    const d = await getKeluarga(k.id)
    if (!d.ok) continue
    for (const a of d.data?.anggota || []) {
      if (String(a.status).toLowerCase() !== 'aktif') continue
      out.push({
        id: a.id,
        label: `${k.alamat || ''} · ${a.nama} (${a.hubungan || 'anggota'})`,
      })
    }
  }
  anggota.value = out
})

async function onSave() {
  msg.value = ''
  if (!wargaId.value || !username.value.trim() || password.value.length < 8) {
    msgOk.value = false
    msg.value = 'Warga, username, dan password (min 8) wajib'
    return
  }
  saving.value = true
  const res = await angkatPengurus({
    warga_id: Number(wargaId.value),
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
  if (res.ok) setTimeout(() => router.replace('/kelola-pengurus'), 600)
}
</script>
