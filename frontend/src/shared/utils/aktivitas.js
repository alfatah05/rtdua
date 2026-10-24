import {
  Activity, ArrowDownLeft, ArrowUpRight, ArrowLeftRight,
  Banknote, Users, UserCog, KeyRound,
  Megaphone, ClipboardList, Images, Settings, Shield,
  Gift, Wallet, Layers, CreditCard, X, LogIn, LogOut,
} from 'lucide-vue-next'

function formatRp(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID')
}

export function labelAksi(a) {
  const aksi = String(a?.aksi || '')
  const s = a?.sesudah || {}

  const map = {
    catat_pembayaran: 'Mencatat pembayaran',
    konfirmasi_transfer: 'Mengonfirmasi transfer',
    tolak_transfer: 'Menolak transfer',
    ajukan_transfer: 'Mengajukan transfer',
    batal_pembayaran: 'Membatalkan pembayaran',
    batal_denda: 'Membatalkan denda',
    kas_masuk: 'Kas masuk',
    kas_keluar: 'Kas keluar',
    kas_alokasi_pos: 'Alokasi antar pos',
    batal_kas: 'Membatalkan transaksi kas',
    batal_kas_alokasi: 'Membatalkan alokasi kas',
    tambah_keluarga: 'Menambah keluarga',
    ubah_keluarga: 'Mengubah data keluarga',
    hapus_keluarga: 'Menghapus keluarga',
    tambah_anggota: 'Menambah anggota',
    ubah_anggota: 'Mengubah anggota',
    ganti_kepala: 'Mengganti kepala keluarga',
    pindah_keluarga: 'Memindahkan keluarga',
    reset_pin_warga: 'Reset PIN warga',
    angkat_pengurus: 'Mengangkat pengurus',
    ubah_pengurus: 'Mengubah data pengurus',
    nonaktif_pengurus_otomatis: 'Nonaktif pengurus',
    buat_pengumuman: 'Membuat pengumuman',
    ubah_pengumuman: 'Mengubah pengumuman',
    hapus_pengumuman: 'Menghapus pengumuman',
    tambah_program: 'Menambah program',
    ubah_program: 'Mengubah program',
    hapus_program: 'Menghapus program',
    buat_iuran_khusus: 'Membuat iuran khusus',
    ubah_iuran_khusus: 'Mengubah iuran khusus',
    batal_iuran_khusus: 'Membatalkan iuran khusus',
    tambah_iuran_komponen: 'Menambah komponen iuran',
    ubah_iuran_komponen: 'Mengubah komponen iuran',
    hapus_iuran_komponen: 'Menghapus komponen iuran',
    terapkan_komponen_bulan_ini: 'Menerapkan komponen bulan ini',
    ubah_pengaturan_warga: 'Mengubah tarif iuran',
    ubah_pengaturan_aplikasi: 'Mengubah pengaturan aplikasi',
    ubah_profil: 'Mengubah profil',
    ganti_kredensial: 'Mengganti kredensial',
    login: 'Login',
    logout: 'Logout',
    reward_undi: 'Mengundi reward',
    reward_klaim: 'Klaim reward',
    reward_batal_batch: 'Membatalkan undian',
    reward_buat_event: 'Membuat event reward',
    reward_ubah_event: 'Mengubah event reward',
    reward_hapus_event: 'Menghapus event reward',
    reward_reset_siklus: 'Reset siklus reward',
    sub_pos_masuk: 'Sub pos masuk',
    sub_pos_keluar: 'Sub pos keluar',
    sub_pos_alokasi: 'Alokasi sub pos',
    sub_pos_batal_mutasi: 'Batal mutasi sub pos',
    kas_pos_buat: 'Membuat pos kas',
    kas_pos_ubah: 'Mengubah pos kas',
    kas_pos_nonaktif: 'Nonaktif pos kas',
    tambah_rekening: 'Menambah rekening',
    ubah_rekening: 'Mengubah rekening',
    hapus_rekening: 'Menghapus rekening',
    tambah_album: 'Menambah album',
    ubah_album: 'Mengubah album',
    hapus_album: 'Menghapus album',
    hapus_foto_galeri: 'Menghapus foto galeri',
    tambah_kader_konten: 'Menambah konten',
    ubah_kader_konten: 'Mengubah konten',
    hapus_kader_konten: 'Menghapus konten',
  }

  if (aksi === 'kas_alokasi_pos') {
    const dari = s.dari_pos_nama || 'pos'
    const ke = s.ke_pos_nama || 'pos'
    const nom = s.nominal != null ? formatRp(s.nominal) : ''
    return `Alokasi ${dari} → ${ke}${nom ? ' · ' + nom : ''}`
  }
  if (aksi === 'kas_masuk' || (aksi.startsWith('kas_') && aksi.includes('masuk'))) {
    const nom = s.nominal != null ? formatRp(s.nominal) : ''
    return nom ? `Kas masuk · ${nom}` : 'Kas masuk'
  }
  if (aksi === 'kas_keluar' || (aksi.startsWith('kas_') && aksi.includes('keluar'))) {
    const nom = s.nominal != null ? formatRp(s.nominal) : ''
    return nom ? `Kas keluar · ${nom}` : 'Kas keluar'
  }
  if (aksi.startsWith('status_anggota_')) return 'Mengubah status anggota'
  if (map[aksi]) return map[aksi]
  if (aksi.startsWith('kas_')) return aksi.replace(/^kas_/, 'Kas ').replace(/_/g, ' ')
  return aksi.replace(/_/g, ' ')
}

