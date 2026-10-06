import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import '@fontsource/plus-jakarta-sans/400.css'
import '@fontsource/plus-jakarta-sans/500.css'
import '@fontsource/plus-jakarta-sans/600.css'
import '@fontsource/plus-jakarta-sans/700.css'
import '@fontsource/plus-jakarta-sans/800.css'
import '@shared/styles/main.css'

createApp(App).use(router).mount('#app')
