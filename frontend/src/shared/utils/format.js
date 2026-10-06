/** Format rupiah bilangan bulat → "Rp 12.450.000" */
export function formatRp(n) {
  const x = Math.round(Number(n) || 0)
  const abs = Math.abs(x)
  const s = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
  return (x < 0 ? '-Rp ' : 'Rp ') + s
}

/** Username warga: hilangkan spasi, lowercase */
export function normalisasiUsernameWarga(u) {
  return String(u || '').trim().toLowerCase().replace(/\s+/g, '')
}
