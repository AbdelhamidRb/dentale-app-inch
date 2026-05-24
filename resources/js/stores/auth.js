import { reactive } from 'vue'
import router from '../router/index.js'

export const authStore = reactive({
  token:          localStorage.getItem('token') || null,
  user:           JSON.parse(localStorage.getItem('user') || 'null'),
  licenseInvalid: false,
  licenseReason:  null,
  licenseMac:     null,

  async login(email, password) {
    const res = await fetch('/api/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    })

    const data = await res.json()

    if (!res.ok) {
      throw new Error(data.message || 'Identifiants incorrects')
    }

    this.token = data.token
    this.user  = data.user
    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))

    router.push({ name: 'dashboard' })
  },

  // ─── Logout : supprime le token et redirige vers login ─────────
  async logout() {
    try {
      await fetch('/api/logout', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json'
        }
      })
    } finally {
      this.token = null
      this.user  = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      router.push({ name: 'login' })
    }
  },

  isDentist()   { return this.user?.role === 'DENTIST'    },
  isAssistant() { return this.user?.role === 'ASSISTANT'  },

  handleLicenseError(data) {
    this.licenseInvalid = true;
    this.licenseReason  = data.reason  || null;
    this.licenseMac     = data.mac     || null;
    router.push({ name: 'license' });
  },
})