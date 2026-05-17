// Couche API — un seul endroit pour tous les appels HTTP patients
// Si l'URL change demain, tu la changes ici seulement

const BASE = '/api/patients'

// Helper interne : construit les headers avec le token Sanctum
function headers(isFormData = false) {
  const h = { Authorization: `Bearer ${localStorage.getItem('token')}` }
  if (!isFormData) h['Content-Type'] = 'application/json'
  return h
}

// Helper interne : gère les erreurs Laravel uniformément
async function handle(res) {
  const data = await res.json()
  if (!res.ok) throw new Error(data.message || 'Erreur serveur')
  return data
}

export const patientsApi = {

  // ─── Liste paginée avec recherche ──────────────────────────────
  async list(params = {}) {
    const query = new URLSearchParams()
    if (params.search)   query.set('search',   params.search)
    if (params.status)   query.set('status',   params.status)
    if (params.archived) query.set('archived', '1')
    if (params.page)     query.set('page',     params.page)

    const res = await fetch(`${BASE}?${query}`, { headers: headers() })
    return handle(res)
  },

  // ─── Fiche complète d'un patient ───────────────────────────────
  async get(id) {
    const res = await fetch(`${BASE}/${id}`, { headers: headers() })
    return handle(res)
  },

  // ─── Créer un patient ──────────────────────────────────────────
  async create(data) {
    const res = await fetch(BASE, {
      method:  'POST',
      headers: headers(),
      body:    JSON.stringify(data)
    })
    return handle(res)
  },

  // ─── Modifier un patient ───────────────────────────────────────
  async update(id, data) {
    const res = await fetch(`${BASE}/${id}`, {
      method:  'PUT',
      headers: headers(),
      body:    JSON.stringify(data)
    })
    return handle(res)
  },

  // ─── Archiver un patient ───────────────────────────────────────
  async archive(id) {
    const res = await fetch(`${BASE}/${id}`, {
      method:  'DELETE',
      headers: headers()
    })
    return handle(res)
  },

  // ─── Réactiver un patient archivé ─────────────────────────────
  async restore(id) {
    const res = await fetch(`${BASE}/${id}/restore`, {
      method:  'POST',
      headers: headers()
    })
    return handle(res)
  },

  // ─── Alertes médicales ─────────────────────────────────────────
  async addAlert(patientId, data) {
    const res = await fetch(`${BASE}/${patientId}/alerts`, {
      method:  'POST',
      headers: headers(),
      body:    JSON.stringify(data)
    })
    return handle(res)
  },

  async deleteAlert(patientId, alertId) {
    const res = await fetch(`${BASE}/${patientId}/alerts/${alertId}`, {
      method:  'DELETE',
      headers: headers()
    })
    return handle(res)
  },

  // ─── Documents ────────────────────────────────────────────────
  async uploadDocument(patientId, file, type) {
    const form = new FormData()
    form.append('file', file)
    form.append('type', type)
    const res = await fetch(`${BASE}/${patientId}/documents`, {
      method:  'POST',
      headers: headers(true), // FormData : pas de Content-Type manuel
      body:    form
    })
    return handle(res)
  },

  async deleteDocument(patientId, docId) {
    const res = await fetch(`${BASE}/${patientId}/documents/${docId}`, {
      method:  'DELETE',
      headers: headers()
    })
    return handle(res)
  },
}