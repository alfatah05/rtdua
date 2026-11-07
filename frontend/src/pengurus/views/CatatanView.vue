<template>
  <div class="catatan-page">
    <AppBackHeader title="Catatan" />

    <div class="catatan-body">
      <AppPageSpinner v-if="loading && !rows.length" class="!py-10" />

      <div v-else-if="mode === 'search' && !loading && !rows.length" class="empty-search">
        Tidak ada data untuk “{{ searchQ }}”
      </div>

      <div v-else ref="tableScroll" class="table-scroll">
        <table class="catatan-table">
          <thead>
            <tr>
              <th class="col-no sticky-col">No</th>
              <th class="col-tgl">Tanggal</th>
              <th class="col-nama">Nama</th>
              <th class="col-alamat">Alamat</th>
              <th class="col-kep">Keperluan</th>
              <th class="col-ket">Keterangan</th>
              <th class="col-dok">Dokumentasi</th>
            </tr>
          </thead>
          <tbody :key="tableKey">
            <tr v-for="row in rows" :key="row.id" :data-row-id="row.id">
              <td class="col-no sticky-col muted-no">{{ row.no }}</td>

              <td class="col-tgl cell-pad" @click="openDate(row)">
                <span class="cell-text">{{ formatTgl(row.tanggal) }}</span>
                <input
                  :ref="(el) => setDateRef(row.id, el)"
                  type="date"
                  class="hidden-date"
                  :value="row.tanggal || ''"
                  @change="onDateChange(row, $event)"
                />
              </td>

              <td class="col-nama">
                <div
                  class="cell-edit"
                  contenteditable="true"
                  spellcheck="false"
                  @blur="onBlur($event, row, 'nama')"
                  @keydown.enter.prevent="blurEl($event)"
                  v-text="row.nama"
                ></div>
              </td>
              <td class="col-alamat">
                <div
                  class="cell-edit"
                  contenteditable="true"
                  spellcheck="false"
                  @blur="onBlur($event, row, 'alamat')"
                  @keydown.enter.prevent="blurEl($event)"
                  v-text="row.alamat"
                ></div>
              </td>
              <td class="col-kep">
                <div
                  class="cell-edit"
                  contenteditable="true"
                  spellcheck="false"
                  @blur="onBlur($event, row, 'keperluan')"
                  @keydown.enter.prevent="blurEl($event)"
                  v-text="row.keperluan"
                ></div>
              </td>
              <td class="col-ket">
                <div
                  class="cell-edit"
                  contenteditable="true"
                  spellcheck="false"
                  @blur="onBlur($event, row, 'keterangan')"
                  v-text="row.keterangan"
                ></div>
              </td>
              <td class="col-dok">
                <button
                  v-if="row.dokumentasi"
                  type="button"
                  class="link-foto"
                  @click="previewFoto = row.dokumentasi"
                >
                  Lihat foto
                </button>
                <button
                  v-else
                  type="button"
                  class="link-foto muted"
                  @click="pickFoto(row)"
                >
                  + Foto
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div ref="sentinel" class="sentinel" />
      </div>
    </div>

    <div class="catatan-float-bar" role="navigation" aria-label="Navigasi halaman">
      <button
        type="button"
        class="circle-btn"
        :class="{ 'is-disabled': !canPrev }"
        aria-label="Halaman sebelumnya"
        @click.stop="onPrev"
      >
        <ChevronLeft :size="20" class="chev-l" />
      </button>

      <div class="search-pill">
        <input
          v-model="searchInput"
          type="text"
          inputmode="search"
          placeholder="cari data"
          enterkeyhint="search"
          autocomplete="off"
          @keydown.enter.prevent="doSearch"
        />
        <button
          v-if="searchInput || mode === 'search'"
          type="button"
          class="clear-btn"
          aria-label="Hapus pencarian"
          @click.stop="clearSearch"
        >
          <X :size="16" />
        </button>
      </div>

      <button
        type="button"
        class="circle-btn"
        :class="{ 'is-disabled': !canNext }"
        aria-label="Halaman berikutnya"
        @click.stop="onNext"
      >
        <ChevronRight :size="20" class="chev-r" />
      </button>
    </div>

    <p v-if="mode === 'book'" class="page-indicator">
      Halaman {{ page }} / {{ totalPages }}
    </p>

    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFile" />

    <Teleport to="body">
      <div
        v-if="previewFoto"
        class="foto-overlay"
        @click.self="previewFoto = null"
      >
        <button type="button" class="foto-close" @click="previewFoto = null">Tutup</button>
        <img :src="mediaUrl(previewFoto)" alt="Dokumentasi" />
        <button type="button" class="foto-change" @click="pickFotoFromPreview">Ganti foto</button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next'
