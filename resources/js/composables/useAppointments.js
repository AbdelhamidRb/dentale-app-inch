import { ref, computed } from "vue";
import { appointmentsApi } from "../api/appointments";
import { invalidateDashboard } from "../stores/dashboard";

// Date locale du jour (YYYY-MM-DD) — évite le décalage UTC lors d'un new Date().toISOString()
function localToday() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

// Retourne le lundi de la semaine en UTC pur
// Accepte un objet Date (→ converti en date locale) ou une chaîne "YYYY-MM-DD"
function getWeekStart(dateOrStr) {
    const str = dateOrStr instanceof Date
        ? `${dateOrStr.getFullYear()}-${String(dateOrStr.getMonth() + 1).padStart(2, "0")}-${String(dateOrStr.getDate()).padStart(2, "0")}`
        : dateOrStr;
    const d = new Date(str); // UTC minuit
    const day = d.getUTCDay();
    const diff = day === 0 ? -6 : 1 - day;
    d.setUTCDate(d.getUTCDate() + diff);
    return d.toISOString().split("T")[0];
}

// Retourne le samedi (5 jours après lundi) — borne de fetchWeek
function getWeekEnd(mondayStr) {
    const d = new Date(mondayStr); // UTC minuit
    d.setUTCDate(d.getUTCDate() + 5);
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

    // ─── Date sélectionnée (date locale du jour, jamais dimanche) ────
    const _initD = new Date(localToday()); // UTC minuit de la date locale
    if (_initD.getUTCDay() === 0) _initD.setUTCDate(_initD.getUTCDate() + 1);
    const selectedDate = ref(_initD.toISOString().split("T")[0]);

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

    // ─── Navigation jour — tout en UTC pour éviter le décalage UTC+1 ─
    function previousDay() {
        const d = new Date(selectedDate.value); // "YYYY-MM-DD" → UTC minuit
        d.setUTCDate(d.getUTCDate() - 1);
        if (d.getUTCDay() === 0) d.setUTCDate(d.getUTCDate() - 1); // dim → sam
        selectedDate.value = d.toISOString().split("T")[0];
        fetchAppointments(selectedDate.value);
    }

    function nextDay() {
        const d = new Date(selectedDate.value);
        d.setUTCDate(d.getUTCDate() + 1);
        if (d.getUTCDay() === 0) d.setUTCDate(d.getUTCDate() + 1); // dim → lun
        selectedDate.value = d.toISOString().split("T")[0];
        fetchAppointments(selectedDate.value);
    }

    function goToToday() {
        const d = new Date(localToday());
        if (d.getUTCDay() === 0) d.setUTCDate(d.getUTCDate() + 1);
        selectedDate.value = d.toISOString().split("T")[0];
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

    // ─── Lun–Sam en UTC pur (normalisé même si weekStartDate ≠ lundi) ─
    const weekDays = computed(() => {
        const ref = new Date(weekStartDate.value); // UTC minuit
        const day = ref.getUTCDay();
        const diff = day === 0 ? -6 : 1 - day;
        const mondayMs = ref.getTime() + diff * 86400000;
        return Array.from({ length: 6 }, (_, i) =>
            new Date(mondayMs + i * 86400000).toISOString().split("T")[0]
        );
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
        const d = new Date(weekStartDate.value);
        d.setUTCDate(d.getUTCDate() - 7);
        weekStartDate.value = d.toISOString().split("T")[0];
        fetchWeek(weekStartDate.value);
    }

    function nextWeek() {
        const d = new Date(weekStartDate.value);
        d.setUTCDate(d.getUTCDate() + 7);
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
