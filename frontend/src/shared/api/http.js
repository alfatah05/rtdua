/**
 * Wrapper fetch ke /api. Cookie sesi ikut otomatis (same-origin).
 * Tidak dipanggil langsung dari halaman — lewat services/*.
 */
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

  if (!res.ok || json?.ok === false) {
    const err = new Error(json?.error || json?.message || 'Permintaan gagal')
    err.status = res.status
    err.payload = json
    throw err
  }
  return json
}
