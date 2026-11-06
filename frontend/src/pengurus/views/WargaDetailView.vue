<template>
  <div>
    <AppBackHeader title="Profil keluarga" />
    <AppPageSpinner v-if="loading" />
    <p v-else-if="err" class="text-[13px] text-red-600 text-center py-4">{{ err }}</p>

    <template v-else>
      <!-- Header: sama gaya warga (tanpa card) -->
      <div class="flex flex-col items-center text-center mb-6 pt-2">
        <div
          class="w-24 h-24 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center text-3xl font-bold overflow-hidden mb-3"
        >
          <img
            v-if="fotoKepala"
            :src="fotoKepala"
            alt=""
            class="w-full h-full object-cover"
            @error="fotoKepalaBroken = true"
          />
          <span v-else>{{ inisial }}</span>
        </div>
        <p class="text-xl font-extrabold m-0 leading-tight">{{ namaKepala }}</p>
        <p class="text-[13px] text-[var(--mut)] m-0 mt-1">{{ alamatLabel }}</p>
        <p
          v-if="penanda"
          class="text-[12px] font-bold px-2.5 py-1 rounded-full bg-[var(--search)] text-[var(--mut)] inline-block m-0 mt-2"
        >
          {{ penanda }}
        </p>
      </div>

      <!-- Anggota: list flat seperti warga -->
      <div class="flex items-center justify-between mb-1 px-1">
        <p class="text-[13px] font-semibold text-[var(--mut)] m-0">Anggota keluarga</p>
        <button
          type="button"
          class="text-[13px] font-bold text-[var(--g)]"
          @click="$router.push('/warga/' + id + '/tambah-anggota')"
        >
          + Tambah
        </button>
      </div>

      <div class="px-1 mb-6">
        <div
          v-for="a in detail.anggota || []"
          :key="a.id"
          class="flex items-center gap-3 py-3"
        >
          <div
            class="w-10 h-10 rounded-full bg-[var(--card2)] text-[var(--text)] grid place-items-center text-[12px] font-bold shrink-0 overflow-hidden"
          >
            <img
              v-if="a.foto"
              :src="mediaUrl(a.foto)"
              alt=""
              class="w-full h-full object-cover"
            />
            <span v-else>{{ init(a.nama) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-[15px] m-0 truncate">{{ a.nama }}</p>
            <p class="text-[12px] text-[var(--mut)] m-0 truncate">
              {{ a.hubungan }}<span v-if="a.status && a.status !== 'aktif'"> · {{ a.status }}</span>
            </p>
          </div>
          <button
            v-if="a.status === 'aktif'"
            type="button"
            class="text-[12px] font-bold text-red-600 shrink-0 px-1"
            :disabled="hapusAnggotaId === a.id"
            @click="openHapusAnggota(a)"
          >
            {{ hapusAnggotaId === a.id ? '…' : 'Hapus' }}
          </button>
        </div>
        <p
          v-if="!(detail.anggota || []).length"
          class="text-[13px] text-[var(--mut)] text-center py-4 m-0"
        >
          Belum ada anggota
        </p>
      </div>

      <!-- Menu aksi: style profil warga (ikon + label, tanpa card) -->
      <p class="text-[13px] font-semibold text-[var(--mut)] mb-1 px-1">Aksi</p>
      <div class="px-1 mb-4">
        <button
          v-for="m in menus"
          :key="m.label"
          type="button"
          class="w-full flex flex-row items-center gap-3 px-2 py-3 text-left active:opacity-70 transition-opacity disabled:opacity-50"
          :disabled="m.disabled"
          @click="m.action ? m.action() : $router.push(m.to)"
        >
          <span
            class="w-5 shrink-0 grid place-items-center"
            :class="m.danger ? 'text-red-600' : 'text-[var(--mut)]'"
          >
            <component :is="m.icon" :size="18" stroke-width="1.75" />
          </span>
          <div class="flex-1 min-w-0">
            <p
              class="font-medium text-[14px] m-0 leading-snug"
              :class="m.danger ? 'text-red-600' : 'text-[var(--text)]'"
            >
              {{ m.label }}
            </p>
            <p v-if="m.desc" class="text-[11px] text-[var(--mut)] m-0 mt-0.5 leading-snug">
              {{ m.desc }}
            </p>
          </div>
          <ChevronRight
            :size="16"
            class="shrink-0 opacity-60"
            :class="m.danger ? 'text-red-600' : 'text-[var(--mut)]'"
          />
        </button>
      </div>

      <p v-if="aksiMsg" class="text-[13px] text-center px-2 mb-4" :class="aksiOk ? 'text-[var(--g)]' : 'text-red-600'">
        {{ aksiMsg }}
      </p>
    </template>

    <!-- Modal reset PIN -->
    <div
      v-if="modal === 'resetPin'"
      class="fixed inset-0 z-50 flex items-end justify-center bg-black/40"
      @click.self="modal = null"
    >
      <div class="w-full max-w-[560px] rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
        <h2 class="text-[17px] font-extrabold m-0 mb-1">Reset PIN</h2>
        <p class="text-[13px] text-[var(--mut)] m-0 mb-4 leading-relaxed">
          PIN keluarga <span class="font-semibold text-[var(--text)]">{{ namaKepala }}</span>
          ({{ alamatLabel }}) akan direset ke <span class="font-bold text-[var(--text)]">123456</span>.
        </p>
        <button
          type="button"
          class="w-full min-h-12 rounded-full font-bold text-white bg-[var(--g)] disabled:opacity-60 mb-2"
          :disabled="resetting"
          @click="confirmResetPin"
        >
          {{ resetting ? 'Mereset…' : 'Ya, reset PIN' }}
        </button>
        <button
          type="button"
          class="w-full min-h-12 rounded-full font-bold bg-[var(--card2)]"
          :disabled="resetting"
          @click="modal = null"
        >
          Batal
        </button>
      </div>
    </div>

    <!-- Modal tandai pindah -->
    <div
      v-if="modal === 'pindah'"
      class="fixed inset-0 z-50 flex items-end justify-center bg-black/40"
      @click.self="modal = null"
    >
      <div class="w-full max-w-[560px] rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
        <h2 class="text-[17px] font-extrabold m-0 mb-1 text-red-600">Tandai pindah</h2>
        <p class="text-[13px] text-[var(--mut)] m-0 mb-4 leading-relaxed">
          Keluarga <span class="font-semibold text-[var(--text)]">{{ namaKepala }}</span>
          ({{ alamatLabel }}) akan ditandai pindah. Lanjut ke form pindah?
        </p>
        <button
          type="button"
          class="w-full min-h-12 rounded-full font-bold text-white bg-red-600 disabled:opacity-60 mb-2"
          @click="goPindah"
        >
          Lanjut tandai pindah
        </button>
        <button
          type="button"
          class="w-full min-h-12 rounded-full font-bold bg-[var(--card2)]"
          @click="modal = null"
        >
          Batal
        </button>
      </div>
    </div>

    <!-- Modal hapus keluarga -->
    <div
      v-if="modal === 'hapusKeluarga'"
      class="fixed inset-0 z-50 flex items-end justify-center bg-black/40"
      @click.self="modal = null"
    >
      <div class="w-full max-w-[560px] rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
        <h2 class="text-[17px] font-extrabold m-0 mb-1 text-red-600">Hapus keluarga</h2>
        <p class="text-[13px] text-[var(--mut)] m-0 mb-3 leading-relaxed">
          Hapus <span class="font-semibold text-[var(--text)]">{{ namaKepala }}</span>
          ({{ alamatLabel }})? Akun warga nonaktif, anggota keluar. Riwayat iuran/kas tetap tersimpan.
        </p>
        <label class="block text-[13px] font-semibold text-[var(--mut)] mb-1">
          Ketik <span class="text-red-600">HAPUS</span> untuk konfirmasi
        </label>
        <input
          v-model="hapusConfirmText"
          type="text"
          class="w-full min-h-12 px-4 rounded-full bg-[var(--search)] border-0 outline-none text-[15px] mb-3"
          placeholder="HAPUS"
          autocomplete="off"
        />
        <button
          type="button"
          class="w-full min-h-12 rounded-full font-bold text-white bg-red-600 disabled:opacity-60 mb-2"
          :disabled="hapusKeluargaLoading || hapusConfirmText.trim().toUpperCase() !== 'HAPUS'"
          @click="confirmHapusKeluarga"
        >
          {{ hapusKeluargaLoading ? 'Menghapus…' : 'Ya, hapus keluarga' }}
        </button>
        <button
          type="button"
          class="w-full min-h-12 rounded-full font-bold bg-[var(--card2)]"
          :disabled="hapusKeluargaLoading"
          @click="modal = null"
        >
          Batal
        </button>
      </div>
    </div>

    <!-- Modal hapus anggota -->
    <div
      v-if="modal === 'hapusAnggota' && anggotaTarget"
      class="fixed inset-0 z-50 flex items-end justify-center bg-black/40"
      @click.self="modal = null"
    >
      <div class="w-full max-w-[560px] rounded-t-[24px] bg-[var(--bg)] p-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
        <h2 class="text-[17px] font-extrabold m-0 mb-1 text-red-600">Hapus anggota</h2>
        <p class="text-[13px] text-[var(--mut)] m-0 mb-3 leading-relaxed">
          Hapus <span class="font-semibold text-[var(--text)]">{{ anggotaTarget.nama }}</span>?
          Status jadi keluar (riwayat tetap).
        </p>
        <div v-if="perluKepalaBaru" class="mb-3">
          <p class="text-[13px] font-semibold m-0 mb-2">Pilih kepala keluarga baru</p>
          <button
            v-for="x in aktifLain"
            :key="x.id"
            type="button"
            class="w-full flex items-center gap-3 px-3 py-2.5 mb-1 rounded-[12px] text-left border"
            :class="kepalaBaruId === x.id
              ? 'border-[var(--g)] bg-[var(--g)]/10'
              : 'border-[var(--line)] bg-[var(--card)]'"
            @click="kepalaBaruId = x.id"
          >
            <span class="font-semibold text-[14px]">{{ x.nama }}</span>
            <span class="text-[12px] text-[var(--mut)]">{{ x.hubungan }}</span>
          </button>
        </div>
        <button
          type="button"
          class="w-full min-h-12 rounded-full font-bold text-white bg-red-600 disabled:opacity-60 mb-2"
          :disabled="hapusAnggotaId === anggotaTarget.id || (perluKepalaBaru && !kepalaBaruId)"
          @click="confirmHapusAnggota"
        >
          {{ hapusAnggotaId === anggotaTarget.id ? 'Menghapus…' : 'Ya, hapus anggota' }}
        </button>
        <button
          type="button"
          class="w-full min-h-12 rounded-full font-bold bg-[var(--card2)]"
          :disabled="!!hapusAnggotaId"
          @click="modal = null"
        >
          Batal
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ChevronRight, UserPlus, Users, Pencil, KeyRound,
  Receipt, FileWarning, MapPin, Trash2,
} from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import { getKeluarga, resetPin, hapusKeluarga, hapusAnggota } from '@shared/services/warga.js'
import { mediaUrl } from '@shared/services/upload.js'
import { useToast } from '@shared/composables/useToast.js'

const route = useRoute()
const router = useRouter()
const { success } = useToast()

const id = computed(() => Number(route.params.id))
const loading = ref(true)
const err = ref('')
const detail = ref({})
const resetting = ref(false)
const aksiMsg = ref('')
const aksiOk = ref(true)
const hapusKeluargaLoading = ref(false)
const hapusAnggotaId = ref(null)
const fotoKepalaBroken = ref(false)

const modal = ref(null) // resetPin | pindah | hapusKeluarga | hapusAnggota
const hapusConfirmText = ref('')
const anggotaTarget = ref(null)
const kepalaBaruId = ref(null)

const namaKepala = computed(() => {
  const list = detail.value.anggota || []
  const k = list.find((a) => String(a.hubungan || '').toLowerCase().includes('kepala'))
  return k?.nama || list[0]?.nama || '—'
})
const alamatLabel = computed(() => detail.value.alamat_label || detail.value.alamat || '—')
const inisial = computed(() => init(namaKepala.value))
const fotoKepala = computed(() => {
  if (fotoKepalaBroken.value) return ''
  const list = detail.value.anggota || []
  const k = list.find((a) => String(a.hubungan || '').toLowerCase().includes('kepala'))
  const f = k?.foto || list[0]?.foto
  return f ? mediaUrl(f) : ''
})
const penanda = computed(() => {
  if (detail.value.mulai_bulan_depan) return 'Mulai bulan depan'
  if (detail.value.belum_ganti_pin) return 'Belum ganti PIN'
  if (detail.value.data_belum_lengkap) return 'Data belum lengkap'
  return ''
})

const aktifLain = computed(() => {
  const targetId = anggotaTarget.value?.id
  return (detail.value.anggota || []).filter(
    (x) => x.status === 'aktif' && x.id !== targetId,
  )
})
const perluKepalaBaru = computed(() => {
  const a = anggotaTarget.value
  if (!a) return false
  const isKepala = String(a.hubungan || '').toLowerCase().includes('kepala')
  return isKepala && aktifLain.value.length > 0
})

const menus = computed(() => [
  {
    label: 'Tambah anggota',
    desc: 'Tambah orang di keluarga ini',
    icon: UserPlus,
    to: '/warga/' + id.value + '/tambah-anggota',
  },
  {
    label: 'Status anggota',
    desc: 'Aktif, keluar, atau meninggal',
    icon: Users,
    to: '/warga/' + id.value + '/status-anggota',
  },
  {
    label: 'Edit data keluarga',
    desc: 'Nama, alamat, dan foto',
    icon: Pencil,
    to: '/warga/' + id.value + '/edit',
  },
  {
    label: resetting.value ? 'Mereset PIN…' : 'Reset PIN',
    desc: 'Kembalikan PIN ke 123456',
    icon: KeyRound,
    action: () => { modal.value = 'resetPin' },
    disabled: resetting.value,
  },
  {
    label: 'Lihat tagihan / catat bayar',
    desc: 'Tagihan iuran keluarga ini',
    icon: Receipt,
    to: '/keuangan/tagihan/' + id.value,
  },
  {
    label: 'Tagihan khusus',
    desc: 'Iuran khusus untuk keluarga',
    icon: FileWarning,
    to: '/warga/' + id.value + '/tagihan-khusus',
  },
  {
    label: 'Tandai pindah',
    desc: 'Keluarga pindah dari RT',
    icon: MapPin,
    danger: true,
    action: () => { modal.value = 'pindah' },
  },
  {
    label: hapusKeluargaLoading.value ? 'Menghapus…' : 'Hapus keluarga',
    desc: 'Nonaktifkan keluarga ini',
    icon: Trash2,
    danger: true,
    action: () => {
      hapusConfirmText.value = ''
      modal.value = 'hapusKeluarga'
    },
    disabled: hapusKeluargaLoading.value,
  },
])

function init(nama) {
  return String(nama || '?')
    .split(/\s+/)
    .map((w) => w[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

async function load() {
  loading.value = true
  err.value = ''
  fotoKepalaBroken.value = false
  const res = await getKeluarga(id.value)
  loading.value = false
  if (!res.ok) {
    err.value = res.error || 'Gagal memuat.'
    return
  }
  detail.value = res.data || {}
}

async function confirmResetPin() {
  aksiMsg.value = ''
  resetting.value = true
  const res = await resetPin(id.value)
  resetting.value = false
  if (!res.ok) {
    aksiOk.value = false
    aksiMsg.value = res.error || 'Gagal reset PIN.'
    return
  }
  modal.value = null
  aksiOk.value = true
  aksiMsg.value = 'PIN direset ke 123456.'
  success('PIN direset')
  load()
}

function goPindah() {
  modal.value = null
  router.push('/warga/' + id.value + '/pindah')
}

async function confirmHapusKeluarga() {
  if (hapusConfirmText.value.trim().toUpperCase() !== 'HAPUS') return
  aksiMsg.value = ''
  hapusKeluargaLoading.value = true
  const res = await hapusKeluarga(id.value, { alasan: 'Dihapus pengurus' })
  hapusKeluargaLoading.value = false
  if (!res.ok) {
    aksiOk.value = false
    aksiMsg.value = res.error || 'Gagal menghapus.'
    return
  }
  modal.value = null
  success('Keluarga dihapus')
  router.replace('/warga')
}

function openHapusAnggota(a) {
  anggotaTarget.value = a
  kepalaBaruId.value = null
  modal.value = 'hapusAnggota'
}

async function confirmHapusAnggota() {
  const a = anggotaTarget.value
  if (!a) return
  if (perluKepalaBaru.value && !kepalaBaruId.value) return
  aksiMsg.value = ''
  hapusAnggotaId.value = a.id
  const res = await hapusAnggota(a.id, {
    alasan: 'Dihapus pengurus',
    kepala_baru_id: kepalaBaruId.value || undefined,
  })
  hapusAnggotaId.value = null
  if (!res.ok) {
    aksiOk.value = false
    aksiMsg.value = res.error || 'Gagal menghapus anggota.'
    return
  }
  modal.value = null
  anggotaTarget.value = null
  success('Anggota dihapus')
  load()
}

onMounted(load)
</script>
