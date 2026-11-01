<template>
  <div>
    <AppBackHeader :title="isEdit ? 'Edit undian' : 'Buat undian'" />
    <AppPageSpinner v-if="loading" />
    <form v-else class="space-y-4" @submit.prevent="simpan">
      <div>
        <label class="text-[13px] font-semibold text-[var(--mut)]">Judul</label>
        <input
          v-model="judul"
          type="text"
          required
          maxlength="255"
          class="mt-1 w-full min-h-[48px] rounded-[14px] border border-[var(--line)] bg-[var(--card)] px-4 text-[15px]"
          placeholder="Mis. Hadiah Sembako Juli"
        />
      </div>
      <div>
        <label class="text-[13px] font-semibold text-[var(--mut)] mb-1 block">Isi (Markdown)</label>
        <p class="text-[12px] text-[var(--mut)] m-0 mb-2">Ucapan, list hadiah, syarat & ketentuan.</p>
        <MarkdownEditor v-model="isi" />
      </div>
      <button
        type="submit"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-50"
        :disabled="saving"
      >
        {{ saving ? 'Menyimpan…' : 'Simpan' }}
      </button>
      <button
        v-if="isEdit"
        type="button"
        class="w-full min-h-[48px] rounded-full border border-red-500 text-red-600 font-bold"
        @click="onHapus"
      >
        Hapus event
      </button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import MarkdownEditor from '@shared/components/MarkdownEditor.vue'
import { detailReward, buatReward, ubahReward, hapusReward } from '@shared/services/reward.js'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id && route.params.id !== 'tambah')
const judul = ref('')
const isi = ref('')
const loading = ref(false)
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)

onMounted(async () => {
  if (!isEdit.value) return
  loading.value = true
  const res = await detailReward(route.params.id)
  loading.value = false
  if (res.ok && res.data) {
    judul.value = res.data.judul || ''
    isi.value = res.data.isi_html || ''
  }
})

async function simpan() {
  msg.value = ''
  saving.value = true
  const payload = { judul: judul.value.trim(), isi_html: isi.value }
  const res = isEdit.value
    ? await ubahReward(route.params.id, payload)
    : await buatReward(payload)
  saving.value = false
  msgOk.value = !!res.ok
  if (!res.ok) {
    msg.value = res.error || 'Gagal'
    return
  }
  const id = res.data?.id || route.params.id
  router.replace('/reward/' + id)
}

async function onHapus() {
  if (!confirm('Hapus event undian ini beserta seluruh riwayat?')) return
  const res = await hapusReward(route.params.id)
  if (!res.ok) {
    msg.value = res.error || 'Gagal hapus'
    msgOk.value = false
    return
  }
  router.replace('/reward')
}
</script>