import AppBackHeader from '@shared/components/AppBackHeader.vue'
import AppPageSpinner from '@shared/components/AppPageSpinner.vue'
import {
  listCatatan, searchCatatan, patchCatatanField,
} from '@shared/services/catatan.js'
import { uploadGambar, mediaUrl } from '@shared/services/upload.js'
import { useToast } from '@shared/composables/useToast.js'

const { success, error: toastError } = useToast()

const PER = 50
const mode = ref('book')
const rows = ref([])
const page = ref(1)
const totalPages = ref(1)
const loading = ref(false)
const searchInput = ref('')
const searchQ = ref('')
const searchDone = ref(false)
const previewFoto = ref(null)
const fileInput = ref(null)
const fotoTargetId = ref(null)
const dateRefs = {}
const sentinel = ref(null)
const tableScroll = ref(null)
let observer = null
let prevHtmlBg = ''
let prevBodyBg = ''
let navLock = false

const canPrev = computed(() => mode.value === 'book' && Number(page.value) > 1)
const canNext = computed(() => mode.value === 'book' && Number(page.value) < Number(totalPages.value))
const tableKey = computed(() => `${mode.value}-p${page.value}`)
let suppressBlur = false

function setDateRef(id, el) {
  if (el) dateRefs[id] = el
}

function formatTgl(v) {
  if (!v) return ''
  const p = String(v).slice(0, 10).split('-')
  if (p.length !== 3) return v
  return `${p[2]}/${p[1]}/${p[0]}`
}

function blurEl(e) {
  e.target.blur()
}

function scrollTableTop() {
  nextTick(() => {
    const box = tableScroll.value
    if (box) {
      box.scrollLeft = 0
      box.scrollTop = 0
    }
    window.scrollTo({ top: 0, behavior: 'auto' })
  })
}

/** Scroll baris ke tengah area tabel saja (bukan window) — jaga sticky thead. */
function scrollRowInTable(rowId) {
  const box = tableScroll.value
  if (!box || rowId == null) return
  const el = box.querySelector(`[data-row-id="${rowId}"]`)
  if (!el) return
  const cRect = box.getBoundingClientRect()
  const eRect = el.getBoundingClientRect()
  const delta = (eRect.top - cRect.top) - (cRect.height / 2) + (eRect.height / 2)
  box.scrollTop += delta
}

async function loadPage(p, { scrollToFocus = false } = {}) {
  const target = Math.max(0, Number(p) || 0)
  loading.value = true
  suppressBlur = true
  try {
    const res = await listCatatan({ page: target, per_page: PER })
    if (!res.ok) {
      toastError(res.error || 'Gagal memuat')
      return
    }
    const d = res.data || {}
    const nextRows = Array.isArray(d.rows) ? d.rows : []
    page.value = Math.max(1, Number(d.page) || 1)
    totalPages.value = Math.max(1, Number(d.total_pages) || 1)
    mode.value = 'book'
    rows.value = nextRows.slice()

    await nextTick()
    if (scrollToFocus && d.focus_row_id) {
      requestAnimationFrame(() => {
        scrollRowInTable(d.focus_row_id)
      })
    } else {
      scrollTableTop()
    }
  } finally {
    loading.value = false
    await nextTick()
    setTimeout(() => { suppressBlur = false }, 50)
  }
}

function onPrev() {
  if (navLock || loading.value || mode.value !== 'book') return
  const cur = Number(page.value) || 1
  if (cur <= 1) return
  navLock = true
  Promise.resolve(loadPage(cur - 1, { scrollToFocus: false })).finally(() => {
    navLock = false
  })
}

