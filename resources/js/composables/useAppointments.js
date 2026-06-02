import { ref, computed } from "vue";
import { appointmentsApi } from "../api/appointments";
import { invalidateDashboard } from "../stores/dashboard";

// Retourne le lundi de la semaine contenant `date`
function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay(); // 0=dim, 1=lun...
    const diff = day === 0 ? -6 : 1 - day;
    d.setDate(d.getDate() + diff);
    return d.toISOString().split("T")[0];
}

// Retourne le dimanche (6 jours après lundi)
function getWeekEnd(mondayStr) {
    const d = new Date(mondayStr + "T00:00:00");
    d.setDate(d.getDate() + 6);
    return d.toISOString().split("T")[0];
}

export function useAppointments() {
    // ─── État principal ────────────────────────────────────────────
    const appointments = ref([]);
    const catalogActs = ref([]);
    const stats = ref({ total: 0, termine: 0, en_cours: 0, annule: 0 });
    const loading = ref(false);
    const weekLoading = ref(false);
    const error = ref(null);

    // ─── Date sélectionnée (aujourd'hui par défaut) ────────────────
    const selectedDate = ref(new Date().toISOString().split("T")[0]);

    // ─── Semaine : RDV groupés par date { "YYYY-MM-DD": [...] } ───
    const weekAppointments = ref({});
    const weekStartDate = ref(getWeekStart(new Date()));

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
        if (catalogActs.value.length > 0) return;
        try {
            const res = await appointmentsApi.getCatalogActs();
            catalogActs.value = res;
        } catch (e) {
            error.value = e.message;
        }
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
        invalidateDashboard();
        return res;
        // Les erreurs remontent à l'appelant (AgendaView les catch)
    }

    async function updateAppointment(id, data) {
        const res = await appointmentsApi.update(id, data);
        const idx = appointments.value.findIndex((a) => a.id === id);
        if (idx !== -1) appointments.value[idx] = res.appointment;
        return res;
    }

    async function changeStatus(id, status) {
        const res = await appointmentsApi.updateStatus(id, status);
        const idx = appointments.value.findIndex((a) => a.id === id);
        if (idx !== -1) appointments.value[idx] = res.appointment;
        invalidateDashboard();
        return res;
    }

    async function cancelAppointment(id) {
        await appointmentsApi.cancel(id);
        appointments.value = appointments.value.filter((a) => a.id !== id);
        stats.value.total--;
        invalidateDashboard();
    }

    // ─── Les 7 jours de la semaine courante ───────────────────────
    const weekDays = computed(() => {
        const days = [];
        const start = new Date(weekStartDate.value + "T00:00:00");
        for (let i = 0; i < 7; i++) {
            const d = new Date(start);
            d.setDate(d.getDate() + i);
            days.push(d.toISOString().split("T")[0]);
        }
        return days;
    });

    // ─── Charge les RDV d'une semaine ────────────────────────────
    async function fetchWeek(mondayStr = weekStartDate.value) {
        weekLoading.value = true;
        try {
            const endDate = getWeekEnd(mondayStr);
            const res = await appointmentsApi.getByWeek(mondayStr, endDate);
            // La réponse peut être un objet {} ou un tableau [] si vide
            weekAppointments.value = Array.isArray(res.appointments) ? {} : res.appointments;
        } catch (e) {
            error.value = e.message;
        } finally {
            weekLoading.value = false;
        }
    }

    function previousWeek() {
        const d = new Date(weekStartDate.value + "T00:00:00");
        d.setDate(d.getDate() - 7);
        weekStartDate.value = d.toISOString().split("T")[0];
        fetchWeek(weekStartDate.value);
    }

    function nextWeek() {
        const d = new Date(weekStartDate.value + "T00:00:00");
        d.setDate(d.getDate() + 7);
        weekStartDate.value = d.toISOString().split("T")[0];
        fetchWeek(weekStartDate.value);
    }

    function goToTodayWeek() {
        weekStartDate.value = getWeekStart(new Date());
        fetchWeek(weekStartDate.value);
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
        weekLoading,
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
        // semaine
        weekAppointments,
        weekStartDate,
        weekDays,
        fetchWeek,
        previousWeek,
        nextWeek,
        goToTodayWeek,
    };
}
