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
import PermintaanDetailView from '../views/PermintaanDetailView.vue'
import KasFormView from '../views/KasFormView.vue'
import LaporanView from '../views/LaporanView.vue'
import AktivitasView from '../views/AktivitasView.vue'
import AktivitasDetailView from '../views/AktivitasDetailView.vue'
import ProfilView from '../views/ProfilView.vue'
import LainnyaView from '../views/LainnyaView.vue'
import NotifikasiView from '../views/NotifikasiView.vue'
import PlaceholderView from '../views/PlaceholderView.vue'

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
      { path: 'keuangan/tagihan/:id/batal-denda', component: PlaceholderView, meta: { title: 'Batalkan denda' } },
      { path: 'keuangan/permintaan/:id', component: PermintaanDetailView },
      { path: 'keuangan/permintaan/:id/konfirmasi', component: PlaceholderView, meta: { title: 'Konfirmasi transfer' } },
      { path: 'keuangan/permintaan/:id/tolak', component: PlaceholderView, meta: { title: 'Tolak permintaan' } },
      { path: 'keuangan/kas-masuk', component: KasFormView },
      { path: 'keuangan/kas-keluar', component: KasFormView },
      { path: 'keuangan/kas/:id', component: PlaceholderView, meta: { title: 'Detail kas' } },
      { path: 'keuangan/filter-kas', component: PlaceholderView, meta: { title: 'Filter riwayat kas' } },
      { path: 'keuangan/filter-iuran', component: PlaceholderView, meta: { title: 'Filter iuran' } },
      { path: 'keuangan/ekspor-iuran', component: PlaceholderView, meta: { title: 'Ekspor iuran' } },
      { path: 'keuangan/laporan', component: LaporanView },
      { path: 'keuangan/iuran-khusus', component: PlaceholderView, meta: { title: 'Iuran khusus' } },
      { path: 'keuangan/catat', component: PlaceholderView, meta: { title: 'Catat iuran' } },

      { path: 'aktivitas', name: 'aktivitas', component: AktivitasView },
      { path: 'aktivitas/:id', component: AktivitasDetailView },
      { path: 'profil', name: 'profil', component: ProfilView },
      { path: 'lainnya', name: 'lainnya', component: LainnyaView },
      { path: 'notifikasi', name: 'notifikasi', component: NotifikasiView },

      { path: 'pengaturan-warga', component: PlaceholderView, meta: { title: 'Tarif iuran & denda' } },
      { path: 'ronda', component: PlaceholderView, meta: { title: 'Jadwal ronda' } },
      { path: 'konten/pengumuman', component: PlaceholderView, meta: { title: 'Pengumuman' } },
      { path: 'konten/program', component: PlaceholderView, meta: { title: 'Program RT' } },
      { path: 'konten/galeri', component: PlaceholderView, meta: { title: 'Galeri' } },
      { path: 'konten/struktur', component: PlaceholderView, meta: { title: 'Struktur pengurus' } },
      { path: 'pengaturan-aplikasi', component: PlaceholderView, meta: { title: 'Pengaturan aplikasi' } },
      { path: 'kelola-pengurus', component: PlaceholderView, meta: { title: 'Kelola pengurus' } },
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
  if ((to.path === '/pengaturan-aplikasi' || to.path === '/kelola-pengurus') && !isKetua.value) {
    return { name: 'home' }
  }
})

export default router
