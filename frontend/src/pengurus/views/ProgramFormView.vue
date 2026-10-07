<template>
  <div>
    <AppBackHeader :title="isEdit ? 'Edit program' : 'Tambah program'" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
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
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Isi</label>
        <textarea v-model="isi" rows="6" class="w-full px-4 py-3 rounded-[12px] bg-[var(--search)] outline-none resize-none" />
      </div>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
      <button v-if="isEdit" type="button" class="w-full min-h-[44px] rounded-full bg-red-50 text-red-600 font-semibold" @click="onHapus">Hapus</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { detailProgram, buatProgram, ubahProgram, hapusProgram } from '@shared/services/konten.js'
const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id && route.params.id !== 'tambah')
const judul = ref('')
const isi = ref('')
const status = ref('direncanakan')
const loading = ref(false)
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)
onMounted(async () => {
  if (!isEdit.value) return
  loading.value = true
  const res = await detailProgram(route.params.id)
  loading.value = false
  if (res.ok && res.data) {
    judul.value = res.data.judul || ''
    isi.value = (res.data.isi_html || '').replace(/<[^>]+>/g, '')
    status.value = res.data.status || 'direncanakan'
  }
})
async function onSave() {
  if (!judul.value.trim()) { msgOk.value = false; msg.value = 'Judul wajib'; return }
  saving.value = true
  const payload = { judul: judul.value.trim(), isi_html: '<p>' + isi.value.replace(/\n/g, '<br>') + '</p>', status: status.value }
  const res = isEdit.value ? await ubahProgram(route.params.id, payload) : await buatProgram(payload)
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Disimpan' : (res.error || 'Gagal')
  if (res.ok) setTimeout(() => router.replace('/konten/program'), 500)
}
async function onHapus() {
  if (!confirm('Hapus program?')) return
  const res = await hapusProgram(route.params.id)
  if (res.ok) router.replace('/konten/program')
  else { msgOk.value = false; msg.value = res.error || 'Gagal' }
}
</script>
