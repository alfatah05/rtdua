<template>
  <div>
    <AppBackHeader title="Konfirmasi transfer" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <template v-else-if="item">
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 mb-4">
        <p class="text-[13px] text-[var(--mut)] m-0">{{ item.alamat }}</p>
        <p class="font-bold text-lg m-0">Diajukan: {{ rp(item?.nominal_diajukan ?? item?.nominal) }}</p>
      </div>
      <form class="space-y-4" @submit.prevent="onSave">
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal dikonfirmasi</label>
          <input v-model="nominal" type="text" inputmode="numeric" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
        </div>
        <div>
          <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal</label>
          <input v-model="tanggal" type="date" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
        </div>
        <button type="submit" class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold" :disabled="saving">
          {{ saving ? 'Menyimpan…' : 'Konfirmasi & simpan' }}
        </button>
        <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
      </form>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listPermintaan, konfirmasiPermintaan } from '@shared/services/keuangan.js'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const item = ref(null)
const nominal = ref('')
const tanggal = ref(new Date().toISOString().slice(0, 10))
const saving = ref(false)
const msg = ref('')
const msgOk = ref(true)

function rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID') }

onMounted(async () => {
  const id = String(route.params.id)
  const res = await listPermintaan('menunggu')
  loading.value = false
  if (res.ok) {
    item.value = (res.data || []).find((p) => String(p.id) === id) || null
    if (item.value) nominal.value = String(item.value.nominal_diajukan ?? item.value.nominal ?? '')
  }
})

async function onSave() {
  const n = parseInt(String(nominal.value).replace(/\D/g, ''), 10) || 0
  if (n < 1) { msgOk.value = false; msg.value = 'Nominal wajib'; return }
  saving.value = true
  const res = await konfirmasiPermintaan(route.params.id, { nominal: n, tanggal: tanggal.value })
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Transfer dikonfirmasi' : (res.error || 'Gagal')
  if (res.ok) setTimeout(() => router.replace('/keuangan'), 600)
}
</script>
