/* Handler push — di-import workbox SW via importScripts (jangan dihapus). */
self.addEventListener('push', (event) => {
  let data = { title: 'rtdua', body: '', url: '/notifikasi', tag: 'rtdua' }
  try {
    if (event.data) data = { ...data, ...event.data.json() }
  } catch (_) {}
  event.waitUntil(
    self.registration.showNotification(data.title || 'rtdua', {
      body: data.body || '',
      data: { url: data.url || '/notifikasi' },
      icon: '/icons/app-icon.png',
      badge: '/icons/app-icon.png',
      tag: data.tag || 'rtdua',
      renotify: true,
    })
  )
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const url = event.notification.data?.url || '/notifikasi'
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
      for (const c of list) {
        if (c.url && 'focus' in c) {
          if (typeof c.navigate === 'function') {
            try { c.navigate(url) } catch (_) {}
          }
          return c.focus()
        }
      }
      if (clients.openWindow) return clients.openWindow(url)
    })
  )
})