export function iconMeta(aksiRaw) {
  const aksi = String(aksiRaw || '')

  if (aksi === 'kas_alokasi_pos' || aksi === 'sub_pos_alokasi') {
    return { icon: ArrowLeftRight, bg: 'rgba(14,165,233,.15)', color: '#0284C7' }
  }
  if (aksi === 'kas_masuk' || aksi === 'sub_pos_masuk' || aksi === 'catat_pembayaran' || aksi === 'konfirmasi_transfer') {
    return { icon: ArrowDownLeft, bg: 'rgba(34,184,99,.15)', color: '#0B8442' }
  }
  if (aksi === 'kas_keluar' || aksi === 'sub_pos_keluar') {
    return { icon: ArrowUpRight, bg: 'rgba(239,68,68,.15)', color: '#DC2626' }
  }
  if (aksi.startsWith('batal_') || aksi === 'tolak_transfer') {
    return { icon: X, bg: 'rgba(239,68,68,.15)', color: '#DC2626' }
  }
  if (aksi.includes('pembayaran') || aksi.includes('transfer') || aksi.includes('iuran') || aksi.includes('denda')) {
    return { icon: Banknote, bg: 'rgba(34,184,99,.15)', color: '#0B8442' }
  }
  if (aksi.includes('keluarga') || aksi.includes('anggota') || aksi.includes('kepala') || aksi.includes('pindah')) {
    return { icon: Users, bg: 'rgba(6,182,212,.15)', color: '#0891B2' }
  }
  if (aksi.includes('pengurus') || aksi === 'angkat_pengurus') {
    return { icon: UserCog, bg: 'rgba(100,116,139,.15)', color: '#475569' }
  }
  if (aksi.includes('pin') || aksi.includes('kredensial') || aksi === 'login' || aksi === 'logout') {
    return { icon: aksi === 'login' ? LogIn : aksi === 'logout' ? LogOut : KeyRound, bg: 'rgba(245,158,11,.15)', color: '#B45309' }
  }
  if (aksi.includes('pengumuman')) {
    return { icon: Megaphone, bg: 'rgba(168,85,247,.15)', color: '#7C3AED' }
  }
  if (aksi.includes('program') || aksi.includes('kader_konten')) {
    return { icon: ClipboardList, bg: 'rgba(249,115,22,.15)', color: '#EA580C' }
  }
  if (aksi.includes('album') || aksi.includes('galeri') || aksi.includes('foto')) {
    return { icon: Images, bg: 'rgba(236,72,153,.15)', color: '#DB2777' }
  }
  if (aksi.includes('reward')) {
    return { icon: Gift, bg: 'rgba(245,158,11,.15)', color: '#D97706' }
  }
  if (aksi.includes('sub_pos')) {
    return { icon: Layers, bg: 'rgba(6,182,212,.15)', color: '#0891B2' }
  }
  if (aksi.includes('kas_pos') || aksi.includes('kas')) {
    return { icon: Wallet, bg: 'rgba(34,184,99,.15)', color: '#0B8442' }
  }
  if (aksi.includes('rekening')) {
    return { icon: CreditCard, bg: 'rgba(59,130,246,.15)', color: '#2563EB' }
  }
  if (aksi.includes('pengaturan') || aksi.includes('profil')) {
    return { icon: Settings, bg: 'rgba(100,116,139,.15)', color: '#475569' }
  }
  if (aksi.includes('ronda') || aksi.includes('denda')) {
    return { icon: Shield, bg: 'rgba(59,130,246,.15)', color: '#2563EB' }
  }
  return { icon: Activity, bg: 'rgba(100,116,139,.12)', color: '#64748B' }
}

export function listTitle(a) {
  const nama = a?.pelaku || 'Sistem'
  return `${nama} · ${labelAksi(a)}`
}

export function formatWaktu(w) {
  if (!w) return ''
  const d = new Date(String(w).replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return w
  return d.toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function formatJsonBlock(obj) {
  if (obj == null) return null
  if (typeof obj !== 'object') return String(obj)
  const lines = []
  for (const [k, v] of Object.entries(obj)) {
    if (v == null || v === '') continue
    const key = k.replace(/_/g, ' ')
    let val = v
    if (typeof v === 'object') val = JSON.stringify(v)
    else if (typeof v === 'number' && /nominal|jumlah|saldo|total/i.test(k)) val = formatRp(v)
    lines.push(`${key}: ${val}`)
  }
  return lines.length ? lines.join('\n') : null
}
