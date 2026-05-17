import { ref, computed } from "vue";
import { consultationsApi } from "../api/consultations";
// ─── État réactif ──────────────────────────────────────────────
// ─── Filtres actifs ────────────────────────────────────────────
const consultations = ref([]);
const consultation = ref(null);
const loading = ref(false);
const error = ref(null);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const filters = ref({ status: "", patient_id: null, page: 1 });
export function useConsultations() {
    // ─── Nombre de consultations EN_COURS (pour le badge profil) ──
    const enCoursCount = computed(
        () => consultations.value.filter((c) => c.status === "EN_COURS").length,
    );

    // ═══════════════════════════════════════════════════════════════
    // Charger la liste
    // ═══════════════════════════════════════════════════════════════
    // Cache global — persiste pendant la session
    const _cache = new Map();
    const _cacheTime = new Map();
    const CACHE_TTL = 30_000; // 30 secondes

    async function fetchConsultations(params = {}, forceRefresh = false) {
        const cacheKey = JSON.stringify({ ...filters.value, ...params });

        // ── Retourne le cache si encore frais ─────────────────────────
        if (!forceRefresh && _cache.has(cacheKey)) {
            const age = Date.now() - (_cacheTime.get(cacheKey) ?? 0);
            if (age < CACHE_TTL) {
                const cached = _cache.get(cacheKey);
                consultations.value = cached.data;
                meta.value = cached.meta;
                return;
            }
        }

        loading.value = true;
        error.value = null;
        try {
            const res = await consultationsApi.list({
                ...filters.value,
                ...params,
            });
            consultations.value = res.data;
            meta.value = res.meta;

            // ── Stocke en cache ───────────────────────────────────────
            _cache.set(cacheKey, { data: res.data, meta: res.meta });
            _cacheTime.set(cacheKey, Date.now());
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    }

    // Vide le cache (appeler après create/update/delete)
    function invalidateCache() {
        _cache.clear();
        _cacheTime.clear();
    }

    // ─── Charger uniquement les EN_COURS (pour le widget profil) ──
    async function fetchEnCours() {
        return fetchConsultations({ en_cours: true, page: 1 });
    }

    // ─── Charger les consultations d'un patient spécifique ────────
    async function fetchForPatient(patientId) {
        return fetchConsultations({ patient_id: patientId, page: 1 });
    }

    // ═══════════════════════════════════════════════════════════════
    // Charger la fiche détail
    // ═══════════════════════════════════════════════════════════════
    async function fetchConsultation(id) {
        loading.value = true;
        error.value = null;
        try {
            const res = await consultationsApi.get(id);
            consultation.value = res.consultation;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // Créer une consultation
    // ═══════════════════════════════════════════════════════════════
    async function createConsultation(payload) {
        const res = await consultationsApi.create(payload);
        // Insère en tête de liste sans rechargement complet
        consultations.value.unshift(res.consultation);
        return res.consultation;
    }

    // ═══════════════════════════════════════════════════════════════
    // Modifier une consultation
    // ═══════════════════════════════════════════════════════════════
    async function updateConsultation(id, payload) {
        const res = await consultationsApi.update(id, payload);
        _replaceInList(res.consultation);
        // Met à jour la fiche détail si elle est ouverte
        if (consultation.value?.id === id) {
            consultation.value = res.consultation;
        }
        return res.consultation;
    }

    // ═══════════════════════════════════════════════════════════════
    // Ajouter une séance
    // ═══════════════════════════════════════════════════════════════
    async function addSession(id, date) {
        const res = await consultationsApi.addSession(id, date);
        // Met à jour les dates dans la liste et dans la fiche
        const target = consultations.value.find((c) => c.id === id);
        if (target) target.session_dates = res.session_dates;
        if (consultation.value?.id === id) {
            consultation.value.session_dates = res.session_dates;
        }
        return res.session_dates;
    }

    // ═══════════════════════════════════════════════════════════════
    // Clôturer
    // ═══════════════════════════════════════════════════════════════
    async function closeConsultation(id) {
        const res = await consultationsApi.close(id);
        _replaceInList(res.consultation);
        if (consultation.value?.id === id) {
            consultation.value = res.consultation;
        }
        return res.consultation;
    }

    // ═══════════════════════════════════════════════════════════════
    // Supprimer
    // ═══════════════════════════════════════════════════════════════
    async function deleteConsultation(id) {
        await consultationsApi.destroy(id);
        consultations.value = consultations.value.filter((c) => c.id !== id);
        if (consultation.value?.id === id) consultation.value = null;
    }

    // ═══════════════════════════════════════════════════════════════
    // Pagination
    // ═══════════════════════════════════════════════════════════════
    async function goToPage(page) {
        filters.value.page = page;
        await fetchConsultations();
    }

    // ─── Helper : remplace une consultation dans la liste ─────────
    function _replaceInList(updated) {
        const idx = consultations.value.findIndex((c) => c.id === updated.id);
        if (idx !== -1) consultations.value[idx] = updated;
    }

    return {
        // État
        consultations,
        consultation,
        loading,
        error,
        meta,
        filters,
        enCoursCount,

        // Actions
        fetchConsultations,
        fetchEnCours,
        fetchForPatient,
        fetchConsultation,
        createConsultation,
        updateConsultation,
        addSession,
        closeConsultation,
        deleteConsultation,
        goToPage,
        invalidateCache,
    };
}
