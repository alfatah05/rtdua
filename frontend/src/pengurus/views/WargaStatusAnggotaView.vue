<template>
  <div>
    <AppBackHeader title="Status anggota" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="loadErr" class="text-[13px] text-red-600 text-center py-4">{{ loadErr }}</p>
    <form v-else class="space-y-4" @submit.prevent="onSave">
      <p class="text-[13px] text-[var(--mut)] m-0">
        Keluarga: <b class="text-[var(--text)]">{{ alamat }}</b>
      </p>

      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Anggota</label>
        <select v-model="anggotaId" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" required>
          <option disabled value="">Pilih anggota</option>
          <option v-for="a in aktif" :key="a.id" :value="String(a.id)">
            {{ a.nama }} · {{ a.hubungan }}{{ a.hubungan === 'Kepala keluarga' ? ' ★' : '' }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Status baru</label>
        <div class="grid grid-cols-2 gap-2">
          <button
            type="button"
            class="min-h-[48px] rounded-[16px] border font-semibold"
            :class="status === 'meninggal' ? 'border-[var(--g)] bg-[var(--ok)] text-[var(--gm)]' : 'border-[var(--line)] bg-[var(--card)]'"
            @click="status = 'meninggal'"
          >Meninggal</button>
          <button
            type="button"
            class="min-h-[48px] rounded-[16px] border font-semibold"
            :class="status === 'keluar' ? 'border-[var(--g)] bg-[var(--ok)] text-[var(--gm)]' : 'border-[var(--line)] bg-[var(--card)]'"
            @click="status = 'keluar'"
          >Keluarkan</button>
        </div>
      </div>

      <div v-if="perluKepalaBaru">
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Kepala keluarga baru (wajib)</label>
        <p class="text-[12px] text-[var(--mut)] m-0 mb-2">
          Anggota ini Kepala keluarga. Pilih pengganti dari anggota aktif lain.
        </p>
        <select v-model="kepalaBaruId" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" required>
          <option disabled value="">Pilih kepala baru</option>
          <option v-for="a in kandidatKepala" :key="a.id" :value="String(a.id)">{{ a.nama }} · {{ a.hubungan }}</option>
        </select>
      </div>

      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Alasan / catatan (opsional)</label>
        <textarea v-model="alasan" rows="3" class="w-full px-4 py-3 rounded-[12px] bg-[var(--search)] outline-none resize-none" />
      </div>

      <p v-if="err" class="text-[13px] text-red-600 text-center">{{ err }}</p>
      <button type="submit" class="w-full min-h-[48px] rounded-full bg-red-600 text-white font-bold" :disabled="saving">
        {{ saving ? 'Menyimpan…' : 'Simpan status' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getKeluarga, setStatusAnggota } from '@shared/services/warga.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const router = useRouter()
const { success } = useToast()

const loading = ref(true)
const loadErr = ref('')
const err = ref('')
const saving = ref(false)
const alamat = ref('')
const anggota = ref([])
const anggotaId = ref(String(route.query.anggota_id || ''))
const status = ref(String(route.query.status || 'meninggal') === 'keluar' ? 'keluar' : 'meninggal')
const kepalaBaruId = ref('')
const alasan = ref('')

const aktif = computed(() => (anggota.value || []).filter((a) => a.status === 'aktif'))
const selected = computed(() => aktif.value.find((a) => String(a.id) === String(anggotaId.value)))
const perluKepalaBaru = computed(() => {
  if (!selected.value) return false
  if (String(selected.value.hubungan || '').toLowerCase() !== 'kepala keluarga') return false
  return aktif.value.some((a) => String(a.id) !== String(anggotaId.value))
})
const kandidatKepala = computed(() =>
  aktif.value.filter((a) => String(a.id) !== String(anggotaId.value))
)

async function onSave() {
  err.value = ''
  if (!anggotaId.value) {
    err.value = 'Pilih anggota.'
    return
  }
  if (!status.value) {
    err.value = 'Pilih status.'
    return
  }
  if (perluKepalaBaru.value && !kepalaBaruId.value) {
    err.value = 'Pilih kepala keluarga baru.'
    return
  }
  const label = status.value === 'meninggal' ? 'meninggal' : 'dikeluarkan'
  if (!confirm(`Yakin tandai anggota ini ${label}?`)) return

  saving.value = true
  const payload = {
    status: status.value,
    alasan: alasan.value || null,
  }
  if (perluKepalaBaru.value) {
    payload.kepala_baru_id = Number(kepalaBaruId.value)
  }
  const res = await setStatusAnggota(anggotaId.value, payload)
  saving.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal menyimpan.'
    return
  }
  success('Status anggota diperbarui')
  router.replace('/warga/' + route.params.id)
}

onMounted(async () => {
  const res = await getKeluarga(route.params.id)
  loading.value = false
  if (!res.ok) {
    loadErr.value = res.error || 'Gagal memuat'
    return
  }
  alamat.value = res.data?.alamat_label || ''
  anggota.value = res.data?.anggota || []
  if (!anggotaId.value && aktif.value.length === 1) {
    anggotaId.value = String(aktif.value[0].id)
  }
})
</script>
