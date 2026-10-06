import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@shared/composables/useAuth.js'
import MainLayout from '../layouts/MainLayout.vue'
import LoginView from '../views/LoginView.vue'
import HomeView from '../views/HomeView.vue'
import ProfilView from '../views/ProfilView.vue'
import PlaceholderView from '../views/PlaceholderView.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { public: true },
  },
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', name: 'home', component: HomeView, meta: { title: 'Beranda' } },
      { path: 'warga', name: 'warga', component: PlaceholderView, meta: { title: 'Warga' } },
      { path: 'keuangan', name: 'keuangan', component: PlaceholderView, meta: { title: 'Keuangan' } },
      { path: 'profil', name: 'profil', component: ProfilView, meta: { title: 'Profil' } },
      { path: 'pengumuman', name: 'pengumuman', component: PlaceholderView, meta: { title: 'Pengumuman' } },
      { path: 'ronda', name: 'ronda', component: PlaceholderView, meta: { title: 'Jadwal Ronda' } },
      { path: 'program', name: 'program', component: PlaceholderView, meta: { title: 'Program RT' } },
      { path: 'galeri', name: 'galeri', component: PlaceholderView, meta: { title: 'Galeri RT' } },
      { path: 'struktur', name: 'struktur', component: PlaceholderView, meta: { title: 'Struktur Pengurus' } },
      { path: 'bantuan', name: 'bantuan', component: PlaceholderView, meta: { title: 'Bantuan' } },
      { path: 'notifikasi', name: 'notifikasi', component: PlaceholderView, meta: { title: 'Notifikasi' } },
      { path: 'ganti-pin', name: 'ganti-pin', component: PlaceholderView, meta: { title: 'Ganti PIN' } },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach((to) => {
  const { isLoggedIn, restore } = useAuth()
  restore('warga')
  if (!to.meta.public && !isLoggedIn.value) {
    return { name: 'login' }
  }
  if (to.name === 'login' && isLoggedIn.value) {
    return { name: 'home' }
  }
})

export default router
