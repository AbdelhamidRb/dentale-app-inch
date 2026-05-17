// resources/js/composables/usePayments.js

import { ref } from "vue";
import { paymentsApi } from "../api/payments";

export function usePayments() {
    const patients = ref([]); // liste patients avec soldes
    const patient = ref(null); // fiche détail ouverte
    const loading = ref(false);
    const error = ref(null);
    const stats = ref({
        total_du: 0,
        total_encaisse: 0,
        total_restant: 0,
        count_partiel: 0,
        count_paye: 0,
        count_avance: 0,
    });

    // ─── Cache ────────────────────────────────────────────────────
    const _cache = new Map();
    const _cacheTime = new Map();
    const CACHE_TTL = 30_000;

    // ═══════════════════════════════════════════════════════════════
    // Charger la liste des patients avec soldes
    // ═══════════════════════════════════════════════════════════════
    async function fetchPatients(params = {}, forceRefresh = false) {
        const key = JSON.stringify(params);

        if (!forceRefresh && _cache.has(key)) {
            const age = Date.now() - (_cacheTime.get(key) ?? 0);
            if (age < CACHE_TTL) {
                const cached = _cache.get(key);
                patients.value = cached.data;
                stats.value = cached.stats;
                return;
            }
        }

        loading.value = true;
        error.value = null;
        try {
            const res = await paymentsApi.list(params);
            patients.value = res.data;
            stats.value = res.stats;
            _cache.set(key, { data: res.data, stats: res.stats });
            _cacheTime.set(key, Date.now());
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // Charger la fiche complète d'un patient
    // ═══════════════════════════════════════════════════════════════
    async function fetchPatient(patientId) {
        loading.value = true;
        error.value = null;
        try {
            const res = await paymentsApi.get(patientId);
            patient.value = res.patient;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // Ajouter un versement
    // ═══════════════════════════════════════════════════════════════
    async function addTransaction(patientId, payload) {
        const res = await paymentsApi.addTransaction(patientId, payload);
        // Met à jour dans la liste
        _updateInList(res.patient);
        // Met à jour la fiche si ouverte
        if (patient.value?.id === patientId) {
            patient.value = { ...patient.value, ...res.patient };
            if (res.transaction) {
                patient.value.transactions = [
                    res.transaction,
                    ...(patient.value.transactions ?? []),
                ];
            }
        }
        invalidateCache();
        return res;
    }

    // ═══════════════════════════════════════════════════════════════
    // Supprimer un versement
    // ═══════════════════════════════════════════════════════════════
    async function deleteTransaction(transactionId, patientId) {
        const res = await paymentsApi.deleteTransaction(transactionId);
        _updateInList(res.patient);
        if (patient.value?.id === patientId) {
            patient.value = { ...patient.value, ...res.patient };
            patient.value.transactions = (
                patient.value.transactions ?? []
            ).filter((t) => t.id !== transactionId);
        }
        invalidateCache();
        return res;
    }

    // ─── Helpers ──────────────────────────────────────────────────
    function _updateInList(updated) {
        const i = patients.value.findIndex((p) => p.id === updated.id);
        if (i !== -1) patients.value[i] = { ...patients.value[i], ...updated };
    }

    function invalidateCache() {
        _cache.clear();
        _cacheTime.clear();
    }

    return {
        patients,
        patient,
        loading,
        error,
        stats,
        fetchPatients,
        fetchPatient,
        addTransaction,
        deleteTransaction,
        invalidateCache,
    };
}
