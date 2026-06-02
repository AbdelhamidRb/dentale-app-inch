import { ref, reactive, computed, watch } from "vue";
import { patientsApi } from "../api/patients";
import { invalidateDashboard } from "../stores/dashboard";

export function usePatients() {
    // ─── État principal ─────────────────────────────────────────────
    const patients     = ref([]);
    const selected     = ref(null);
    const loading      = ref(false);
    const panelLoading = ref(false);
    const error        = ref(null);

    // ─── Pagination ─────────────────────────────────────────────────
    const meta = reactive({ current_page: 1, last_page: 1, total: 0 });

    // ─── Filtres ────────────────────────────────────────────────────
    const filters = reactive({
        search:   "",
        status:   "",
        archived: false,
        page:     1,
    });

    // ─── Debounce recherche ─────────────────────────────────────────
    let debounceTimer = null;
    watch(
        () => filters.search,
        () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                filters.page = 1;
                fetchPatients();
            }, 350);
        },
    );

    // Réinitialise la page sur changement de filtre status/archived
    watch(
        [() => filters.status, () => filters.archived],
        () => {
            filters.page = 1;
            fetchPatients();
        },
    );

    // Changement de page seul (pas de reset)
    watch(() => filters.page, () => fetchPatients());

    // ─── Chargement de la liste ─────────────────────────────────────
    async function fetchPatients() {
        loading.value = true;
        error.value   = null;
        try {
            const res = await patientsApi.list(filters);
            patients.value = res.data;
            Object.assign(meta, res.meta);
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    }

    // ─── Ouvrir la fiche complète d'un patient ──────────────────────
    async function openPatient(id) {
        panelLoading.value = true;
        selected.value     = null;
        try {
            const res      = await patientsApi.get(id);
            selected.value = res;
        } catch (e) {
            error.value = e.message;
        } finally {
            panelLoading.value = false;
        }
    }

    // ─── Fermer le panneau ─────────────────────────────────────────
    function closePanel() {
        selected.value = null;
    }

    // ─── Créer un patient ──────────────────────────────────────────
    async function createPatient(data) {
        const res = await patientsApi.create(data);
        patients.value.unshift(res.patient);
        meta.total++;
        invalidateDashboard();
        return res;
    }

    // ─── Modifier un patient ───────────────────────────────────────
    async function updatePatient(id, data) {
        const res = await patientsApi.update(id, data);
        const idx = patients.value.findIndex((p) => p.id === id);
        if (idx !== -1) patients.value[idx] = res.patient;
        if (selected.value?.patient?.id === id) {
            selected.value.patient = res.patient;
        }
        return res;
    }

    // ─── Archiver un patient ───────────────────────────────────────
    async function archivePatient(id) {
        await patientsApi.archive(id);
        patients.value = patients.value.filter((p) => p.id !== id);
        meta.total--;
        if (selected.value?.patient?.id === id) closePanel();
    }

    // ─── Réactiver un patient archivé ─────────────────────────────
    async function restorePatient(id) {
        await patientsApi.restore(id);
        patients.value = patients.value.filter((p) => p.id !== id);
        meta.total--;
        if (selected.value?.patient?.id === id) closePanel();
    }

    // ─── Ajouter une alerte médicale ──────────────────────────────
    async function addAlert(patientId, data) {
        const res = await patientsApi.addAlert(patientId, data);
        if (selected.value?.medical_alerts) {
            selected.value.medical_alerts.unshift(res.alert);
            _updatePatientFlagsLocally(patientId);
        }
        return res;
    }

    // ─── Supprimer une alerte médicale ────────────────────────────
    async function deleteAlert(patientId, alertId) {
        await patientsApi.deleteAlert(patientId, alertId);
        if (selected.value?.medical_alerts) {
            selected.value.medical_alerts = selected.value.medical_alerts.filter(
                (a) => a.id !== alertId,
            );
            _updatePatientFlagsLocally(patientId);
        }
    }

    // ─── Uploader un document ──────────────────────────────────────
    async function uploadDocument(patientId, file, type) {
        const res = await patientsApi.uploadDocument(patientId, file, type);
        if (selected.value?.documents) {
            selected.value.documents.unshift(res.document);
        }
        return res;
    }

    // ─── Supprimer un document ─────────────────────────────────────
    async function deleteDocument(patientId, docId) {
        await patientsApi.deleteDocument(patientId, docId);
        if (selected.value?.documents) {
            selected.value.documents = selected.value.documents.filter(
                (d) => d.id !== docId,
            );
        }
    }

    // ─── Met à jour les flags d'alerte localement sans appel API ──
    // Utilise les alertes déjà chargées dans selected.value.medical_alerts
    function _updatePatientFlagsLocally(patientId) {
        const idx = patients.value.findIndex((p) => p.id === patientId);
        if (idx === -1) return;
        const alerts = selected.value?.medical_alerts ?? [];
        patients.value[idx] = {
            ...patients.value[idx],
            has_critical_alerts: alerts.some((a) => a.severity === 'ROUGE'),
            alerts_count: alerts.length,
        };
    }

    // ─── Stats rapides côté client ─────────────────────────────────
    const stats = computed(() => ({
        total:      meta.total,
        withAlerts: patients.value.filter((p) => p.has_critical_alerts).length,
    }));

    return {
        patients,
        selected,
        loading,
        panelLoading,
        error,
        meta,
        filters,
        stats,
        fetchPatients,
        openPatient,
        closePanel,
        createPatient,
        updatePatient,
        archivePatient,
        restorePatient,
        addAlert,
        deleteAlert,
        uploadDocument,
        deleteDocument,
    };
}
