<template>
    <div class="flex flex-1 min-h-0 gap-5">
        <!-- ══════════════════════════════════════════════════════════
         COLONNE GAUCHE — Agenda
         ══════════════════════════════════════════════════════════ -->
        <div class="flex flex-col flex-1 min-w-0">
            <!-- ── En-tête ───────────────────────────────────────────── -->
            <div class="px-4 pt-4 pb-3 border-b border-slate-100 shrink-0 space-y-3">

                <!-- Ligne 1 : navigation date -->
                <div class="flex items-center justify-between">
                    <!-- Flèche gauche -->
                    <button
                        @click="viewMode === 'day' ? previousDay() : previousWeek()"
                        class="p-2 border border-slate-200 hover:bg-slate-50 rounded-lg text-slate-500 transition-colors"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>

                    <!-- Date / période centrée -->
                    <div class="text-center flex-1 mx-2">
                        <h1 class="text-base font-semibold text-slate-800 capitalize leading-tight">
                            {{ viewMode === 'day' ? formattedDate : formattedWeekRange }}
                        </h1>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <template v-if="viewMode === 'day'">
                                {{ stats.total }} rendez-vous
                                <span v-if="stats.termine > 0"> · {{ stats.termine }} terminés</span>
                                <span v-if="stats.en_cours > 0"> · {{ stats.en_cours }} en cours</span>
                            </template>
                            <template v-else>Vue semaine</template>
                        </p>
                    </div>

                    <!-- Flèche droite -->
                    <button
                        @click="viewMode === 'day' ? nextDay() : nextWeek()"
                        class="p-2 border border-slate-200 hover:bg-slate-50 rounded-lg text-slate-500 transition-colors"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>

                <!-- Ligne 2 : actions -->
                <div class="flex items-center gap-2">
                    <!-- Toggle Jour / Semaine -->
                    <div class="flex border border-slate-300 rounded-lg overflow-hidden text-xs">
                        <button
                            @click="viewMode = 'day'"
                            :class="['px-3 py-1.5 transition-colors', viewMode === 'day' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-50']"
                        >Jour</button>
                        <button
                            @click="viewMode = 'week'"
                            :class="['px-3 py-1.5 transition-colors border-l border-slate-300', viewMode === 'week' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-50']"
                        >Semaine</button>
                    </div>

                    <!-- Sélecteur date (vue jour uniquement) -->
                    <input
                        v-if="viewMode === 'day'"
                        type="date"
                        v-model="selectedDate"
                        @change="onDateInputChange"
                        class="flex-1 px-2 py-1.5 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />

                    <!-- Aujourd'hui -->
                    <button
                        @click="viewMode === 'day' ? goToToday() : goToTodayWeek()"
                        class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs text-slate-600 hover:bg-slate-50 transition-colors whitespace-nowrap"
                    >
                        Aujourd'hui
                    </button>

                    <!-- Nouveau RDV -->
                    <button
                        @click="openModal(null)"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">Nouveau RDV</span>
                    </button>
                </div>

                <!-- Légende statuts -->
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="text-[11px] text-slate-400 font-medium">Statuts :</span>
                    <div v-for="s in statusLegend" :key="s.label" class="flex items-center gap-1.5">
                        <div :class="`w-2 h-2 rounded-full ${s.dot}`"></div>
                        <span class="text-[11px] text-slate-500">{{ s.label }}</span>
                    </div>
                </div>

            </div><!-- fin header -->

            <!-- ── Erreur ─────────────────────────────────────────────── -->
            <div
                v-if="error"
                class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm"
            >
                {{ error }}
            </div>

            <!-- ══════════════════════════════════════════════════════════
           <!-- TIMELINE PRINCIPALE -->
            <div
                ref="timelineEl"
                class="bg-white rounded-xl border border-slate-200 flex flex-col overflow-hidden"
                :style="viewMode === 'week'
                    ? 'height: calc(100vh - 190px)'
                    : 'flex: 1; overflow-y: auto; min-height: 0'"
            >
                <!-- Skeleton — uniquement pour la vue jour en cours de chargement -->
                <template v-if="loading && viewMode === 'day'">
                    <div
                        v-for="i in 6"
                        :key="i"
                        class="flex gap-4 p-4 border-b border-slate-50 animate-pulse"
                    >
                        <div class="w-12 h-3 bg-slate-100 rounded shrink-0 mt-1"></div>
                        <div class="flex-1 h-16 bg-slate-100 rounded-xl"></div>
                    </div>
                </template>

                <!-- ══════════════════════════════════════════════════════════
                     VUE SEMAINE
                ══════════════════════════════════════════════════════════ -->
                <template v-else-if="viewMode === 'week'">
                    <!-- Indicateur de chargement discret (ne masque pas la grille) -->
                    <div
                        v-if="weekLoading"
                        class="sticky top-0 z-30 h-0.5 bg-blue-500 animate-pulse"
                    ></div>

                    <!-- En-tête colonnes jours (sticky) -->
                    <div class="flex sticky top-0 z-20 bg-white border-b border-slate-200">
                        <!-- Espace colonne heures -->
                        <div class="w-16 shrink-0"></div>
                        <!-- Colonnes jours -->
                        <div
                            v-for="day in weekDays"
                            :key="day"
                            class="flex-1 min-w-0 px-1 py-2 text-center border-l border-slate-100"
                        >
                            <span
                                :class="[
                                    'text-xs font-semibold capitalize block',
                                    isToday(day) ? 'text-blue-600' : 'text-slate-500',
                                ]"
                            >{{ formatDayHeader(day) }}</span>
                            <span
                                v-if="weekAppointments[day]?.length"
                                class="text-[10px] text-slate-400"
                            >{{ weekAppointments[day].length }} RDV</span>
                        </div>
                    </div>

                    <!-- Corps : grille heures × jours -->
                    <div class="flex flex-1 min-h-0" ref="weekGridRef">
                        <!-- Colonne heures -->
                        <div class="w-16 shrink-0 relative bg-white z-10" :style="`height: ${slotH * SLOTS}px`">
                            <div
                                v-for="slot in workHours"
                                :key="slot"
                                class="absolute right-0 pr-3 text-right"
                                :style="{ top: slotTopPx(slot) }"
                            >
                                <span
                                    class="text-[11px] font-mono leading-none block"
                                    :class="slot.endsWith(':00') ? 'text-slate-400' : 'text-slate-200'"
                                >{{ slot }}</span>
                            </div>
                        </div>

                        <!-- 7 colonnes jours -->
                        <div
                            v-for="day in weekDays"
                            :key="day"
                            class="flex-1 min-w-0 border-l border-slate-100 relative"
                            :class="isToday(day) ? 'bg-blue-50/30' : ''"
                            :style="`height: ${slotH * SLOTS}px`"
                        >
                            <!-- Lignes de fond -->
                            <div
                                v-for="slot in workHours"
                                :key="slot"
                                class="absolute left-0 right-0 border-t"
                                :class="slot.endsWith(':00') ? 'border-slate-100' : 'border-slate-50'"
                                :style="{ top: slotTopPx(slot) }"
                            ></div>

                            <!-- Cartes RDV -->
                            <div
                                v-for="appt in dayLayout(day)"
                                :key="appt.id"
                                @click="openPanel(appt)"
                                :class="[
                                    'absolute rounded-lg px-2 py-1 cursor-pointer z-10',
                                    'border transition-all duration-150 hover:shadow-md hover:z-20',
                                    colorConfig(appt.color).card,
                                ]"
                                :style="`
                                    top: ${apptTopPx(appt)};
                                    height: ${apptHeightPx(appt)};
                                    left: calc(${(appt.col / appt.maxCols) * 100}% + 2px);
                                    width: calc(${(1 / appt.maxCols) * 100}% - 4px);
                                `"
                            >
                                <div class="overflow-hidden h-full flex items-center">
                                    <span :class="['text-[11px] font-semibold truncate leading-tight w-full', colorConfig(appt.color).title]">{{ appt.patient.full_name }}</span>
                                </div>
                            </div>

                            <!-- Zone cliquable pour créer un RDV ce jour -->
                            <div class="absolute inset-0" style="z-index: 0" @click.self="openModal(null, null, day)"></div>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <!-- ── État vide : aucun RDV ce jour ─────────────────── -->
                    <div
                        v-if="appointments.length === 0"
                        class="flex flex-col items-center justify-center py-20 text-slate-400"
                    >
                        <CalendarX class="w-12 h-12 mb-3 text-slate-200" />
                        <p class="text-sm font-medium">
                            Aucun rendez-vous ce jour
                        </p>
                        <p class="text-xs mt-1 mb-4">
                            Cliquez sur le bouton pour créer le premier
                        </p>
                        <button
                            @click="openModal(null)"
                            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <Plus class="w-4 h-4" />
                            Créer un rendez-vous
                        </button>
                    </div>

                    <!-- ── Timeline vue jour (scroll, hauteurs fixes) ────── -->
                    <div v-else class="flex" style="height: 1080px">
                        <!-- Colonne heures -->
                        <div class="w-16 shrink-0 relative">
                            <div
                                v-for="slot in workHours"
                                :key="slot"
                                class="absolute right-0 pr-3 text-right"
                                :style="{ top: (timeToMinutes(slot) * 2) + 'px' }"
                            >
                                <span class="text-xs font-mono leading-none" :class="slot.endsWith(':00') ? 'text-slate-400' : 'text-slate-200'">{{ slot }}</span>
                            </div>
                        </div>

                        <!-- Zone des RDV -->
                        <div class="flex-1 border-l border-slate-100 relative">
                            <!-- Lignes de fond par slot -->
                            <div
                                v-for="slot in workHours"
                                :key="slot"
                                class="absolute left-0 right-0 border-t"
                                :class="slot.endsWith(':00') ? 'border-slate-100' : 'border-slate-50'"
                                :style="{ top: (timeToMinutes(slot) * 2) + 'px' }"
                            ></div>

                            <!-- Cartes RDV -->
                            <div
                                v-for="appt in appointmentsWithLayout"
                                :key="appt.id"
                                @click="openPanel(appt)"
                                :class="[
                                    'absolute rounded-xl px-4 py-2.5 cursor-pointer z-10',
                                    'border transition-all duration-150 hover:shadow-lg hover:z-20',
                                    colorConfig(appt.color).card,
                                ]"
                                :style="`
                                    top: ${timeToMinutes(appt.start_time) * 2}px;
                                    height: ${Math.max(apptDuration(appt) * 2, 52)}px;
                                    left: calc(${(appt.col / appt.maxCols) * 100}% + 8px);
                                    width: calc(${(1 / appt.maxCols) * 100}% - 16px);
                                `"
                            >
                                <div
                                    class="flex items-start justify-between gap-2 h-full overflow-hidden"
                                >
                                    <div class="min-w-0 flex-1 overflow-hidden flex flex-col justify-center gap-0.5">
                                        <!-- Ligne 1 : Nom + badge -->
                                        <div class="flex items-center gap-2">
                                            <span :class="['text-xs font-semibold truncate', colorConfig(appt.color).title]">
                                                {{ appt.patient.full_name }}
                                            </span>
                                            <span :class="['text-[9px] font-medium px-1.5 py-0.5 rounded-full shrink-0', colorConfig(appt.color).badge]">
                                                {{ statusLabel(appt.status) }}
                                            </span>
                                        </div>

                                        <!-- Ligne 2 : Heure -->
                                        <div class="flex items-center gap-1">
                                            <Clock class="w-2.5 h-2.5 text-slate-400 shrink-0" />
                                            <span class="text-[10px] text-slate-500 font-mono">
                                                {{ appt.start_time }} → {{ appt.end_time }}
                                            </span>
                                        </div>

                                        <!-- Ligne 3 : Actes (si RDV >= 45 min) -->
                                        <div v-if="apptDuration(appt) >= 45" class="flex items-center gap-1">
                                            <Stethoscope class="w-2.5 h-2.5 text-slate-400 shrink-0" />
                                            <span class="text-[10px] text-slate-500 truncate">
                                                {{ appt.acts.length ? appt.acts.map(a => a.name).join(' · ') : 'Aucun acte' }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Barre colorée droite -->
                                    <div
                                        :class="[
                                            'w-1 rounded-full shrink-0 self-stretch',
                                            colorConfig(appt.color).bar,
                                        ]"
                                    ></div>
                                </div>
                            </div>

                            <!-- Zone cliquable fond — z-0 reste en dessous des cartes -->
                            <div
                                class="absolute inset-0"
                                style="z-index: 0"
                                @click.self="openModal(null)"
                            ></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
         PANNEAU DÉTAIL RDV
         ══════════════════════════════════════════════════════════ -->
        <Transition name="slide">
            <div
                v-if="selectedAppointment"
                class="w-80 shrink-0 bg-white rounded-xl border border-slate-200 overflow-y-auto"
            >
                <AppointmentPanel
                    :appointment="selectedAppointment"
                    :is-dentist="isDentist"
                    @close="selectedAppointment = null"
                    @status-changed="handleStatusChange"
                    @edit="openModal(null, selectedAppointment)"
                    @cancelled="handleCancel"
                />
            </div>
        </Transition>

        <!-- ══════════════════════════════════════════════════════════
         MODAL CRÉATION / MODIFICATION
         ══════════════════════════════════════════════════════════ -->
        <AppointmentModal
            v-if="showModal"
            :slot-time="modalSlot"
            :appointment="editingAppointment"
            :catalog-acts="catalogActs"
            :selected-date="selectedDate"
            :external-error="modalError"
            @close="
                showModal = false;
                modalError = null;
            "
            @saved="handleSaved"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from "vue";
import { authStore } from '../stores/auth'
const isDentist = computed(() => authStore.isDentist())
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Clock,
    Stethoscope,
    CalendarX,
} from "lucide-vue-next";
import { useAppointments } from "../composables/useAppointments";
import AppointmentPanel from "../components/appointments/AppointmentPanel.vue";
import AppointmentModal from "../components/appointments/AppointmentModal.vue";
import { showToast } from "../stores/toast.js";

const {
    appointments,
    catalogActs,
    stats,
    loading,
    weekLoading,
    error,
    selectedDate,
    formattedDate,
    fetchAppointments,
    fetchCatalogActs,
    previousDay,
    nextDay,
    goToToday,
    createAppointment,
    updateAppointment,
    changeStatus,
    cancelAppointment,
    weekAppointments,
    weekStartDate,
    weekDays,
    fetchWeek,
    previousWeek,
    nextWeek,
    goToTodayWeek,
} = useAppointments();

// ─── Mode vue : jour ou semaine ───────────────────────────────────
const viewMode = ref("day");

// ─── État local ───────────────────────────────────────────────────
const timelineEl = ref(null);
const showModal = ref(false);
const modalSlot = ref(null);
const editingAppointment = ref(null);
const selectedAppointment = ref(null);

onMounted(async () => {
    if (window.innerWidth < 640) viewMode.value = 'day';
    await Promise.all([fetchAppointments(), fetchWeek()]);
    fetchCatalogActs();
});

// Rafraîchit les données au changement de vue
// watch viewMode géré dans le bloc ResizeObserver ci-dessous
//
// ─── Constantes timeline ──────────────────────────────────────────
const START_HOUR = 9;
const END_HOUR   = 18;
const SLOTS      = 18; // nombre de créneaux 30min entre 09:00 et 18:00

// Slots affichés : 09:00 → 18:00 inclus (19 marqueurs)
const workHours = [
    '09:00','09:30','10:00','10:30','11:00','11:30',
    '12:00','12:30','13:00','13:30','14:00','14:30',
    '15:00','15:30','16:00','16:30','17:00','17:30','18:00'
];

// ─── Mesure du grid body (ResizeObserver) ─────────────────────────
const weekGridRef    = ref(null);
const weekGridHeight = ref(0);
let   weekGridObs    = null;

function observeWeekGrid() {
    if (weekGridObs) weekGridObs.disconnect();
    if (!weekGridRef.value) return;
    weekGridHeight.value = weekGridRef.value.clientHeight;
    weekGridObs = new ResizeObserver(() => {
        weekGridHeight.value = weekGridRef.value.clientHeight;
    });
    weekGridObs.observe(weekGridRef.value);
}

watch(viewMode, (m) => {
    if (m === 'week') { nextTick(observeWeekGrid); fetchWeek(); }
    else { if (weekGridObs) weekGridObs.disconnect(); fetchAppointments(); }
});

onMounted(() => nextTick(observeWeekGrid));
onUnmounted(() => { if (weekGridObs) weekGridObs.disconnect(); });

// SLOTS + 0.5 → laisse un buffer en bas pour que le label 18:00 ne soit pas coupé
const slotH = computed(() => weekGridHeight.value > 0 ? weekGridHeight.value / (SLOTS + 0.5) : 40);

// ─── Fonctions de positionnement (px) ────────────────────────────
function timeToMinutes(time) {
    const [h, m] = time.split(':').map(Number);
    return (h - START_HOUR) * 60 + m;
}

// Position px d'un marqueur horaire
function slotTopPx(slot) {
    return (timeToMinutes(slot) / 30 * slotH.value) + 'px';
}

// Position px du début d'un RDV
function apptTopPx(appt) {
    return (timeToMinutes(appt.start_time) / 30 * slotH.value) + 'px';
}

// Hauteur px d'un RDV
function apptHeightPx(appt) {
    const [sh, sm] = appt.start_time.split(':').map(Number);
    const [eh, em] = appt.end_time.split(':').map(Number);
    const dur = Math.max(30, eh * 60 + em - (sh * 60 + sm));
    return Math.max(dur / 30 * slotH.value, 22) + 'px';
}

function apptDuration(appt) {
    const [sh, sm] = appt.start_time.split(':').map(Number);
    const [eh, em] = appt.end_time.split(':').map(Number);
    return eh * 60 + em - (sh * 60 + sm);
}

// ─── Calcul des colonnes pour les RDV qui se chevauchent ─────────
const appointmentsWithLayout = computed(() => computeLayout(appointments.value));

// ─── Ouvrir modal ─────────────────────────────────────────────────
function openModal(slot, appointment = null, date = null) {
    modalSlot.value = slot;
    editingAppointment.value = appointment;
    // En vue semaine, pré-sélectionne la date de la colonne cliquée
    if (date) selectedDate.value = date;
    showModal.value = true;
}

// ─── Ouvrir panneau ───────────────────────────────────────────────
function openPanel(appt) {
    selectedAppointment.value = appt;
}

// ─── Sauvegarder ─────────────────────────────────────────────────
// Erreur à passer à la modal
const modalError = ref(null);

async function handleSaved(data, isEdit) {
    modalError.value = null;
    try {
        let saved;
        if (isEdit) saved = await updateAppointment(data.id, data);
        else saved = await createAppointment(data);
        showModal.value = false;
        showToast(isEdit ? 'Rendez-vous modifié' : 'Rendez-vous créé');
        const appt = saved.appointment;
        syncWeekApptAfterSave(appt, isEdit);
        // Rafraîchit le panneau ouvert si c'était ce RDV
        if (isEdit && selectedAppointment.value?.id === appt.id) {
            selectedAppointment.value = { ...appt };
        }
    } catch (e) {
        modalError.value = e.message;
    }
}

// Met à jour weekAppointments directement après création ou édition
function syncWeekApptAfterSave(appt, isEdit) {
    const date = appt.scheduled_date;
    if (isEdit) {
        for (const d of Object.keys(weekAppointments.value)) {
            const idx = weekAppointments.value[d].findIndex((a) => a.id === appt.id);
            if (idx !== -1) { weekAppointments.value[d].splice(idx, 1); break; }
        }
    }
    if (weekDays.value.includes(date)) {
        // Date dans la semaine affichée → insertion directe
        if (!weekAppointments.value[date]) weekAppointments.value[date] = [];
        weekAppointments.value[date].push(appt);
        weekAppointments.value[date].sort((a, b) => a.start_time.localeCompare(b.start_time));
    } else {
        // Date hors semaine → navigate vers la semaine de la nouvelle date
        const newMonday = getWeekStartLocal(date);
        weekStartDate.value = newMonday;
        fetchWeek(newMonday);
    }
}

function getWeekStartLocal(dateOrStr) {
    const d = new Date(typeof dateOrStr === "string" ? dateOrStr : dateOrStr.toISOString().split("T")[0]);
    const day = d.getUTCDay();
    const diff = day === 0 ? -6 : 1 - day;
    d.setUTCDate(d.getUTCDate() + diff);
    return d.toISOString().split("T")[0];
}

// ─── Changer statut ───────────────────────────────────────────────
async function handleStatusChange(id, status) {
    const scrollTop = timelineEl.value?.scrollTop ?? 0;
    try {
        const res = await changeStatus(id, status);
        const appt = res.appointment;
        for (const d of Object.keys(weekAppointments.value)) {
            const idx = weekAppointments.value[d].findIndex((a) => a.id === id);
            if (idx !== -1) { weekAppointments.value[d][idx] = appt; break; }
        }
        if (selectedAppointment.value?.id === id) selectedAppointment.value = { ...appt };
        await nextTick();
        if (timelineEl.value) timelineEl.value.scrollTop = scrollTop;
    } catch (e) {
        error.value = e.message ?? "Impossible de changer le statut.";
    }
}

async function handleCancel(id) {
    try {
        await cancelAppointment(id);
        selectedAppointment.value = null;
        for (const d of Object.keys(weekAppointments.value)) {
            const idx = weekAppointments.value[d].findIndex((a) => a.id === id);
            if (idx !== -1) { weekAppointments.value[d].splice(idx, 1); break; }
        }
    } catch (e) {
        error.value = e.message ?? "Impossible d'annuler le rendez-vous.";
    }
}

// ─── Calcul layout pour une liste de RDV (réutilisé par vue semaine) ─
function computeLayout(apptList) {
    const appts = apptList
        .map((a) => {
            const [sh, sm] = a.start_time.slice(0, 5).split(":").map(Number);
            const [eh, em] = a.end_time.slice(0, 5).split(":").map(Number);
            return { ...a, startMin: sh * 60 + sm, endMin: eh * 60 + em };
        })
        .sort((a, b) => a.startMin - b.startMin);

    const withOverlap = appts.map((appt) => ({
        ...appt,
        overlapping: appts.filter(
            (o) => o.id !== appt.id && o.startMin < appt.endMin && o.endMin > appt.startMin,
        ),
    }));

    const assigned = {};
    for (const appt of withOverlap) {
        const takenCols = appt.overlapping.filter((o) => assigned[o.id] !== undefined).map((o) => assigned[o.id]);
        let col = 0;
        while (takenCols.includes(col)) col++;
        assigned[appt.id] = col;
    }

    return withOverlap.map((appt) => {
        const groupCols = new Set([assigned[appt.id]]);
        appt.overlapping.forEach((o) => { if (assigned[o.id] !== undefined) groupCols.add(assigned[o.id]); });
        return { ...appt, col: assigned[appt.id], maxCols: Math.max(...groupCols) + 1 };
    });
}

// RDV avec layout pour un jour donné de la semaine
function dayLayout(dateStr) {
    return computeLayout(weekAppointments.value[dateStr] || []);
}

// Formate l'en-tête de colonne : "lun. 19"
function formatDayHeader(dateStr) {
    const d = new Date(dateStr + "T00:00:00");
    return d.toLocaleDateString("fr-FR", { weekday: "short", day: "numeric" });
}

// Vérifie si une date est aujourd'hui
function isToday(dateStr) {
    return dateStr === new Date().toISOString().split("T")[0];
}

function onDateInputChange() {
    const d = new Date(selectedDate.value); // UTC minuit
    if (d.getUTCDay() === 0) {
        d.setUTCDate(d.getUTCDate() + 1);
        selectedDate.value = d.toISOString().split("T")[0];
    }
    fetchAppointments(selectedDate.value);
}

// Plage de la semaine affichée : "19 – 25 mai 2026"
const formattedWeekRange = computed(() => {
    if (!weekDays.value.length) return "";
    const first = new Date(weekDays.value[0] + "T00:00:00");
    const last  = new Date(weekDays.value[weekDays.value.length - 1] + "T00:00:00");
    const opts  = { day: "numeric", month: "long" };
    const optsY = { day: "numeric", month: "long", year: "numeric" };
    return `${first.toLocaleDateString("fr-FR", opts)} – ${last.toLocaleDateString("fr-FR", optsY)}`;
});

// ─── Config couleurs par statut ───────────────────────────────────
function colorConfig(color) {
    const configs = {
        gray: {
            card: "bg-slate-50 border-slate-200 hover:border-slate-300",
            title: "text-slate-800",
            badge: "bg-slate-200 text-slate-600",
            bar: "bg-slate-300",
        },
        blue: {
            card: "bg-blue-50 border-blue-200 hover:border-blue-300",
            title: "text-blue-900",
            badge: "bg-blue-200 text-blue-700",
            bar: "bg-blue-400",
        },
        green: {
            card: "bg-green-50 border-green-200 hover:border-green-300",
            title: "text-green-900",
            badge: "bg-green-200 text-green-700",
            bar: "bg-green-400",
        },
        orange: {
            card: "bg-orange-50 border-orange-200 hover:border-orange-300",
            title: "text-orange-900",
            badge: "bg-orange-200 text-orange-700",
            bar: "bg-orange-400",
        },
    };
    return configs[color] || configs.gray;
}

function statusLabel(status) {
    return (
        {
            PLANIFIE: "Planifié",
            CONFIRME: "Confirmé",
            TERMINE: "Terminé",
            NO_SHOW: "Absent",
        }[status] || status
    );
}

const statusLegend = [
    { label: "Planifié", dot: "bg-slate-400" },
    { label: "Confirmé", dot: "bg-blue-400" },
    { label: "Terminé", dot: "bg-green-400" },
    { label: "Absent", dot: "bg-orange-400" },
];
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: all 0.2s ease;
}
.slide-enter-from,
.slide-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
</style>
