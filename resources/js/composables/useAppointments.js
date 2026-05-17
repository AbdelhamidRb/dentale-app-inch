import { ref, computed } from "vue";
import { appointmentsApi } from "../api/appointments";

export function useAppointments() {
    // ─── État principal ────────────────────────────────────────────
    const appointments = ref([]);
    const catalogActs = ref([]);
    const stats = ref({ total: 0, termine: 0, en_cours: 0, annule: 0 });
    const loading = ref(false);
    const error = ref(null);

    // ─── Date sélectionnée (aujourd'hui par défaut) ────────────────
    const selectedDate = ref(new Date().toISOString().split("T")[0]);

    // ─── Créneaux de la timeline : 08:00 → 19:30 par pas de 30min ─
    // Calculé une seule fois, jamais recalculé (performance)
    const timeSlots = computed(() => {
        const slots = [];
        for (let h = 8; h < 20; h++) {
            slots.push(`${String(h).padStart(2, "0")}:00`);
            slots.push(`${String(h).padStart(2, "0")}:30`);
        }
        return slots;
    });

    // ─── Charge les RDV d'un jour ─────────────────────────────────
    async function fetchAppointments(date = selectedDate.value) {
        loading.value = true;
        error.value = null;
        try {
            const res = await appointmentsApi.getByDate(date);
            appointments.value = res.appointments;
            stats.value = res.stats;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    }

    // ─── Charge le catalogue des actes (une seule fois) ───────────
    async function fetchCatalogActs() {
        if (catalogActs.value.length > 0) return; // cache : déjà chargé
        const res = await appointmentsApi.getCatalogActs();
        catalogActs.value = res;
    }

    // ─── Navigation jour précédent / suivant ──────────────────────
    function previousDay() {
        const d = new Date(selectedDate.value);
        d.setDate(d.getDate() - 1);
        selectedDate.value = d.toISOString().split("T")[0];
        fetchAppointments(selectedDate.value);
    }

    function nextDay() {
        const d = new Date(selectedDate.value);
        d.setDate(d.getDate() + 1);
        selectedDate.value = d.toISOString().split("T")[0];
        fetchAppointments(selectedDate.value);
    }

    function goToToday() {
        selectedDate.value = new Date().toISOString().split("T")[0];
        fetchAppointments(selectedDate.value);
    }

    // ─── Trouver le RDV qui occupe un créneau précis ──────────────
    // Utilisé par la timeline pour placer chaque RDV sur le bon slot
    function getAppointmentForSlot(slot) {
        return appointments.value.find((a) => a.start_time === slot) || null;
    }

    // ─── Vérifie si un créneau est occupé (pour le style) ─────────
    function isSlotOccupied(slot) {
        return appointments.value.some((a) => {
            return slot >= a.start_time && slot < a.end_time;
        });
    }

    // ─── Créer un RDV ─────────────────────────────────────────────
    async function createAppointment(data) {
        const res = await appointmentsApi.create(data);
        appointments.value.push(res.appointment);
        appointments.value.sort((a, b) =>
            a.start_time.localeCompare(b.start_time),
        );
        stats.value.total++;
        return res;
    }

    // ─── Modifier un RDV ──────────────────────────────────────────
    async function updateAppointment(id, data) {
        const res = await appointmentsApi.update(id, data);
        const idx = appointments.value.findIndex((a) => a.id === id);
        if (idx !== -1) appointments.value[idx] = res.appointment;
        return res;
    }

    // ─── Changer le statut ────────────────────────────────────────
    async function changeStatus(id, status) {
        const res = await appointmentsApi.updateStatus(id, status);
        // Met à jour seulement la ligne concernée dans la liste
        const idx = appointments.value.findIndex((a) => a.id === id);
        if (idx !== -1) appointments.value[idx] = res.appointment;
        // Met à jour les stats
        await fetchAppointments(selectedDate.value);
        return res;
    }

    // ─── Annuler un RDV ───────────────────────────────────────────
    async function cancelAppointment(id) {
        await appointmentsApi.cancel(id);
        // Suppression physique → retire de la liste directement
        appointments.value = appointments.value.filter((a) => a.id !== id);
        stats.value.total--;
    }

    // ─── Date formatée lisible ────────────────────────────────────
    const formattedDate = computed(() => {
        return new Date(selectedDate.value + "T00:00:00").toLocaleDateString(
            "fr-FR",
            {
                weekday: "long",
                day: "numeric",
                month: "long",
                year: "numeric",
            },
        );
    });

    return {
        appointments,
        catalogActs,
        stats,
        loading,
        error,
        selectedDate,
        formattedDate,
        timeSlots,
        fetchAppointments,
        fetchCatalogActs,
        previousDay,
        nextDay,
        goToToday,
        getAppointmentForSlot,
        isSlotOccupied,
        createAppointment,
        updateAppointment,
        changeStatus,
        cancelAppointment,
    };
}
