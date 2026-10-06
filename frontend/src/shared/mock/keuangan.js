/** Tagihan & pembayaran dummy untuk uji alokasi dan UI. */
export const mockTagihan = [
  { id: 101, keluarga_id: 1, periode: '2026-09', jenis: 'kas', nominal: 40000, sisa: 0 },
  { id: 102, keluarga_id: 1, periode: '2026-10', jenis: 'kas', nominal: 40000, sisa: 40000 },
  { id: 103, keluarga_id: 1, periode: '2026-09', jenis: 'denda_ronda', nominal: 10000, sisa: 10000 },
  { id: 201, keluarga_id: 3, periode: '2026-08', jenis: 'kas', nominal: 40000, sisa: 40000 },
  { id: 202, keluarga_id: 3, periode: '2026-09', jenis: 'denda_ronda', nominal: 10000, sisa: 10000 },
  { id: 203, keluarga_id: 3, periode: '2026-09', jenis: 'denda_ronda', nominal: 10000, sisa: 10000 },
  { id: 204, keluarga_id: 3, periode: '2026-10', jenis: 'denda_ronda', nominal: 10000, sisa: 10000 },
  { id: 301, keluarga_id: 2, periode: '2026-10', jenis: 'kas', nominal: 40000, sisa: 0 },
]

export const mockKasSaldo = 12500000
