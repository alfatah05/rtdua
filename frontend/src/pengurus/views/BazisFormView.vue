<template>
  <div>
    <AppBackHeader title="Bazis" />
    <AppPageSpinner v-if="loading" />
    <form v-else class="space-y-4" @submit.prevent="onSave">
      <div>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1.5">Label bulan</label>
        <input
          v-model="labelBulan"
          type="text"
          placeholder="Contoh: Bazis Oktober 2026"
          class="w-full min-h-[48px] px-4 rounded-[12px] bg-[var(--search)] outline-none"
        />
        <p class="text-[12px] text-[var(--mut)] m-0 mt-1">Teks ini yang tampil di aplikasi warga.</p>
      </div>

      <div>
        <p class="text-[13px] font-semibold text-[var(--mut)] m-0 mb-2">Nominal per RT</p>
        <div class="space-y-2">
          <div
            v-for="(row, idx) in items"
            :key="row.rt"
            class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[14px] px-3 py-2"
          >
            <span class="w-16 text-[14px] font-bold shrink-0">{{ row.rt }}</span>
            <input
              v-model.number="items[idx].total"
              type="number"
              min="0"
              step="1000"
              class="flex-1 min-h-[44px] px-3 rounded-[10px] bg-[var(--search)] outline-none tabular-nums"
              inputmode="numeric"
            />
          </div>
        </div>
        <p class="text-[12px] text-[var(--mut)] m-0 mt-2">Hanya informasi tampilan — tidak dijumlahkan.</p>
      </div>

      <button
        type="submit"
        class="w-full min-h-[48px] rounded-full bg-[var(--g)] text-white font-bold"
        :disabled="saving"
      >{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
      <p v-if="msg" class="text-[13px] text-center" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { getBazis, simpanBazis } from '@shared/services/konten.js'

const RTS = ['RT 001', 'RT 002', 'RT 003', 'RT 004', 'RT 005', 'RT 006', 'RT 007', 'RT 008']

const loading = ref(true)
const saving = ref(false)
const labelBulan = ref('')
const items = ref(RTS.map((rt) => ({ rt, total: 0 })))
const msg = ref('')
const msgOk = ref(true)

onMounted(async () => {
  loading.value = true
  const res = await getBazis()
  loading.value = false
  if (res.ok && res.data) {
    labelBulan.value = res.data.label_bulan || ''
    const map = {}
    for (const row of res.data.items || []) {
      map[row.rt] = Number(row.total) || 0
    }
    items.value = RTS.map((rt) => ({ rt, total: map[rt] || 0 }))
  }
})

async function onSave() {
  saving.value = true
  msg.value = ''
  const payload = {
    label_bulan: labelBulan.value.trim(),
    items: items.value.map((r) => ({
      rt: r.rt,
      total: Math.max(0, Number(r.total) || 0),
    })),
  }
  const res = await simpanBazis(payload)
  saving.value = false
  msgOk.value = !!res.ok
  msg.value = res.ok ? 'Disimpan' : (res.error || 'Gagal')
}
</script>
