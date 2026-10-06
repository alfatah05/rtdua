import { mockPengaturan } from '../mock/pengaturan.js'

export async function getPengaturan() {
  return { ok: true, data: { ...mockPengaturan } }
}

export async function getBlok() {
  return { ok: true, data: mockPengaturan.blok.filter((b) => b.aktif) }
}
