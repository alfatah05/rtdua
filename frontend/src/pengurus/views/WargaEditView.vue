<template>
  <div>
    <AppBackHeader title="Edit keluarga" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <form v-else class="space-y-4 pb-8" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Blok</label>
        <select v-model="blokId" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
          <option v-for="b in blokList" :key="b.id" :value="b.id">{{ b.nama }}</option>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nomor</label>
          <input v-model="nomor" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Akhiran</label>
          <input v-model="akhiran" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
        </div>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Telepon</label>
        <input v-model="telepon" type="tel" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Alamat tambahan</label>
        <input v-model="alamat" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>

      <h2 class="text-[15px] font-bold pt-2 m-0">Nama anggota</h2>
      <p class="text-[12px] text-[var(--mut)] m-0">Ubah nama kepala keluarga dan anggota di sini.</p>
      <div class="space-y-3">
        <div
          v-for="a in anggotaEdit"
          :key="a.id"
          class="bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5 space-y-2"
        >
          <p class="text-[12px] font-semibold text-[var(--mut)] m-0">{{ a.hubungan }} · {{ a.status }}</p>
          <input
            v-model="a.nama"
            type="text"
            class="w-full min-h-[44px] px-3 rounded-[12px] bg-[var(--search)] outline-none font-semibold"
            :placeholder="a.hubungan"
            required
          />
        </div>
        <p v-if="!anggotaEdit.length" class="text-[13px] text-[var(--mut)]">Belum ada anggota</p>
      </div>

      <h2 class="text-[15px] font-bold pt-2 m-0">Pas foto kepala keluarga</h2>
      <div class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-full bg-[var(--card2)] overflow-hidden grid place-items-center">
          <img v-if="fotoUrl" :src="fotoUrl" class="w-full h-full object-cover" alt="" />
          <span v-else class="text-[var(--mut)] text-xs">Foto</span>
        </div>
        <label class="min-h-[44px] px-4 rounded-full bg-[var(--card2)] font-semibold grid place-items-center cursor-pointer">
          {{ uploading ? 'Mengunggah…' : 'Pilih foto' }}
          <input type="file" accept="image/*" class="hidden" :disabled="uploading" @change="onFoto" />
        </label>
      </div>
      <p class="text-[12px] text-[var(--mut)]">Hanya kepala keluarga (dipakai di struktur & profil keluarga).</p>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'

import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getKeluarga, updateKeluarga, updateAnggota } from '@shared/services/warga.js'
import { getBlok } from '@shared/services/pengaturan.js'
import { uploadGambar, mediaUrl } from '@shared/services/upload.js'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const err = ref('')
const blokList = ref([])
const blokId = ref('')
const nomor = ref('')
const akhiran = ref('')
const telepon = ref('')
const alamat = ref('')
const kepalaId = ref(null)
const fotoPath = ref('')
const fotoUrl = ref('')
const uploading = ref(false)
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)
const anggotaEdit = ref([])
const anggotaAsli = ref([])

onMounted(async () => {
  const [b, d] = await Promise.all([getBlok(), getKeluarga(route.params.id)])
  if (b.ok) blokList.value = b.data || []
  loading.value = false
  if (!d.ok) { err.value = d.error || 'Gagal'; return }
  const data = d.data || {}
  blokId.value = data.blok_id
  nomor.value = data.nomor || ''
  akhiran.value = data.akhiran || ''
  telepon.value = data.telepon || ''
  alamat.value = data.alamat || ''
  const list = data.anggota || []
  anggotaAsli.value = list.map((a) => ({ id: a.id, nama: a.nama || '' }))
  anggotaEdit.value = list.map((a) => ({
    id: a.id,
    nama: a.nama || '',
    hubungan: a.hubungan || 'Anggota',
    status: a.status || 'aktif',
  }))
  const kepala = list.find((a) => String(a.hubungan).toLowerCase().includes('kepala'))
  if (kepala) {
    kepalaId.value = kepala.id
    if (kepala.foto) {
      fotoPath.value = kepala.foto
      fotoUrl.value = mediaUrl(kepala.foto)
    }
  }
})

async function onFoto(e) {
  const f = e.target.files?.[0]
  if (!f) return
  uploading.value = true
  const res = await uploadGambar(f, 'foto_profil', { maxSide: 800 })
  uploading.value = false
  e.target.value = ''
  if (!res.ok) { msgOk.value = false; msg.value = res.error || 'Upload gagal'; return }
  fotoPath.value = res.data.path
  fotoUrl.value = res.data.url || mediaUrl(res.data.path)
}

async function onSave() {
  saving.value = true
  msg.value = ''
  for (const a of anggotaEdit.value) {
    if (!String(a.nama || '').trim()) {
      saving.value = false
      msgOk.value = false
      msg.value = 'Nama anggota tidak boleh kosong.'
      return
    }
  }

  const res = await updateKeluarga(route.params.id, {
    blok_id: Number(blokId.value),
    nomor: nomor.value,
    akhiran: akhiran.value,
    telepon: telepon.value,
    alamat: alamat.value,
  })
  if (!res.ok) {
    saving.value = false
    msgOk.value = false
    msg.value = res.error || 'Gagal'
    return
  }

  for (const a of anggotaEdit.value) {
    const asal = anggotaAsli.value.find((x) => x.id === a.id)
    const namaBaru = String(a.nama || '').trim()
    if (!asal || asal.nama !== namaBaru) {
      const r = await updateAnggota(a.id, { nama: namaBaru })
      if (!r.ok) {
        saving.value = false
        msgOk.value = false
        msg.value = r.error || 'Gagal menyimpan nama.'
        return
      }
    }
  }

  if (kepalaId.value && fotoPath.value) {
    await updateAnggota(kepalaId.value, { foto: fotoPath.value })
  }
  saving.value = false
  msgOk.value = true
  msg.value = 'Disimpan'
  setTimeout(() => router.replace('/warga/' + route.params.id), 500)
}
</script>
