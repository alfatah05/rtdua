<template>
  <div>
    <AppBackHeader :title="title" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-8">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else>
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-14 h-14 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-lg overflow-hidden">
            <img v-if="fotoKepala" :src="fotoKepala" class="w-full h-full object-cover" alt="" />
            <span v-else>{{ inisial }}</span>
          </div>
          <div>
            <p class="font-bold text-lg m-0">{{ namaKepala }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0">{{ detail.alamat_label || detail.alamat || '—' }}</p>
            <p v-if="detail.username" class="text-[12px] text-[var(--mut)] m-0">@{{ detail.username }}</p>
          </div>
        </div>
        <p v-if="penanda" class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[var(--search)] text-[var(--mut)] inline-block m-0">{{ penanda }}</p>
      </div>
      <h2 class="text-[15px] font-bold mb-2">Anggota</h2>
      <div class="space-y-2 mb-5">
        <div v-for="a in detail.anggota || []" :key="a.id" class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-3.5">
          <div class="w-10 h-10 rounded-full bg-[var(--card2)] grid place-items-center font-bold text-xs overflow-hidden">
            <img v-if="a.foto" :src="mediaUrl(a.foto)" class="w-full h-full object-cover" alt="" />
            <span v-else>{{ init(a.nama) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-bold text-[15px] m-0">{{ a.nama }}</p>
            <p class="text-[13px] text-[var(--mut)] m-0">{{ a.hubungan }} · {{ a.status }}</p>
          </div>
          <span class="text-[12px] text-[var(--mut)]">{{ a.punya_nik ? 'NIK ✓' : 'Tanpa NIK' }}</span>
        </div>
      </div>
      <h2 class="text-[15px] font-bold mb-2">Aksi</h2>
      <div class="space-y-2">
        <button type="button" class="w-full flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 text-left" @click="$router.push('/warga/' + id + '/edit')">
          <span class="font-semibold text-[15px]">Edit keluarga / foto</span>
        </button>
        <button type="button" class="w-full flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 text-left" :disabled="resetting" @click="onResetPin">
          <span class="font-semibold text-[15px]">{{ resetting ? 'Mereset…' : 'Reset PIN' }}</span>
        </button>
        <button type="button" class="w-full flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[16px] p-4 text-left" @click="$router.push('/keuangan/tagihan/' + id)">
          <span class="font-semibold text-[15px]">Lihat tagihan / catat bayar</span>
        </button>
        <button type="button" class="w-full flex items-center gap-3 bg-red-50 border border-red-100 rounded-[16px] p-4 text-left" @click="$router.push('/warga/' + id + '/pindah')">
          <span class="font-semibold text-[15px] text-red-600">Tandai pindah</span>
        </button>
      </div>
      <p v-if="aksiMsg" class="text-[13px] text-center mt-3" :class="aksiOk ? 'text-[var(--g)]' : 'text-red-600'">{{ aksiMsg }}</p>
    </template>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { getKeluarga, resetPin } from '@shared/services/warga.js'
import { mediaUrl } from '@shared/services/upload.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const { success } = useToast()
const id = computed(() => Number(route.params.id))
const loading = ref(true)
const err = ref('')
const detail = ref({})
const resetting = ref(false)
const aksiMsg = ref('')
const aksiOk = ref(true)

const title = computed(() => detail.value.alamat_label || detail.value.alamat || 'Detail keluarga')
const namaKepala = computed(() => {
  const list = detail.value.anggota || []
  const k = list.find((a) => String(a.hubungan).toLowerCase().includes('kepala'))
  return k?.nama || list[0]?.nama || '—'
})
const inisial = computed(() => init(namaKepala.value))
const fotoKepala = computed(() => {
  const list = detail.value.anggota || []
  const k = list.find((a) => String(a.hubungan).toLowerCase().includes('kepala'))
  return k?.foto ? mediaUrl(k.foto) : ''
})
const penanda = computed(() => {
  if (detail.value.mulai_bulan_depan) return 'Mulai bulan depan'
  if (detail.value.belum_ganti_pin) return 'Belum ganti PIN'
  if (detail.value.data_belum_lengkap) return 'Data belum lengkap'
  return ''
})
function init(nama) {
  return String(nama || '?').split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()
}
async function load() {
  loading.value = true
  err.value = ''
  const res = await getKeluarga(id.value)
  loading.value = false
  if (!res.ok) { err.value = res.error || 'Gagal memuat.'; return }
  detail.value = res.data || {}
}
async function onResetPin() {
  if (!confirm('Reset PIN keluarga ini ke 123456?')) return
  aksiMsg.value = ''
  resetting.value = true
  const res = await resetPin(id.value)
  resetting.value = false
  if (!res.ok) { aksiOk.value = false; aksiMsg.value = res.error || 'Gagal reset PIN.'; return }
  aksiOk.value = true
  aksiMsg.value = 'PIN direset ke 123456.'
  success('PIN direset')
  load()
}
onMounted(load)
</script>
