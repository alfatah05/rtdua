import { USE_MOCK } from '../config/dataSource.js'
import * as real from './notifikasi.real.js'

const mockList = [
  {
    id: 1,
    jenis: 'umum',
    judul: 'Contoh notifikasi',
    isi: 'Aktifkan sumber data real (Stage 12).',
    tautan: null,
    dibaca: false,
    dibuat_pada: new Date().toISOString().slice(0, 19).replace('T', ' '),
  },
]

export async function listNotifikasi(opts) {
  if (USE_MOCK.notifikasi) {
    return { ok: true, data: { items: mockList, belum_dibaca: 1 } }
  }
  return real.listNotifikasi(opts)
}

export async function badgeNotifikasi() {
  if (USE_MOCK.notifikasi) return { ok: true, data: { belum_dibaca: 1 } }
  return real.badgeNotifikasi()
}

export async function bacaNotifikasi(id) {
  if (USE_MOCK.notifikasi) return { ok: true }
  return real.bacaNotifikasi(id)
}

export async function bacaSemuaNotifikasi() {
  if (USE_MOCK.notifikasi) return { ok: true, data: { jumlah: 0 } }
  return real.bacaSemuaNotifikasi()
}

export async function getVapidPublic() {
  if (USE_MOCK.notifikasi) return { ok: false, error: 'mock' }
  return real.getVapidPublic()
}

export async function subscribePush(subscription, perangkat) {
  if (USE_MOCK.notifikasi) return { ok: true }
  return real.subscribePush(subscription, perangkat)
}

export async function unsubscribePush(endpoint) {
  if (USE_MOCK.notifikasi) return { ok: true }
  return real.unsubscribePush(endpoint)
}
