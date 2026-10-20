<template>
  <div>
    <AppBackHeader :title="isEdit ? 'Edit ' + pageTitle : 'Tambah ' + pageTitle" />
    <AppPageSpinner v-if="loading" />
    <form v-else class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Judul</label>
        <input v-model="judul" type="text" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Status</label>
        <select v-model="status" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
          <option value="direncanakan">Direncanakan</option>
          <option value="berjalan">Berjalan</option>
          <option value="selesai">Selesai</option>
        </select>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Banner</label>
        <div v-if="bannerUrl" class="rounded-[12px] overflow-hidden h-32 mb-2 bg-[var(--card2)]">
          <img :src="bannerUrl" class="w-full h-full object-cover" alt=""  loading="lazy" decoding="async" />
        </div>
        <label class="inline-flex min-h-[44px] px-4 rounded-full bg-[var(--card2)] font-semibold items-center cursor-pointer">
          {{ uploading ? 'Mengunggah…' : 'Pilih gambar' }}
          <input type="file" accept="image/*" class="hidden" :disabled="uploading" @change="onBanner" />
        </label>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Isi (Markdown)</label>
        <MarkdownEditor v-model="isi" :rows="10" placeholder="Isi informasi… support **tebal**, list, tabel, dll." />
      </div>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
      <button v-if="isEdit" type="button" class="w-full min-h-[48px] rounded-full border border-red-500 text-red-600 font-bold" @click="onHapus">Hapus</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'

import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import MarkdownEditor from '@shared/components/MarkdownEditor.vue'
import { detailKaderKonten, buatKaderKonten, ubahKaderKonten, hapusKaderKonten } from '@shared/services/konten.js'
import { uploadGambar, mediaUrl } from '@shared/services/upload.js'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id && route.params.id !== 'tambah')
const kategori = computed(() => String(route.meta.kategori || 'posyandu'))
const pageTitle = computed(() => {
  if (kategori.value === 'pengajian') return 'Pengajian'
  if (kategori.value === 'lingkungan') return 'Lingkungan'
  if (kategori.value === 'bulutangkis') return 'Bulutangkis'
  return 'Posyandu'
})
const basePath = computed(() => '/konten/' + kategori.value)
const judul = ref('')
const isi = ref('')
const status = ref('direncanakan')
const banner = ref('')
const bannerUrl = ref('')
const loading = ref(false)
const saving = ref(false)
const uploading = ref(false)
const msg = ref('')
const msgOk = ref(true)

onMounted(async () => {
  if (!isEdit.value) return
  loading.value = true
  const res = await detailKaderKonten(route.params.id)
  loading.value = false
  if (res.ok && res.data) {
    judul.value = res.data.judul || ''
    isi.value = res.data.isi_html || ''
    status.value = res.data.status || 'direncanakan'
    if (res.data.banner) {
      banner.value = res.data.banner
      bannerUrl.value = mediaUrl(res.data.banner)
    }
  }
})

async function onBanner(e) {
  const f = e.target.files?.[0]
  if (!f) return
  uploading.value = true
  const res = await uploadGambar(f, 'banner', { maxSide: 1600 })
  uploading.value = false
  e.target.value = ''
  if (!res.ok) { msgOk.value = false; msg.value = res.error || 'Upload gagal'; return }
  banner.value = res.data.path
  bannerUrl.value = res.data.url || mediaUrl(res.data.path)
}

async function onSave() {
  if (!judul.value.trim()) { msgOk.value = false; msg.value = 'Judul wajib'; return }
  saving.value = true
  const payload = {
    kategori: kategori.value,
    judul: judul.value.trim(),
    isi_html: isi.value.trim(),
    status: status.value,
    banner: banner.value || null,
  }
  const res = isEdit.value ? await ubahKaderKonten(route.params.id, payload) : await buatKaderKonten(payload)
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Disimpan' : (res.error || 'Gagal')
  if (res.ok) setTimeout(() => router.replace(basePath.value), 500)
}

async function onHapus() {
  if (!confirm('Hapus konten?')) return
  const res = await hapusKaderKonten(route.params.id)
  if (res.ok) router.replace(basePath.value)
  else { msgOk.value = false; msg.value = res.error || 'Gagal' }
}
</script>
