// Couche API agenda — tous les appels HTTP centralisés ici
const BASE = "/api/appointments";

function headers() {
    return {
        Authorization: `Bearer ${localStorage.getItem("token")}`,
        "Content-Type": "application/json",
        Accept: "application/json",
    };
}

async function handle(res) {
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || "Erreur serveur");
    return data;
}

export const appointmentsApi = {
    // ─── RDV d'un jour précis ──────────────────────────────────────
    async getByDate(date) {
        const res = await fetch(`${BASE}?date=${date}`, { headers: headers() });
        return handle(res);
    },

    // ─── Créer un RDV ─────────────────────────────────────────────
    async create(data) {
        const res = await fetch(BASE, {
            method: "POST",
            headers: headers(),
            body: JSON.stringify(data),
        });
        return handle(res);
    },

    // ─── Modifier un RDV ──────────────────────────────────────────
    async update(id, data) {
        const res = await fetch(`${BASE}/${id}`, {
            method: "PUT",
            headers: headers(),
            body: JSON.stringify(data),
        });
        return handle(res);
    },

    // ─── Changer le statut uniquement ─────────────────────────────
    async updateStatus(id, status) {
        const res = await fetch(`${BASE}/${id}/status`, {
            method: "PATCH",
            headers: headers(),
            body: JSON.stringify({ status }),
        });
        return handle(res);
    },

    // ─── Annuler un RDV ───────────────────────────────────────────
    async cancel(id) {
        const res = await fetch(`${BASE}/${id}`, {
            method: "DELETE",
            headers: headers(),
        });
        // Certains DELETE retournent 204 sans body — on gère les deux cas
        if (res.status === 204) return { message: "Supprimé." };
        return handle(res);
    },

    // ─── Liste des actes du catalogue ─────────────────────────────
    async getCatalogActs() {
        const res = await fetch("/api/catalog-acts", { headers: headers() });
        return handle(res);
    },
};
