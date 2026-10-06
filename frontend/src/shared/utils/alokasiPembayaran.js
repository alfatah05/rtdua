/**
 * Fungsi murni alokasi pembayaran (dokumen 02).
 * Prioritas jenis: iuran khusus → kas → denda ronda.
 * Di dalam tiap jenis: bulan (periode) terlama dulu.
 * Boleh cicil; boleh bayar lebih (sisa = kelebihan / minus).
 *
 * @param {Array<{id:number, jenis:string, periode:string, sisa:number}>} tagihan
 *   sisa = nominal belum terbayar (bilangan bulat rupiah)
 * @param {number} nominalBayar bilangan bulat rupiah
 * @returns {{ potongan: Array<{tagihan_id, jumlah}>, sisaBayar: number }}
 *   sisaBayar > 0 = kelebihan bayar
 */
const URUTAN_JENIS = ['khusus', 'kas', 'denda_ronda']

export function alokasiPembayaran(tagihan, nominalBayar) {
  let sisa = Math.max(0, Math.round(Number(nominalBayar) || 0))
  const list = (tagihan || [])
    .map((t) => ({
      id: t.id,
      jenis: t.jenis,
      periode: t.periode || '',
      sisa: Math.max(0, Math.round(Number(t.sisa != null ? t.sisa : t.nominal) || 0)),
    }))
    .filter((t) => t.sisa > 0)
    .sort((a, b) => {
      const ja = URUTAN_JENIS.indexOf(a.jenis)
      const jb = URUTAN_JENIS.indexOf(b.jenis)
      if (ja !== jb) return (ja < 0 ? 99 : ja) - (jb < 0 ? 99 : jb)
      return String(a.periode).localeCompare(String(b.periode))
    })

  const potongan = []
  for (const t of list) {
    if (sisa <= 0) break
    const ambil = Math.min(t.sisa, sisa)
    if (ambil > 0) {
      potongan.push({ tagihan_id: t.id, jumlah: ambil, jenis: t.jenis, periode: t.periode })
      sisa -= ambil
    }
  }
  return { potongan, sisaBayar: sisa }
}
