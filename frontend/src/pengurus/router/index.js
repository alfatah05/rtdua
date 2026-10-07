import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@shared/composables/useAuth.js'
import MainLayout from '../layouts/MainLayout.vue'
import LoginView from '../views/LoginView.vue'
import HomeView from '../views/HomeView.vue'
import WargaView from '../views/WargaView.vue'
import WargaFilterView from '../views/WargaFilterView.vue'
import WargaDetailView from '../views/WargaDetailView.vue'
import WargaTambahView from '../views/WargaTambahView.vue'
import WargaEksporView from '../views/WargaEksporView.vue'
import KeuanganView from '../views/KeuanganView.vue'
import IuranView from '../views/IuranView.vue'
import TagihanDetailView from '../views/TagihanDetailView.vue'
import CatatBayarView from '../views/CatatBayarView.vue'
import CatatIuranPilihView from '../views/CatatIuranPilihView.vue'
import PermintaanDetailView from '../views/PermintaanDetailView.vue'
import KasFormView from '../views/KasFormView.vue'
import LaporanView from '../views/LaporanView.vue'
import AktivitasView from '../views/AktivitasView.vue'
import AktivitasDetailView from '../views/AktivitasDetailView.vue'
import ProfilView from '../views/ProfilView.vue'
import LainnyaView from '../views/LainnyaView.vue'
import NotifikasiView from '../views/NotifikasiView.vue'
import PengumumanListView from '../views/PengumumanListView.vue'
import PengumumanFormView from '../views/PengumumanFormView.vue'
import ProgramListView from '../views/ProgramListView.vue'
import GaleriListView from '../views/GaleriListView.vue'
import StrukturView from '../views/StrukturView.vue'
import RondaView from '../views/RondaView.vue'
import RondaMalamView from '../views/RondaMalamView.vue'
import PengaturanWargaView from '../views/PengaturanWargaView.vue'
import PengaturanAplikasiView from '../views/PengaturanAplikasiView.vue'
import KelolaPengurusView from '../views/KelolaPengurusView.vue'
import KonfirmasiTransferView from '../views/KonfirmasiTransferView.vue'
import TolakPermintaanView from '../views/TolakPermintaanView.vue'
import IuranKhususView from '../views/IuranKhususView.vue'
import DetailKasView from '../views/DetailKasView.vue'
import PlaceholderView from '../views/PlaceholderView.vue'
import RondaJadwalTetapView from '../views/RondaJadwalTetapView.vue'
import RondaJadwalKhususView from '../views/RondaJadwalKhususView.vue'
import ProgramFormView from '../views/ProgramFormView.vue'
import GaleriAlbumFormView from '../views/GaleriAlbumFormView.vue'
import FilterKasView from '../views/FilterKasView.vue'
import FilterIuranView from '../views/FilterIuranView.vue'
import AngkatPengurusView from '../views/AngkatPengurusView.vue'
import KelolaPengurusDetailView from '../views/KelolaPengurusDetailView.vue'
import EksporIuranView from '../views/EksporIuranView.vue'
import BatalDendaView from '../views/BatalDendaView.vue'

