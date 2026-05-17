// Composable — toute la logique patients en dehors du composant
// Le composant Vue ne fait qu'afficher, ce fichier pense

import { ref, reactive, computed, watch } from "vue";
import { patientsApi } from "../api/patients";

export function usePatients() {
    // ─── État principal ─────────────────────────────────────────────
    const patients = ref([]); // liste courante
    const selected = ref(null); // patient ouvert dans le panneau
    const loading = ref(false); // skeleton pendant le chargement
    const error = ref(null); // message d'erreur global

    // ─── Pagination ─────────────────────────────────────────────────
    const meta = reactive({ current_page: 1, last_page: 1, total: 0 });

    // ─── Filtres ────────────────────────────────────────────────────
    const filters = reactive({
        search: "",
        status: "",
        archived: false,
        page: 1,
    });

    // ─── Debounce : attend 350ms après la frappe avant d'appeler l'API
    // Évite un appel API à chaque lettre tapée
    let debounceTimer = null;
    watch(
        () => filters.search,
        () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                filters.page = 1; // retour page 1 à chaque nouvelle recherche
                fetchPatients();
            }, 350);
        },
    );

    // Recharge quand le statut ou la page change (immédiatement)
    watch(
        [() => filters.status, () => filters.archived, () => filters.page],
        () => {
            fetchPatients();
        },
    );

    // ─── Chargement de la liste ─────────────────────────────────────
    async function fetchPatients() {
        loading.value = true;
        error.value = null;
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
        try {
            const res = await patientsApi.get(id);
            selected.value = res;
        } catch (e) {
            error.value = e.message;
        }
    }

    // ─── Fermer le panneau ─────────────────────────────────────────
    function closePanel() {
        selected.value = null;
    }

    // ─── Créer un patient ──────────────────────────────────────────
    async function createPatient(data) {
        const res = await patientsApi.create(data);
        // Insère en tête de liste sans recharger toute la page
        patients.value.unshift(res.patient);
        meta.total++;
        return res; // retourne le doublon éventuel
    }

    // ─── Modifier un patient ───────────────────────────────────────
    async function updatePatient(id, data) {
        const res = await patientsApi.update(id, data);
        // Met à jour uniquement la ligne concernée dans la liste (pas de rechargement)
        const idx = patients.value.findIndex((p) => p.id === id);
        if (idx !== -1) patients.value[idx] = res.patient;
        // Met à jour aussi le panneau si ce patient est ouvert
        if (selected.value?.patient?.id === id) {
            selected.value.patient = res.patient;
        }
        return res;
    }

    // ─── Archiver un patient ───────────────────────────────────────
    async function archivePatient(id) {
        await patientsApi.archive(id);
        // Retire de la liste sans recharger
        patients.value = patients.value.filter((p) => p.id !== id);
        meta.total--;
        if (selected.value?.patient?.id === id) closePanel();
    }

    // ─── Ajouter une alerte médicale ──────────────────────────────
    async function addAlert(patientId, data) {
        const res = await patientsApi.addAlert(patientId, data);
        if (selected.value?.medical_alerts) {
            selected.value.medical_alerts.unshift(res.alert);
        }
        // Met à jour le flag has_critical_alerts dans la liste
        _refreshPatientFlags(patientId);
        return res;
    }

    // ─── Supprimer une alerte médicale ────────────────────────────
    async function deleteAlert(patientId, alertId) {
        await patientsApi.deleteAlert(patientId, alertId);
        if (selected.value?.medical_alerts) {
            selected.value.medical_alerts =
                selected.value.medical_alerts.filter((a) => a.id !== alertId);
        }
        _refreshPatientFlags(patientId);
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

    // ─── Helper : rafraîchit les flags d'un patient dans la liste ──
    // Appelé après ajout/suppression d'alerte pour mettre à jour l'icône
    async function _refreshPatientFlags(patientId) {
        const idx = patients.value.findIndex((p) => p.id === patientId);
        if (idx === -1) return;
        const res = await patientsApi.get(patientId);
        patients.value[idx] = { ...patients.value[idx], ...res.patient };
    }

    // ─── Stats rapides calculées côté client ───────────────────────
    const stats = computed(() => ({
        total: meta.total,
        withAlerts: patients.value.filter((p) => p.has_critical_alerts).length,
    }));

    async function restorePatient(id) {
        await patientsApi.restore(id);
        patients.value = patients.value.filter((p) => p.id !== id);
        meta.total--;
        if (selected.value?.patient?.id === id) closePanel();
    }
    async function createAppointment(data) {
        // Si l'API retourne une erreur (ex: créneau occupé),
        // elle sera propagée jusqu'à handleSaved → modal reste ouverte
        const res = await appointmentsApi.create({
            ...data,
            scheduled_date: selectedDate.value,
        });
        appointments.value.push(res.appointment);
        appointments.value.sort((a, b) =>
            a.start_time.localeCompare(b.start_time),
        );
        stats.value.total++;
        return res;
    }

    return {
        // État
        patients,
        selected,
        loading,
        error,
        meta,
        filters,
        stats,
        // Actions
        fetchPatients,
        openPatient,
        closePanel,
        createPatient,
        updatePatient,
        archivePatient,
        addAlert,
        deleteAlert,
        uploadDocument,
        deleteDocument,
        restorePatient,
        createAppointment,
    };
}
