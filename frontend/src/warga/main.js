import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import '@fontsource/plus-jakarta-sans/400.css'
import '@fontsource/plus-jakarta-sans/500.css'
import '@fontsource/plus-jakarta-sans/600.css'
import '@fontsource/plus-jakarta-sans/700.css'
import '@fontsource/plus-jakarta-sans/800.css'
import '@shared/styles/main.css'
import AppLazyImg from '@shared/components/AppLazyImg.vue'

const app = createApp(App)
app.component('AppLazyImg', AppLazyImg)
app.use(router)
app.mount('#app')
