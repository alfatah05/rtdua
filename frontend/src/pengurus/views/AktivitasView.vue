<template>
  <div>
    <AppMainHeader show-profil />

    <div class="flex gap-2 mb-3">
      <select v-model="bulan" class="flex-1 min-h-[44px] px-4 rounded-full bg-[var(--search)] text-[var(--text)] outline-none appearance-none">
        <option v-for="b in daftarBulan" :key="b.v" :value="b.v">{{ b.l }}</option>
      </select>
      <select v-model="tahun" class="w-28 min-h-[44px] px-4 rounded-full bg-[var(--search)] text-[var(--text)] outline-none appearance-none">
        <option v-for="t in daftarTahun" :key="t" :value="t">{{ t }}</option>
      </select>
    </div>

    <p class="text-[13px] text-[var(--mut)] mb-1 px-2">{{ filtered.length }} aktivitas</p>

    <div v-if="filtered.length === 0" class="px-2 py-8 text-center text-[var(--mut)] text-[14px]">Tidak ada aktivitas di bulan ini</div>
    <div v-else class="px-1 space-y-0.5">
      <button
        v-for="act in filtered"
        :key="act.id"
        type="button"
        class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:scale-[0.99] transition-transform"
        @click="$router.push('/aktivitas/' + act.id)"
      >
        <div class="w-10 h-10 rounded-full grid place-items-center shrink-0 bg-[var(--card2)] text-[var(--mut)]">
          <Activity :size="18" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-[15px] m-0 leading-tight">{{ act.text }}</p>
          <p class="text-[13px] text-[var(--mut)] m-0 leading-tight mt-0.5">{{ act.waktu }}</p>
        </div>
        <ChevronRight :size="18" class="text-[var(--mut)] shrink-0" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import AppMainHeader from '@shared/components/AppMainHeader.vue'
import { listAktivitas } from '@shared/services/aktivitas.js'
import { Activity, ChevronRight } from 'lucide-vue-next'

const now = new Date()
const bulan = ref(now.getMonth() + 1)
const tahun = ref(String(now.getFullYear()))
const daftarBulan = [
  { v: 1, l: 'Januari' }, { v: 2, l: 'Februari' }, { v: 3, l: 'Maret' },
  { v: 4, l: 'April' }, { v: 5, l: 'Mei' }, { v: 6, l: 'Juni' },
  { v: 7, l: 'Juli' }, { v: 8, l: 'Agustus' }, { v: 9, l: 'September' },
  { v: 10, l: 'Oktober' }, { v: 11, l: 'November' }, { v: 12, l: 'Desember' },
]
const daftarTahun = [String(now.getFullYear()), String(now.getFullYear() - 1)]
const items = ref([])

async function load() {
  const res = await listAktivitas({ bulan: bulan.value, tahun: tahun.value })
  if (res.ok) {
    items.value = (res.data || []).map((a) => ({
      id: a.id,
      text: `${a.pelaku} · ${String(a.aksi || '').replace(/_/g, ' ')}`,
      waktu: a.waktu,
    }))
  }
}

onMounted(load)
watch([bulan, tahun], load)

const filtered = computed(() => items.value)
</script>
