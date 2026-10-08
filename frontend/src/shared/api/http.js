/**
 * Wrapper fetch ke /api. Cookie sesi ikut otomatis (same-origin).
 * Tidak dipanggil langsung dari halaman — lewat services/*.
 *
 * HTTP 401 → event 'rtdua:unauthorized' (App.vue clear sesi + redirect login).
 */

let lastUnauthorizedAt = 0

function emitUnauthorized() {
  const now = Date.now()
  // debounce 1.5s supaya banyak request gagal bersamaan tidak spam redirect
  if (now - lastUnauthorizedAt < 1500) return
  lastUnauthorizedAt = now
  try {
    window.dispatchEvent(new CustomEvent('rtdua:unauthorized', { detail: { status: 401 } }))
  } catch (e) {}
}

export async function api(path, options = {}) {
  const opts = {
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    },
    ...options,
  }
  if (opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData)) {
    opts.body = JSON.stringify(opts.body)
  }

  const res = await fetch('/api' + path, opts)
  const text = await res.text()
  let json = null
  try {
    json = text ? JSON.parse(text) : null
  } catch {
    const snippet = (text || '').replace(/\s+/g, ' ').slice(0, 180)
    json = {
      ok: false,
      error: `Respons bukan JSON (HTTP ${res.status})${snippet ? ': ' + snippet : ''}`,
      data: null,
    }
  }

  // Sesi habis / belum login
  if (res.status === 401) {
    // jangan kick saat endpoint login/me sendiri (biar form login tetap jalan)
    const p = String(path || '')
    if (!p.startsWith('/auth/login') && !p.startsWith('/auth/me')) {
      emitUnauthorized()
    }
  }

  if (!res.ok || json?.ok === false) {
    const err = new Error(json?.error || json?.message || 'Permintaan gagal')
    err.status = res.status
    err.payload = json
    throw err
  }
  return json
}
