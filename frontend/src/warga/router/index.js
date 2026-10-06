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
import PlaceholderView from '../views/PlaceholderView.vue'

const routes = [
  { path: '/login', name: 'login', component: LoginView, meta: { public: true } },
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', name: 'home', component: HomeView },
      { path: 'warga', name: 'warga', component: WargaView },
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
      { path: 'program', name: 'program', component: PlaceholderView, meta: { title: 'Program RT' } },
      { path: 'galeri', name: 'galeri', component: PlaceholderView, meta: { title: 'Galeri RT' } },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 } },
})

router.beforeEach((to) => {
  const { isLoggedIn, restore, user } = useAuth()
  restore('warga')
  if (!to.meta.public && !isLoggedIn.value) return { name: 'login' }
  if (to.name === 'login' && isLoggedIn.value) return { name: 'home' }
  // Paksa ganti PIN
  if (isLoggedIn.value && user.value?.harus_ganti_kredensial && to.name !== 'ganti-pin') {
    return { name: 'ganti-pin' }
  }
})

export default router
