import { createApp } from 'vue'
import App from './App.vue'
import router from './router/index.js'
import { authStore } from './stores/auth.js'
import '../css/app.css'

// Intercepteur global : gère 401 (token expiré) et 403 (licence invalide)
const originalFetch = window.fetch
window.fetch = async (...args) => {
    const res = await originalFetch(...args)

    // 401 : token expiré ou invalide → déconnexion (API uniquement)
    if (res.status === 401) {
        const url = typeof args[0] === 'string' ? args[0] : args[0]?.url ?? ''
        if (url.includes('/api/') && !url.includes('/api/login')) {
            authStore.token = null
            authStore.user  = null
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            router.push({ name: 'login' })
        }
        return res
    }

    // 403 : licence invalide
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
