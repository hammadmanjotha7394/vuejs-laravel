import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createUnhead } from '@unhead/vue'
import router from '@/router'
import App from '@/App.vue'
import '@/assets/css/main.css'

const app = createApp(App)
const pinia = createPinia()
const head = createUnhead()

app.use(pinia)
app.use(head)
app.use(router)

app.mount('#app')
