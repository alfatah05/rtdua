/* Service worker minimal: tampilkan push & buka tautan saat diketuk */
self.addEventListener('push', (event) => {
  let data = { title: 'rtdua', body: '', url: '/notifikasi' }
  try {
    if (event.data) data = { ...data, ...event.data.json() }
  } catch (_) {}
  event.waitUntil(
    self.registration.showNotification(data.title || 'rtdua', {
      body: data.body || '',
      data: { url: data.url || '/notifikasi' },
      icon: '/icon-192.png',
      badge: '/icon-192.png',
    })
  )
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const url = event.notification.data?.url || '/notifikasi'
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
      for (const c of list) {
        if ('focus' in c) {
          c.navigate?.(url)
          return c.focus()
        }
      }
      if (clients.openWindow) return clients.openWindow(url)
    })
  )
})