function onNext() {
  if (navLock || loading.value || mode.value !== 'book') return
  const cur = Number(page.value) || 1
  const max = Number(totalPages.value) || 1
  if (cur >= max) return
  navLock = true
  Promise.resolve(loadPage(cur + 1, { scrollToFocus: false })).finally(() => {
    navLock = false
  })
}

async function openFocusPage() {
  await loadPage(0, { scrollToFocus: true })
}

async function doSearch() {
  const q = String(searchInput.value || '').trim()
  if (!q) {
    clearSearch()
    return
  }
  searchQ.value = q
  searchDone.value = false
  mode.value = 'search'
  loading.value = true
  suppressBlur = true
  try {
    const res = await searchCatatan(q, { limit: 80, offset: 0 })
    if (!res.ok) {
      toastError(res.error || 'Gagal cari')
      rows.value = []
      return
    }
    rows.value = Array.isArray(res.data?.rows) ? res.data.rows.slice() : []
    if (rows.value.length < 80) searchDone.value = true
    await nextTick()
    scrollTableTop()
  } finally {
    loading.value = false
    await nextTick()
    setTimeout(() => { suppressBlur = false }, 50)
  }
}

async function loadMoreSearch() {
  if (mode.value !== 'search' || loading.value || searchDone.value) return
  loading.value = true
  try {
    const off = rows.value.length
    const res = await searchCatatan(searchQ.value, { limit: 80, offset: off })
    if (!res.ok) return
    const more = Array.isArray(res.data?.rows) ? res.data.rows : []
    if (!more.length) {
      searchDone.value = true
      return
    }
    rows.value = rows.value.concat(more)
    if (more.length < 80) searchDone.value = true
  } finally {
    loading.value = false
  }
}

function clearSearch() {
  searchInput.value = ''
  searchQ.value = ''
  searchDone.value = false
  mode.value = 'book'
  openFocusPage()
}

async function onBlur(e, row, field) {
  if (suppressBlur) return
  if (!e?.target || !e.target.isConnected) return
  const val = (e.target.innerText || '').replace(/\u00a0/g, ' ').trimEnd()
  const prev = String(row[field] ?? '')
  if (val === prev) return
  row[field] = val
  const res = await patchCatatanField(row.id, field, val)
  if (!res.ok) {
    toastError(res.error || 'Gagal simpan')
    e.target.innerText = prev
    row[field] = prev
    return
  }
  if (res.data && typeof res.data === 'object') {
    const { _meta, ...rest } = res.data
    Object.assign(row, rest)
    if (_meta?.total_pages != null) {
      totalPages.value = Math.max(1, Number(_meta.total_pages))
    }
  }
  if (res.total_pages != null) {
    totalPages.value = Math.max(1, Number(res.total_pages))
  }
}

function openDate(row) {
  const el = dateRefs[row.id]
  if (!el) return
  try {
    if (typeof el.showPicker === 'function') el.showPicker()
    else el.click()
  } catch (_) {
    el.click()
  }
}

async function onDateChange(row, e) {
  const val = e.target.value || null
  const prev = row.tanggal
  row.tanggal = val
  const res = await patchCatatanField(row.id, 'tanggal', val)
  if (!res.ok) {
    toastError(res.error || 'Gagal simpan tanggal')
    row.tanggal = prev
    return
  }
  if (res.total_pages != null) {
    totalPages.value = Math.max(1, Number(res.total_pages))
  }
  if (res.data?._meta?.total_pages != null) {
    totalPages.value = Math.max(1, Number(res.data._meta.total_pages))
  }
}

function pickFoto(row) {
  fotoTargetId.value = row.id
  fileInput.value?.click()
}

function pickFotoFromPreview() {
  const row = rows.value.find((r) => r.dokumentasi === previewFoto.value)
  if (row) pickFoto(row)
}