const routes = [
  { path: '/login', name: 'login', component: LoginView, meta: { public: true } },
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', name: 'home', component: HomeView },
      { path: 'warga', name: 'warga', component: WargaView },
      { path: 'warga/filter', component: WargaFilterView },
      { path: 'warga/tambah', component: WargaTambahView },
      { path: 'warga/ekspor', component: WargaEksporView },
      { path: 'warga/scan-kk', component: PlaceholderView, meta: { title: 'Scan KK' } },
      { path: 'warga/:id', component: WargaDetailView },
      { path: 'warga/:id/edit', component: PlaceholderView, meta: { title: 'Edit keluarga' } },
      { path: 'warga/:id/tambah-anggota', component: PlaceholderView, meta: { title: 'Tambah anggota' } },
      { path: 'warga/:id/meninggal', component: PlaceholderView, meta: { title: 'Tandai meninggal' } },
      { path: 'warga/:id/pindah', component: PlaceholderView, meta: { title: 'Pindah keluarga' } },
      { path: 'warga/:id/reset-pin', component: PlaceholderView, meta: { title: 'Reset PIN' } },

      { path: 'keuangan', name: 'keuangan', component: KeuanganView },
      { path: 'keuangan/iuran', component: IuranView },
      { path: 'keuangan/tagihan/:id', component: TagihanDetailView },
      { path: 'keuangan/tagihan/:id/bayar', component: CatatBayarView },
      { path: 'keuangan/tagihan/:id/batal-denda', component: BatalDendaView },
      { path: 'keuangan/permintaan/:id', component: PermintaanDetailView },
      { path: 'keuangan/permintaan/:id/konfirmasi', component: KonfirmasiTransferView },
      { path: 'keuangan/permintaan/:id/tolak', component: TolakPermintaanView },
      { path: 'keuangan/kas-masuk', component: KasFormView },
      { path: 'keuangan/kas-keluar', component: KasFormView },
      { path: 'keuangan/kas/:id', component: DetailKasView },
      { path: 'keuangan/filter-kas', component: FilterKasView },
      { path: 'keuangan/filter-iuran', component: FilterIuranView },
      { path: 'keuangan/ekspor-iuran', component: EksporIuranView },
      { path: 'keuangan/laporan', component: LaporanView },
      { path: 'keuangan/iuran-khusus', component: IuranKhususView },
      { path: 'keuangan/catat', component: CatatIuranPilihView },

      { path: 'aktivitas', name: 'aktivitas', component: AktivitasView },
      { path: 'aktivitas/:id', component: AktivitasDetailView },
      { path: 'profil', name: 'profil', component: ProfilView },
      { path: 'lainnya', name: 'lainnya', component: LainnyaView },
      { path: 'notifikasi', name: 'notifikasi', component: NotifikasiView },

      { path: 'konten/pengumuman', component: PengumumanListView },
      { path: 'konten/pengumuman/tambah', component: PengumumanFormView },
      { path: 'konten/pengumuman/:id', component: PengumumanFormView },
      { path: 'konten/program', component: ProgramListView },
      { path: 'konten/program/tambah', component: ProgramFormView },
      { path: 'konten/program/:id', component: ProgramFormView },
      { path: 'konten/galeri', component: GaleriListView },
      { path: 'konten/galeri/tambah', component: GaleriAlbumFormView },
      { path: 'konten/galeri/:id', component: PlaceholderView, meta: { title: 'Isi album' } },
      { path: 'konten/struktur', component: StrukturView },

      { path: 'ronda', component: RondaView },
      { path: 'ronda/malam/:date', component: RondaMalamView },
      { path: 'ronda/malam/edit', component: PlaceholderView, meta: { title: 'Ganti keluarga' } },
      { path: 'ronda/jadwal-tetap', component: RondaJadwalTetapView },
      { path: 'ronda/jadwal-khusus', component: RondaJadwalKhususView },
      { path: 'ronda/isi-otomatis', component: PlaceholderView, meta: { title: 'Isi otomatis' } },

      { path: 'pengaturan-warga', component: PengaturanWargaView },
      { path: 'pengaturan-aplikasi', component: PengaturanAplikasiView },
      { path: 'kelola-pengurus', component: KelolaPengurusView },
      { path: 'kelola-pengurus/angkat', component: AngkatPengurusView },
      { path: 'kelola-pengurus/:id', component: KelolaPengurusDetailView },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 } },
})

router.beforeEach((to) => {
  const { isLoggedIn, restore, isKetua } = useAuth()
  restore('pengurus')
  if (!to.meta.public && !isLoggedIn.value) return { name: 'login' }
  if (to.name === 'login' && isLoggedIn.value) return { name: 'home' }
  if ((to.path.startsWith('/pengaturan-aplikasi') || to.path.startsWith('/kelola-pengurus')) && !isKetua.value) {
    return { name: 'home' }
  }
})

export default router
