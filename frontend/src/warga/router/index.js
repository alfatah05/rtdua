import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@shared/composables/useAuth.js'
import MainLayout from '../layouts/MainLayout.vue'
import LoginView from '../views/LoginView.vue'
import HomeView from '../views/HomeView.vue'
import WargaView from '../views/WargaView.vue'
import KeuanganView from '../views/KeuanganView.vue'
import ProfilView from '../views/ProfilView.vue'
import PengumumanView from '../views/PengumumanView.vue'
import PengumumanDetailView from '../views/PengumumanDetailView.vue'
import RincianIuranView from '../views/RincianIuranView.vue'
import TransferView from '../views/TransferView.vue'
import RondaView from '../views/RondaView.vue'
import BantuanView from '../views/BantuanView.vue'
import StrukturView from '../views/StrukturView.vue'
import NotifikasiView from '../views/NotifikasiView.vue'
import GantiPinView from '../views/GantiPinView.vue'
import ProgramListView from '../views/ProgramListView.vue'
import ProgramDetailView from '../views/ProgramDetailView.vue'
import GaleriListView from '../views/GaleriListView.vue'
import GaleriDetailView from '../views/GaleriDetailView.vue'
import DataKeluargaView from '../views/DataKeluargaView.vue'

const routes = [
  { path: '/login', name: 'login', component: LoginView, meta: { public: true } },
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', name: 'home', component: HomeView },
      { path: 'warga', name: 'warga', component: WargaView },
      { path: 'data-keluarga', name: 'data-keluarga', component: DataKeluargaView },
      { path: 'keuangan', name: 'keuangan', component: KeuanganView },
      { path: 'profil', name: 'profil', component: ProfilView },
      { path: 'pengumuman', name: 'pengumuman', component: PengumumanView },
      { path: 'pengumuman/:id', name: 'pengumuman-detail', component: PengumumanDetailView },
      { path: 'rincian-iuran', name: 'rincian-iuran', component: RincianIuranView },
      { path: 'transfer', name: 'transfer', component: TransferView },
      { path: 'ronda', name: 'ronda', component: RondaView },
      { path: 'bantuan', name: 'bantuan', component: BantuanView },
      { path: 'struktur', name: 'struktur', component: StrukturView },
      { path: 'notifikasi', name: 'notifikasi', component: NotifikasiView },
      { path: 'ganti-pin', name: 'ganti-pin', component: GantiPinView },
      { path: 'program', name: 'program', component: ProgramListView },
      { path: 'program/:id', name: 'program-detail', component: ProgramDetailView },
      { path: 'galeri', name: 'galeri', component: GaleriListView },
      { path: 'galeri/:id', name: 'galeri-detail', component: GaleriDetailView },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 } },
})

router.beforeEach(async (to) => {
  const { isLoggedIn, ensureSession, user } = useAuth()
  await ensureSession('warga')

  if (!to.meta.public && !isLoggedIn.value) {
    return { name: 'login', query: to.fullPath !== '/' ? { redirect: to.fullPath } : undefined }
  }
  if (to.name === 'login' && isLoggedIn.value) {
    return { name: 'home' }
  }
  if (isLoggedIn.value && user.value?.harus_ganti_kredensial && to.name !== 'ganti-pin') {
    return { name: 'ganti-pin' }
  }
})

export default router
