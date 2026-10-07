<template>
  <div>
    <AppBackHeader title="Malam ronda" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-8">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <template v-else-if="malam">
      <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)] mb-4">
        <p class="font-bold text-[17px] m-0">{{ formatHari(malam.tanggal) }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0">
          {{ jamLabel(malam.jam_mulai) }} – {{ jamLabel(malam.jam_selesai) }}
          · Sumber: {{ malam.sumber || '—' }}
          <span v-if="malam.terkunci"> · terkunci</span>
        </p>
      </div>

      <h2 class="text-[15px] font-bold mb-2">Bertugas</h2>
      <div class="px-1 space-y-0.5 mb-5">
        <div v-for="k in malam.keluarga || []" :key="k.keluarga_id" class="w-full flex flex-row items-center gap-3 px-2 py-3">
          <div class="flex-1 min-w-0">
            <p class="font-bold text-[15px] m-0">{{ k.alamat }}</p>
            <p class="text-[13px] m-0" :class="k.status === 'hadir' ? 'text-[var(--g)]' : 'text-amber-600'">
              {{ k.status === 'hadir' ? 'Hadir' : 'Belum absen' }}{{ k.absen?.waktu_server ? ' · ' + jamLabel(k.absen.waktu_server.slice(11, 16)) : '' }}
            </p>
          </div>
          <button
            v-if="k.status === 'hadir' && k.absen?.id && !malam.terkunci"
            type="button"
            class="text-[12px] font-semibold text-red-600"
            :disabled="busy"
            @click="onBatal(k)"
          >Batalkan</button>
          <button
            v-else-if="k.status !== 'hadir' && !malam.terkunci"
            type="button"
            class="text-[12px] font-semibold text-[var(--g)]"
            :disabled="busy"
            @click="onAbsen(k)"
          >Absenkan</button>
        </div>
        <p v-if="!(malam.keluarga || []).length" class="text-[13px] text-[var(--mut)] px-2">Belum ada keluarga bertugas</p>
      </div>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { detailMalam, absenManual, batalkanAbsen } from '@shared/services/ronda.js'

const route = useRoute()
const loading = ref(true)
const err = ref('')
const malam = ref(null)
const msg = ref('')
const msgOk = ref(true)
const busy = ref(false)

function jamLabel(j) {
  if (!j) return '—'
  return String(j).slice(0, 5).replace(':', '.')
}
function formatHari(tgl) {
  if (!tgl) return ''
  return new Date(tgl + 'T00:00:00').toLocaleDateString('id-ID', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  })
}

async function load() {
  loading.value = true
  err.value = ''
  const tgl = route.params.tanggal || route.params.id
  const res = await detailMalam(tgl)
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Tidak ada data'
    return
  }
  malam.value = res.data
}

async function onAbsen(k) {
  if (!malam.value?.id) return
  busy.value = true
  msg.value = ''
  const res = await absenManual(malam.value.id, k.keluarga_id)
  busy.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Absen dicatat' : (res.error || 'Gagal')
  if (res.ok) await load()
}

async function onBatal(k) {
  if (!k.absen?.id) return
  if (!confirm('Batalkan absen keluarga ini?')) return
  busy.value = true
  msg.value = ''
  const res = await batalkanAbsen(k.absen.id)
  busy.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Absen dibatalkan' : (res.error || 'Gagal')
  if (res.ok) await load()
}

onMounted(load)
</script>
