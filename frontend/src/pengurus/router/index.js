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
      { path: 'aktivitas', name: 'aktivitas', component: PlaceholderView, meta: { title: 'Aktivitas' } },
      { path: 'profil', name: 'profil', component: ProfilView, meta: { title: 'Profil' } },
      { path: 'lainnya', name: 'lainnya', component: PlaceholderView, meta: { title: 'Lainnya' } },
      { path: 'notifikasi', name: 'notifikasi', component: PlaceholderView, meta: { title: 'Notifikasi' } },
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
  restore('pengurus')
  if (!to.meta.public && !isLoggedIn.value) {
    return { name: 'login' }
  }
  if (to.name === 'login' && isLoggedIn.value) {
    return { name: 'home' }
  }
})

export default router
