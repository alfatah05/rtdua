import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['icons/warga-192.png', 'icons/warga-512.png', 'icons/warga-180.png'],
      manifest: {
        id: '/?app=warga',
        name: 'Warga rtdua',
        short_name: 'Warga rtdua',
        description: 'Aplikasi warga rtdua',
        theme_color: '#0A8F44',
        background_color: '#FFFFFF',
        display: 'standalone',
        orientation: 'portrait',
        start_url: '/',
        scope: '/',
        lang: 'id',
        icons: [
          { src: '/icons/warga-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
          { src: '/icons/warga-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
          { src: '/icons/warga-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        skipWaiting: true,
        clientsClaim: true,
        cleanupOutdatedCaches: true,
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2,webmanifest}'],
        navigateFallback: 'index.html',
        navigateFallbackDenylist: [/^\/api(?:\/|$)/],
        runtimeCaching: [
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/api'),
            handler: 'NetworkOnly',
          },
          {
            urlPattern: ({ request }) =>
              request.destination === 'script' || request.destination === 'style',
            handler: 'NetworkFirst',
            options: {
              cacheName: 'rtdua-assets-dev',
              networkTimeoutSeconds: 4,
              expiration: {
                maxEntries: 48,
                maxAgeSeconds: 60 * 30,
              },
            },
          },
          {
            urlPattern: ({ request }) => request.destination === 'image',
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'rtdua-img-dev',
              expiration: { maxEntries: 64, maxAgeSeconds: 60 * 60 * 6 },
            },
          },
        ],
        importScripts: ['sw-push.js'],
      },
    }),
  ],
  root: resolve(__dirname, 'src/warga'),
  publicDir: resolve(__dirname, 'public'),
  build: {
    outDir: resolve(__dirname, 'dist/warga'),
    emptyOutDir: true,
  },
  resolve: {
    alias: {
      '@shared': resolve(__dirname, 'src/shared'),
      '@warga': resolve(__dirname, 'src/warga'),
    },
  },
  server: { port: 5173 },
})
