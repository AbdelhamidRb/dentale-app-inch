import { createApp } from 'vue'
import App from './App.vue'
import router from './router/index.js'
import '../css/app.css'

// Pas besoin d'importer le store ici
const app = createApp(App)
app.use(router)
app.mount('#app')