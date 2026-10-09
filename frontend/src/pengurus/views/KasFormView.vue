<template>
  <div>
    <AppBackHeader :title="isMasuk ? 'Kas masuk' : 'Kas keluar'" />

    <form class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Nominal</label>
        <input
          v-model="nominal"
          type="text"
          inputmode="numeric"
          placeholder="100000"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none focus:outline focus:outline-2 focus:outline-[var(--gh)]"
        />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Kategori</label>
        <select v-model="kategori" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none">
          <option v-for="k in kategoriList" :key="k" :value="k">{{ k }}</option>
        </select>
        <p class="text-[12px] text-[var(--mut)] mt-1 m-0">
          Kategori iuran otomatis dari pembayaran. Pilih kategori manual di sini.
        </p>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Metode</label>
        <div class="flex gap-2">
          <button
            type="button"
            class="flex-1 min-h-[44px] rounded-full text-[13px] font-semibold border"
            :class="metode === 'tunai' ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="metode = 'tunai'"
          >Tunai</button>
          <button
            type="button"
            class="flex-1 min-h-[44px] rounded-full text-[13px] font-semibold border"
            :class="metode === 'transfer' ? 'bg-[var(--g)] text-white border-transparent' : 'bg-[var(--card)] border-[var(--line)]'"
            @click="metode = 'transfer'"
          >Transfer</button>
        </div>
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Keterangan</label>
        <input
          v-model="ket"
          type="text"
          placeholder="Keterangan (bebas)"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
        />
      </div>
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Tanggal</label>
        <input v-model="tanggal" type="date" class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none" />
      </div>
      <p v-if="err" class="text-[13px] text-red-600 text-center m-0">{{ err }}</p>
      <button
        type="submit"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold disabled:opacity-60"
        :disabled="saving"
      >
        {{ saving ? 'Menyimpan…' : 'Simpan' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { useToast } from '@shared/composables/useToast.js'
import { tambahKas } from '@shared/services/keuangan.js'

const route = useRoute()
const router = useRouter()
const { success } = useToast()

const isMasuk = computed(() => route.path.includes('kas-masuk'))
const nominal = ref('')
const kategori = ref(isMasuk.value ? 'Saldo awal' : 'Operasional')
const metode = ref('tunai')
const ket = ref('')
const tanggal = ref(new Date().toISOString().slice(0, 10))
const err = ref('')
const saving = ref(false)

const kategoriList = computed(() =>
  isMasuk.value
    ? ['Saldo awal', 'Sumbangan', 'Lainnya']
    : ['Operasional', 'Pengembalian kelebihan', 'Lainnya']
)

async function onSave() {
  err.value = ''
  const n = parseInt(String(nominal.value).replace(/\D/g, ''), 10) || 0
  if (n < 1) {
    err.value = 'Nominal wajib diisi.'
    return
  }
  saving.value = true
  const res = await tambahKas({
    tipe: isMasuk.value ? 'masuk' : 'keluar',
    nominal: n,
    kategori: kategori.value,
    metode: metode.value,
    keterangan: ket.value || null,
    tanggal: tanggal.value,
  })
  saving.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal menyimpan.'
    return
  }
  success('Transaksi tersimpan')
  router.replace('/keuangan')
}
</script>
