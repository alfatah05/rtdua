<template>
  <div>
    <AppBackHeader title="Jadwal tetap" />
    <p v-if="loading" class="text-[13px] text-[var(--mut)] text-center py-6">Memuat…</p>
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Hari ronda</p>
      <div class="flex justify-between gap-1.5 mb-5">
        <button
          v-for="(label, h) in hariPendek"
          :key="h"
          type="button"
          class="w-11 h-11 rounded-full text-[12px] font-bold grid place-items-center shrink-0 transition-transform active:scale-95 border-0"
          :class="selected[h] ? 'bg-[var(--gd)] text-[var(--gm)]' : 'bg-[var(--card2)] text-[var(--mut)]'"
          :disabled="busy"
          @click="toggleHari(h)"
        >{{ label }}</button>
      </div>

      <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Jam ronda</p>
      <div class="flex gap-2 mb-4">
        <input v-model="jamMulai" type="time" class="flex-1 min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none" />
        <input v-model="jamSelesai" type="time" class="flex-1 min-h-[44px] px-4 rounded-full bg-[var(--search)] outline-none" />
      </div>

      <p class="text-[13px] font-semibold text-[var(--mut)] mb-2">Pola card</p>
      <select v-model="durasi" class="w-full min-h-[44px] px-4 rounded-full bg-[var(--search)] mb-2 outline-none appearance-none">
        <option value="minggu">1 minggu (satu set card)</option>
        <option value="4">4 minggu putar (Minggu 1–4)</option>
      </select>
      <p class="text-[12px] text-[var(--mut)] m-0 mb-4 px-1">
        Template memakai rotasi minggu 1–4 di dalam bulan. Pilih 4 minggu putar bila penugasan KK berbeda tiap minggu; pilih 1 minggu bila semua minggu sama.
      </p>

      <button
        type="button"
        class="w-full min-h-[44px] rounded-full bg-[var(--g)] text-white font-bold mb-2 disabled:opacity-50"
        :disabled="busy || !adaHari"
        @click="jalankanBuat"
      >{{ busy ? 'Menyimpan…' : (cards.length ? 'Update jadwal' : 'Buat jadwal') }}</button>
      <p v-if="msg" class="text-[13px] text-center mb-4" :class="msgOk ? 'text-[var(--g)]' : 'text-red-600'">{{ msg }}</p>

      <p class="text-[15px] font-bold m-0 mb-3">Card jadwal</p>
      <p v-if="!cards.length" class="text-[13px] text-[var(--mut)] text-center py-4">Belum ada card. Pilih hari lalu klik Buat jadwal.</p>

      <div class="space-y-3 mb-8">
        <div
          v-for="card in cards"
          :key="card.id"
          class="rounded-[20px] border border-[var(--line)] overflow-hidden bg-[var(--card)]"
        >
          <div class="px-4 py-3 flex items-center justify-between" style="background: linear-gradient(145deg, #22B863, #0F9D4E 55%, #0B8442)">
            <div class="min-w-0">
              <p class="text-[14px] font-bold m-0 text-white">{{ card.label }}</p>
              <p class="text-[12px] m-0 mt-0.5 text-white/90">{{ jamLabel(card.jam_mulai) }}–{{ jamLabel(card.jam_selesai) }}</p>
            </div>
            <button
              type="button"
              class="min-h-[32px] px-3 rounded-full text-[12px] font-bold bg-white/20 text-white active:scale-95"
              @click="$router.push('/ronda/jadwal-tetap/card/' + card.id)"
            >Ubah warga</button>
          </div>
          <div class="px-4 py-3 space-y-1">
            <template v-if="card.keluarga?.length">
              <div
                v-for="k in card.keluarga"
                :key="k.keluarga_id"
                class="flex items-center justify-between gap-3 py-1"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 bg-[var(--card2)] grid place-items-center text-[12px] font-bold text-[var(--gm)]">
                    <img v-if="k.foto" :src="mediaUrl(k.foto)" alt="" class="w-full h-full object-cover" />
                    <span v-else>{{ inisial(k.nama) }}</span>
                  </div>
                  <span class="text-[14px] font-semibold min-w-0 truncate">{{ k.nama || '—' }}</span>
                </div>
                <span class="text-[13px] text-[var(--mut)] shrink-0 text-right">{{ k.alamat }}</span>
              </div>
            </template>
            <p v-else class="text-[13px] text-[var(--mut)] m-0">Belum ada warga</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import { listJadwalTetap, listTemplateCards, buatTemplateCards } from '@shared/services/ronda.js'
import { mediaUrl } from '@shared/services/upload.js'

const hariPendek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']
const loading = ref(true)
const err = ref('')
const selected = ref([false, false, false, false, false, false, false])
const jamMulai = ref('21:00')
const jamSelesai = ref('00:00')
/** 'minggu' = 1 set card; selain itu backend memakai 4 minggu putar */
const durasi = ref('4')
const busy = ref(false)
const msg = ref('')
const msgOk = ref(true)
const cards = ref([])
const adaHari = computed(() => selected.value.some(Boolean))

function jamLabel(j) {
  if (!j) return '—'
  return String(j).slice(0, 5).replace(':', '.')
}

function inisial(nama) {
  if (!nama) return '?'
  const p = String(nama).trim().split(/\s+/)
  return ((p[0]?.[0] || '') + (p[1]?.[0] || '')).toUpperCase() || '?'
}

async function load() {
  loading.value = true
  err.value = ''
  const [j, c] = await Promise.all([listJadwalTetap(), listTemplateCards()])
  loading.value = false
  if (!j.ok) {
    err.value = j.error || 'Gagal memuat'
    return
  }
  const next = [false, false, false, false, false, false, false]
  let jamSet = false
  for (const row of j.data || []) {
    const h = Number(row.hari)
    if (h >= 0 && h <= 6) {
      next[h] = true
      if (!jamSet && row.jam_mulai) {
        jamMulai.value = String(row.jam_mulai).slice(0, 5)
        jamSelesai.value = String(row.jam_selesai || '00:00').slice(0, 5)
        jamSet = true
      }
    }
  }
  selected.value = next
  if (c.ok) {
    cards.value = c.data || []
    // Infer pola dari jumlah minggu di card yang ada
    const mingguSet = new Set((cards.value || []).map((x) => Number(x.minggu)).filter(Boolean))
    if (mingguSet.size <= 1) durasi.value = 'minggu'
    else durasi.value = '4'
  }
}

function toggleHari(h) {
  const copy = selected.value.slice()
  copy[h] = !copy[h]
  selected.value = copy
}

async function jalankanBuat() {
  if (!adaHari.value) {
    msgOk.value = false
    msg.value = 'Pilih minimal satu hari ronda'
    return
  }
  busy.value = true
  msg.value = ''
  const hariList = []
  selected.value.forEach((on, h) => { if (on) hariList.push(h) })
  const res = await buatTemplateCards({
    hari: hariList,
    jam_mulai: jamMulai.value + ':00',
    jam_selesai: jamSelesai.value + ':00',
    durasi: durasi.value,
  })
  busy.value = false
  msgOk.value = !!res.ok
  if (res.ok) {
    const d = res.data || {}
    cards.value = d.cards || []
    const nMinggu = d.minggu || (durasi.value === 'minggu' ? 1 : 4)
    msg.value = `${d.dibuat || cards.value.length} card siap · pola ${nMinggu} minggu`
    if (!cards.value.length) {
      const c = await listTemplateCards()
      if (c.ok) cards.value = c.data || []
    }
  } else {
    msg.value = res.error || 'Gagal buat jadwal'
  }
}

onMounted(load)
</script>
