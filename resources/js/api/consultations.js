// Couche API consultations — tous les appels HTTP centralisés ici

const BASE = '/api/consultations'

function headers() {
  return {
    Authorization: `Bearer ${localStorage.getItem('token')}`,
    'Content-Type': 'application/json',
    Accept: 'application/json',
  }
}

async function handle(res) {
  const data = await res.json()
  if (!res.ok) throw new Error(data.message || 'Erreur serveur')
  return data
}

export const consultationsApi = {

  // ─── Liste filtrée ─────────────────────────────────────────────
  async list(params = {}) {
    const query = new URLSearchParams()
    if (params.status)     query.set('status',     params.status)
    if (params.patient_id) query.set('patient_id', params.patient_id)
    if (params.en_cours)   query.set('en_cours',   '1')
    if (params.page)       query.set('page',        params.page)

    const res = await fetch(`${BASE}?${query}`, { headers: headers() })
    return handle(res)
  },

  // ─── Fiche complète ────────────────────────────────────────────
  async get(id) {
    const res = await fetch(`${BASE}/${id}`, { headers: headers() })
    return handle(res)
  },

  // ─── Créer une consultation ────────────────────────────────────
  // payload = { patient_id, appointment_id?, status, notes, acts[] }
  // acts[] = [{ catalog_act_id, teeth[], price, notes }]
  async create(payload) {
    const res = await fetch(BASE, {
      method: 'POST',
      headers: headers(),
      body: JSON.stringify(payload),
    })
    return handle(res)
  },

  // ─── Modifier (actes + notes + statut) ────────────────────────
  async update(id, payload) {
    const res = await fetch(`${BASE}/${id}`, {
      method: 'PUT',
      headers: headers(),
      body: JSON.stringify(payload),
    })
    return handle(res)
  },

  // ─── Ajouter une séance (date) ─────────────────────────────────
  async addSession(id, date) {
    const res = await fetch(`${BASE}/${id}/session`, {
      method: 'POST',
      headers: headers(),
      body: JSON.stringify({ date }),
    })
    return handle(res)
  },

  // ─── Clôturer ──────────────────────────────────────────────────
  async close(id) {
    const res = await fetch(`${BASE}/${id}/close`, {
      method: 'PATCH',
      headers: headers(),
    })
    return handle(res)
  },

  // ─── Supprimer (dentiste uniquement) ───────────────────────────
  async destroy(id) {
    const res = await fetch(`${BASE}/${id}`, {
      method: 'DELETE',
      headers: headers(),
    })
    if (res.status === 200) return res.json()
    throw new Error('Erreur suppression')
  },
}