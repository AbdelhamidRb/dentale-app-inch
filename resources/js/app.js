import { createApp } from 'vue'
import App from './App.vue'
import router from './router/index.js'
import { authStore } from './stores/auth.js'
import '../css/app.css'

// Intercepteur global : si une réponse API retourne 403 licence_invalid
const originalFetch = window.fetch
window.fetch = async (...args) => {
    const res = await originalFetch(...args)
    if (res.status === 403) {
        const clone = res.clone()
        try {
            const data = await clone.json()
            if (data.error === 'license_invalid') {
                authStore.handleLicenseError(data)
            }
        } catch { /* pas du JSON */ }
    }
    return res
}

const app = createApp(App)
app.use(router)
app.mount('#app')
