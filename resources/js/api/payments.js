// resources/js/api/payments.js

const BASE = "/api/payments";

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

export const paymentsApi = {
    // Liste patients avec soldes
    // params: { status, search }
    async list(params = {}) {
        const q = new URLSearchParams();
        if (params.status) q.set("status", params.status);
        if (params.search) q.set("search", params.search);
        const res = await fetch(`${BASE}?${q}`, { headers: headers() });
        return handle(res);
    },

    // Fiche complète d'un patient
    async get(patientId) {
        const res = await fetch(`${BASE}/${patientId}`, { headers: headers() });
        return handle(res);
    },

    // Ajouter un versement
    async addTransaction(patientId, payload) {
        const res = await fetch(`${BASE}/${patientId}/transactions`, {
            method: "POST",
            headers: headers(),
            body: JSON.stringify(payload),
        });
        return handle(res);
    },

    // Supprimer un versement
    async deleteTransaction(transactionId) {
        const res = await fetch(`${BASE}/transactions/${transactionId}`, {
            method: "DELETE",
            headers: headers(),
        });
        return handle(res);
    },
};