async function onFile(e) {
  const f = e.target.files?.[0]
  e.target.value = ''
  const id = fotoTargetId.value
  if (!f || !id) return
  const up = await uploadGambar(f, 'catatan', { maxSide: 1600 })
  if (!up.ok) {
    toastError(up.error || 'Upload gagal')
    return
  }
  const path = up.data?.path || up.data?.url || ''
  if (!path) {
    toastError('Path foto kosong')
    return
  }
  const res = await patchCatatanField(id, 'dokumentasi', path)
  if (!res.ok) {
    toastError(res.error || 'Gagal simpan foto')
    return
  }
  const row = rows.value.find((r) => r.id === id)
  if (row) row.dokumentasi = path
  if (res.total_pages != null) {
    totalPages.value = Math.max(1, Number(res.total_pages))
  }
  if (res.data?._meta?.total_pages != null) {
    totalPages.value = Math.max(1, Number(res.data._meta.total_pages))
  }
  previewFoto.value = path
  success('Foto tersimpan')
}

function applyPageBg() {
  const bg = getComputedStyle(document.documentElement).getPropertyValue('--bg').trim() || '#fff'
  prevHtmlBg = document.documentElement.style.background
  prevBodyBg = document.body.style.background
  document.documentElement.style.background = bg
  document.documentElement.style.backgroundColor = bg
  document.body.style.background = bg
  document.body.style.backgroundColor = bg
}

function restorePageBg() {
  document.documentElement.style.background = prevHtmlBg
  document.documentElement.style.backgroundColor = prevHtmlBg
  document.body.style.background = prevBodyBg
  document.body.style.backgroundColor = prevBodyBg
}

onMounted(async () => {
  applyPageBg()
  await openFocusPage()
  observer = new IntersectionObserver(
    (entries) => {
      if (entries.some((x) => x.isIntersecting)) loadMoreSearch()
    },
    { root: null, rootMargin: '120px' },
  )
  await nextTick()
  if (sentinel.value) observer.observe(sentinel.value)
})

onUnmounted(() => {
  observer?.disconnect()
  restorePageBg()
})
</script>

<style scoped>
.catatan-page {
  height: 100dvh;
  height: 100svh;
  max-height: 100dvh;
  max-height: 100svh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background: var(--bg);
  background-color: var(--bg);
  padding-bottom: calc(88px + env(safe-area-inset-bottom, 0px));
  overscroll-behavior-y: none;
}

