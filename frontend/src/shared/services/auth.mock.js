import { normalisasiUsernameWarga } from '../utils/format.js'

function delay(ms) {
  return new Promise((r) => setTimeout(r, ms))
}

export async function loginWarga(username, pin) {
  await delay(300)
  const u = normalisasiUsernameWarga(username)
  if (!u) return { ok: false, error: 'Username wajib diisi' }
  if (!pin || String(pin).length < 4) return { ok: false, error: 'PIN minimal 4 angka' }
  if (u === 'tutup') return { ok: false, error: 'Aplikasi belum dibuka' }
  const mustChange = u === 'baru' || String(pin) === '123456'
  return {
    ok: true,
    mustChange,
    user: {
      id: 1,
      role: 'warga',
      username: u,
      nama: 'Keluarga ' + u.toUpperCase(),
      keluarga_id: 1,
      harus_ganti_kredensial: mustChange,
    },
  }
}

export async function loginPengurus(username, password) {
  await delay(300)
  const u = String(username || '').trim().toLowerCase()
  if (!u) return { ok: false, error: 'Username wajib diisi' }
  if (!password || String(password).length < 4) return { ok: false, error: 'Password minimal 4 karakter' }
  const isKetuaUser = u === 'ketua' || u === 'developer'
  return {
    ok: true,
    user: {
      id: isKetuaUser ? 1 : 2,
      role: isKetuaUser ? 'ketua' : 'pengurus',
      username: u,
      nama: isKetuaUser ? 'Budi Ketua' : 'Ani Pengurus',
      jabatan: isKetuaUser ? 'Ketua RT' : 'Bendahara',
      is_developer: u === 'developer',
      harus_ganti_kredensial: String(password) === '12345678',
    },
  }
}

export async function logout() { return { ok: true } }
export async function me() { return { ok: true, user: null } }
export async function changeCredential() { return { ok: true } }
