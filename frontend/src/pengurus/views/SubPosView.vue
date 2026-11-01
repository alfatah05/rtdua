<template>
  <div>
    <AppBackHeader title="Sub pos" />

    <p class="text-[13px] text-[var(--mut)] m-0 mb-4 px-1">
      Buku saldo per komponen iuran, iuran khusus, dan denda. Uang masuk ke sini saat bayar — setor ke pos kas lewat alokasi.
    </p>

    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>
    <EmptyState v-else-if="!items.length" title="Belum ada sub pos" description="Sub pos tercipta otomatis saat komponen iuran / iuran khusus dibuat." />

    <template v-else>
      <div class="rounded-[16px] border border-[var(--line)] bg-[var(--card)] p-4 mb-4">
        <p class="text-[13px] text-[var(--mut)] m-0">Total belum disetor</p>
        <p class="text-[22px] font-extrabold m-0 mt-0.5">{{ formatRp(totalSaldo) }}</p>
      </div>

      <div class="space-y-2">
        <button
          v-for="p in items"
          :key="p.id"
          type="button"
          class="w-full text-left rounded-[16px] border border-[var(--line)] bg-[var(--card)] p-4 active:scale-[0.99] transition-transform"
          @click="$router.push('/keuangan/sub-pos/' + p.id)"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="font-bold text-[15px] m-0 leading-tight">{{ p.judul }}</p>
              <p class="text-[12px] text-[var(--mut)] m-0 mt-1">
                {{ labelJenis(p.jenis) }}
                <span v-if="!p.aktif"> · nonaktif</span>
              </p>
            </div>
            <div class="text-right shrink-0">
              <p class="font-extrabold text-[15px] m-0" :class="p.saldo > 0 ? 'text-[var(--g)]' : ''">
                {{ formatRp(p.saldo) }}
              </p>
              <p class="text-[11px] text-[var(--mut)] m-0 mt-0.5">Detail →</p>
            </div>
          </div>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import EmptyState from '@shared/components/EmptyState.vue'
import { formatRp } from '@shared/utils/format.js'
import { listSubPos } from '@shared/services/keuangan.js'

const loading = ref(true)
const err = ref('')
const items = ref([])

const totalSaldo = computed(() =>
  items.value.reduce((s, p) => s + (Number(p.saldo) || 0), 0)
)

function labelJenis(j) {
  if (j === 'komponen') return 'Komponen iuran'
  if (j === 'khusus') return 'Iuran khusus'
  if (j === 'denda') return 'Denda ronda'
  return j || '—'
}

async function load() {
  loading.value = true
  err.value = ''
  const res = await listSubPos()
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat'
    return
  }
  const data = res.data
  items.value = Array.isArray(data) ? data : (data?.items || [])
}

onMounted(load)
</script>