.catatan-body {
  padding: 0;
  margin: 0;
  background: var(--bg);
  background-color: var(--bg);
  flex: 1;
  min-height: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.empty-search {
  padding: 48px 20px;
  text-align: center;
  color: var(--mut);
  font-size: 14px;
}

.page-indicator {
  position: fixed;
  left: 0;
  right: 0;
  bottom: calc(56px + env(safe-area-inset-bottom, 0px));
  z-index: 9998;
  margin: 0;
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  color: var(--mut);
  pointer-events: none;
}

/* Satu-satunya area scroll — thead sticky relatif ke sini */
.table-scroll {
  width: 100vw;
  max-width: 100vw;
  margin-left: calc(50% - 50vw);
  flex: 1;
  min-height: 0;
  overflow: auto;
  -webkit-overflow-scrolling: touch;
  background: var(--bg);
  background-color: var(--bg);
}
.catatan-table {
  border-collapse: collapse;
  table-layout: fixed;
  width: max-content;
  min-width: 100%;
  font-size: 13px;
  margin: 0;
  background: var(--bg);
}
.catatan-table th,
.catatan-table td {
  border: 1px solid color-mix(in srgb, var(--text) 14%, transparent);
  padding: 0;
  vertical-align: top;
  background: var(--bg);
}
.catatan-table th {
  font-weight: 700;
  font-size: 11px;
  text-align: left;
  padding: 8px 6px;
  position: sticky;
  top: 0;
  z-index: 2;
  background: var(--card2);
  color: var(--mut);
  white-space: nowrap;
  box-shadow: 0 1px 0 color-mix(in srgb, var(--text) 14%, transparent);
}
.col-no { width: 44px; min-width: 44px; max-width: 44px; text-align: center !important; }
.col-tgl { width: 108px; min-width: 108px; max-width: 108px; }
.col-nama { width: 132px; min-width: 132px; max-width: 132px; }
.col-alamat { width: 112px; min-width: 112px; max-width: 112px; }
.col-kep { width: 132px; min-width: 132px; max-width: 132px; }
.col-ket { width: 168px; min-width: 168px; max-width: 168px; }
.col-dok { width: 96px; min-width: 96px; max-width: 96px; text-align: center; }

.sticky-col {
  position: sticky;
  left: 0;
  z-index: 1;
  background: var(--bg);
}
th.sticky-col {
  z-index: 4;
  top: 0;
  left: 0;
  background: var(--card2);
}
.muted-no {
  color: color-mix(in srgb, var(--text) 40%, transparent);
  font-size: 12px;
  text-align: center;
  padding: 8px 4px !important;
  vertical-align: middle;
}
.cell-edit {
  min-height: 36px;
  padding: 8px 6px;
  outline: none;
  white-space: pre-wrap;
  word-break: break-word;
  overflow-wrap: anywhere;
  line-height: 1.35;
}
.cell-edit:focus {
  background: color-mix(in srgb, var(--g) 10%, var(--bg));
}
.cell-text {
  display: block;
  padding: 8px 6px;
  min-height: 36px;
}
.cell-pad {
  position: relative;
  cursor: pointer;
}
.hidden-date {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
  pointer-events: none;
}
.link-foto {
  border: 0;
  background: transparent;
  color: #2563eb;
  font-size: 12px;
  font-weight: 600;
  padding: 8px 4px;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.link-foto.muted {
  color: var(--mut);
  text-decoration: none;
  font-weight: 500;
}
.sentinel { height: 1px; }
.hidden { display: none; }
</style>

<style>
.catatan-float-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 10px 14px calc(10px + env(safe-area-inset-bottom, 0px));
  pointer-events: auto;
  background: transparent;
  box-sizing: border-box;
}
.catatan-float-bar .circle-btn {
  width: 42px;
  height: 42px;
  border-radius: 9999px;
  border: 1px solid color-mix(in srgb, var(--text) 12%, transparent);
  background: var(--card);
  color: var(--text);
  display: grid;
  place-items: center;
  box-shadow: var(--sh);
  flex-shrink: 0;
  cursor: pointer;
}
.catatan-float-bar .circle-btn.is-disabled,
.catatan-float-bar .circle-btn:disabled {
  opacity: 1;
  color: var(--mut);
  cursor: default;
  box-shadow: none;
  border-color: color-mix(in srgb, var(--text) 8%, transparent);
}
.catatan-float-bar .circle-btn .chev-l {
  transform: translateX(-2px);
}
.catatan-float-bar .circle-btn .chev-r {
  transform: translateX(2px);
}
.catatan-float-bar .search-pill {
  flex: 1;
  max-width: 280px;
  display: flex;
  align-items: center;
  gap: 4px;
  min-height: 42px;
  padding: 0 6px 0 14px;
  border-radius: 9999px;
  background: var(--card);
  border: 1px solid color-mix(in srgb, var(--text) 10%, transparent);
  box-shadow: var(--sh);
}
.catatan-float-bar .search-pill input {
  flex: 1;
  min-width: 0;
  border: 0;
  outline: none;
  background: transparent;
  font-size: 14px;
  color: var(--text);
  min-height: 40px;
}
.catatan-float-bar .search-pill input::-webkit-search-cancel-button,
.catatan-float-bar .search-pill input::-webkit-search-decoration {
  -webkit-appearance: none;
  appearance: none;
  display: none;
}
.catatan-float-bar .search-pill input:focus {
  outline: none;
  box-shadow: none;
  border: 0;
}
.catatan-float-bar .clear-btn {
  width: 32px;
  height: 32px;
  border: 0;
  background: transparent;
  color: var(--mut);
  display: grid;
  place-items: center;
  border-radius: 9999px;
}
.foto-overlay {
  position: fixed;
  inset: 0;
  z-index: 110;
  background: rgba(0, 0, 0, 0.88);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
  gap: 12px;
}
.foto-overlay img {
  max-width: 100%;
  max-height: 75vh;
  object-fit: contain;
  border-radius: 12px;
}
.foto-close,
.foto-change {
  border: 0;
  border-radius: 9999px;
  padding: 10px 20px;
  font-weight: 700;
  font-size: 14px;
}
.foto-close {
  background: var(--card);
  color: var(--text);
}
.foto-change {
  background: transparent;
  color: #93c5fd;
  text-decoration: underline;
}
</style>
