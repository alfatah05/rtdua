<template>
  <div>
    <AppBackHeader :title="isEdit ? 'Edit pengumuman' : 'Tambah pengumuman'" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <form v-else class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Judul</label>
        <input v-model="judul" type="text" placeholder="Judul pengumuman"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Isi</label>
        <textarea v-model="isi" rows="5" placeholder="Isi pengumuman"
          class="w-full px-4 py-3 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)] resize-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Lampiran (opsional, PDF atau gambar)</label>
        <div v-if="lampiranFile" class="flex items-center gap-2 mb-2 p-3 rounded-[12px] bg-[var(--search)]">
          <span class="text-[13px] font-semibold truncate flex-1">{{ lampiranLabel }}</span>
          <button type="button" class="text-[12px] font-bold text-red-600" @click="clearLampiran">Hapus</button>
        </div>
        <input type="file" accept="application/pdf,image/jpeg,image/png,image/webp" class="text-[13px] w-full" :disabled="uploading" @change="onPickLampiran" />
        <p v-if="uploading" class="text-[12px] text-[var(--mut)] mt-1">Mengunggah…</p>
      </div>

      <div class="rounded-[16px] bg-[var(--search)] p-4 space-y-3">
        <p class="text-[13px] font-bold m-0">Waktu tampil di beranda</p>
        <p class="text-[12px] text-[var(--mut)] m-0">Setelah selesai, pengumuman tetap ada di halaman Pengumuman (riwayat).</p>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Mulai tampil</label>
          <input v-model="tampilMulai" type="datetime-local"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--card)] outline-none" />
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Selesai tampil</label>
          <input v-model="tampilSelesai" type="datetime-local"
            class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--card)] outline-none" />
        </div>
      </div>

      <label v-if="isEdit" class="flex items-center gap-3 min-h-[44px]">
        <input v-model="kirimNotif" type="checkbox" class="w-5 h-5 accent-[var(--g)]" />
        <span class="text-[15px] font-semibold">Kirim notifikasi lagi</span>
      </label>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving || uploading">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
      <button v-if="isEdit" type="button" class="w-full min-h-[44px] rounded-full bg-red-50 text-red-600 font-semibold" :disabled="saving" @click="onHapus">Hapus pengumuman</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { detailPengumuman, buatPengumuman, ubahPengumuman, hapusPengumuman } from '@shared/services/konten.js'
import { uploadLampiran } from '@shared/services/upload.js'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id && route.params.id !== 'tambah')
const judul = ref('')
const isi = ref('')
const tampilMulai = ref('')
const tampilSelesai = ref('')
const kirimNotif = ref(false)
const msg = ref('')
const msgOk = ref(true)
const saving = ref(false)
const loading = ref(false)
const uploading = ref(false)
const lampiranFile = ref(null)
const lampiranTipe = ref(null)
const lampiranLabel = ref('')

function toLocalInput(s) {
  if (!s) return ''
  const d = new Date(String(s).replace(' ', 'T'))
  if (isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function defaultWindow() {
  const now = new Date()
  const end = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000)
  const pad = (n) => String(n).padStart(2, '0')
  const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
  tampilMulai.value = fmt(now)
  tampilSelesai.value = fmt(end)
}

function clearLampiran() {
  lampiranFile.value = null
  lampiranTipe.value = null
  lampiranLabel.value = ''
}

async function onPickLampiran(ev) {
  const file = ev.target?.files?.[0]
  if (!file) return
  uploading.value = true
  msg.value = ''
  const res = await uploadLampiran(file)
  uploading.value = false
  ev.target.value = ''
  if (!res.ok) {
    msgOk.value = false
    msg.value = res.error || 'Gagal unggah lampiran'
    return
  }
  lampiranFile.value = res.data.path
  lampiranTipe.value = res.data.mime || file.type
  lampiranLabel.value = file.name || res.data.path
  msgOk.value = true
  msg.value = 'Lampiran siap — tekan Simpan'
}

onMounted(async () => {
  if (!isEdit.value) {
    defaultWindow()
    return
  }
  loading.value = true
  const res = await detailPengumuman(route.params.id)
  loading.value = false
  if (res.ok && res.data) {
    judul.value = res.data.judul || ''
    isi.value = res.data.isi || ''
    tampilMulai.value = toLocalInput(res.data.tampil_mulai) || toLocalInput(res.data.diterbitkan_pada)
    tampilSelesai.value = toLocalInput(res.data.tampil_selesai)
    if (!tampilSelesai.value && tampilMulai.value) {
      const d = new Date(tampilMulai.value)
      d.setDate(d.getDate() + 7)
      const pad = (n) => String(n).padStart(2, '0')
      tampilSelesai.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
    }
    if (res.data.lampiran_file) {
      lampiranFile.value = res.data.lampiran_file
      lampiranTipe.value = res.data.lampiran_tipe
      lampiranLabel.value = res.data.lampiran_file.split('/').pop()
    }
  } else {
    msgOk.value = false
    msg.value = res.error || 'Gagal memuat'
  }
})

async function onSave() {
  msg.value = ''
  if (!judul.value.trim() || !isi.value.trim()) {
    msgOk.value = false
    msg.value = 'Judul dan isi wajib'
    return
  }
  if (!tampilMulai.value || !tampilSelesai.value) {
    msgOk.value = false
    msg.value = 'Waktu mulai dan selesai tampil wajib'
    return
  }
  saving.value = true
  const payload = {
    judul: judul.value.trim(),
    isi: isi.value.trim(),
    tampil_mulai: tampilMulai.value,
    tampil_selesai: tampilSelesai.value,
    lampiran_file: lampiranFile.value,
    lampiran_tipe: lampiranTipe.value,
  }
  let res
  if (isEdit.value) {
    payload.kirim_notif_lagi = kirimNotif.value
    res = await ubahPengumuman(route.params.id, payload)
  } else {
    res = await buatPengumuman(payload)
  }
  saving.value = false
  if (!res.ok) {
    msgOk.value = false
    msg.value = res.error || 'Gagal menyimpan'
    return
  }
  msgOk.value = true
  msg.value = 'Pengumuman disimpan'
  setTimeout(() => router.replace('/konten/pengumuman'), 600)
}

async function onHapus() {
  if (!confirm('Hapus pengumuman ini?')) return
  saving.value = true
  const res = await hapusPengumuman(route.params.id)
  saving.value = false
  if (!res.ok) {
    msgOk.value = false
    msg.value = res.error || 'Gagal menghapus'
    return
  }
  router.replace('/konten/pengumuman')
}
</script>
