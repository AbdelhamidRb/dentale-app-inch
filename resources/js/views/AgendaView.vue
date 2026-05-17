<template>
    <div class="flex h-full gap-5">
        <!-- ══════════════════════════════════════════════════════════
         COLONNE GAUCHE — Agenda
         ══════════════════════════════════════════════════════════ -->
        <div class="flex flex-col flex-1 min-w-0">
            <!-- ── En-tête ───────────────────────────────────────────── -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <!-- Navigation jour -->
                    <button
                        @click="previousDay"
                        class="p-2 border border-slate-200 hover:bg-white rounded-lg text-slate-500 transition-colors"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>

                    <div>
                        <h1
                            class="text-base font-semibold text-slate-800 capitalize"
                        >
                            {{ formattedDate }}
                        </h1>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ stats.total }} rendez-vous
                            <span v-if="stats.termine > 0">
                                · {{ stats.termine }} terminés</span
                            >
                            <span v-if="stats.en_cours > 0">
                                · {{ stats.en_cours }} en cours</span
                            >
                        </p>
                    </div>

                    <button
                        @click="nextDay"
                        class="p-2 border border-slate-200 hover:bg-white rounded-lg text-slate-500 transition-colors"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Sélecteur date -->
                    <input
                        type="date"
                        v-model="selectedDate"
                        @change="fetchAppointments(selectedDate)"
                        class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <button
                        @click="goToToday"
                        class="px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-white transition-colors"
                    >
                        Aujourd'hui
                    </button>
                    <button
                        @click="openModal(null)"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <Plus class="w-4 h-4" />
                        Nouveau RDV
                    </button>
                </div>
            </div>

            <!-- ── Légende statuts ───────────────────────────────────── -->
            <div class="flex items-center gap-4 mb-4 px-1">
                <span class="text-[11px] text-slate-400 font-medium"
                    >Statuts :</span
                >
                <div
                    v-for="s in statusLegend"
                    :key="s.label"
                    class="flex items-center gap-1.5"
                >
                    <div :class="`w-2 h-2 rounded-full ${s.dot}`"></div>
                    <span class="text-[11px] text-slate-500">{{
                        s.label
                    }}</span>
                </div>
            </div>

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
                class="flex-1 bg-white rounded-xl border border-slate-200 overflow-y-auto"
                style="max-height: calc(100vh - 180px)"
            >
                <!-- Skeleton -->
                <template v-if="loading">
                    <div
                        v-for="i in 6"
                        :key="i"
                        class="flex gap-4 p-4 border-b border-slate-50 animate-pulse"
                    >
                        <div
                            class="w-12 h-3 bg-slate-100 rounded shrink-0 mt-1"
                        ></div>
                        <div class="flex-1 h-16 bg-slate-100 rounded-xl"></div>
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

                    <!-- ── Timeline positionnement absolu ─────────────────── -->
                    <div v-else class="flex">
                        <!-- Colonne heures -->
                        <div
                            class="w-16 shrink-0 relative"
                            :style="`height: ${totalHeight}px`"
                        >
                            <div
                                v-for="slot in workHours"
                                :key="slot"
                                class="absolute right-0 pr-3 text-right"
                                :style="`top: ${slotTop(slot)}px`"
                            >
                                <span
                                    class="text-xs font-mono leading-none"
                                    :class="
                                        slot.endsWith(':00')
                                            ? 'text-slate-400'
                                            : 'text-slate-200'
                                    "
                                >
                                    {{ slot }}
                                </span>
                            </div>
                        </div>

                        <!-- Zone des RDV -->
                        <div
                            class="flex-1 border-l border-slate-100 relative"
                            :style="`height: ${totalHeight}px`"
                        >
                            <!-- Lignes de fond par slot -->
                            <div
                                v-for="slot in workHours"
                                :key="slot"
                                class="absolute left-0 right-0 border-t"
                                :class="
                                    slot.endsWith(':00')
                                        ? 'border-slate-100'
                                        : 'border-slate-50'
                                "
                                :style="`top: ${slotTop(slot)}px`"
                            ></div>

                            <!-- Cartes RDV — position et largeur calculées selon les chevauchements -->
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
        top: ${apptTop(appt)}px;
        height: ${apptHeight(appt)}px;
        min-height: 52px;
        left: calc(${(appt.col / appt.maxCols) * 100}% + 8px);
        width: calc(${(1 / appt.maxCols) * 100}% - 16px);
    `"
                            >
                                <div
                                    class="flex items-start justify-between gap-2 h-full overflow-hidden"
                                >
                                    <div class="min-w-0 flex-1 overflow-hidden">
                                        <!-- Nom + badge -->
                                        <div
                                            class="flex items-center gap-2 mb-1"
                                        >
                                            <span
                                                :class="[
                                                    'text-sm font-semibold truncate',
                                                    colorConfig(appt.color)
                                                        .title,
                                                ]"
                                            >
                                                {{ appt.patient.full_name }}
                                            </span>
                                            <span
                                                :class="[
                                                    'text-[10px] font-medium px-2 py-0.5 rounded-full shrink-0',
                                                    colorConfig(appt.color)
                                                        .badge,
                                                ]"
                                            >
                                                {{ statusLabel(appt.status) }}
                                            </span>
                                        </div>

                                        <!-- Heure -->
                                        <div
                                            class="flex items-center gap-1.5 mb-1"
                                        >
                                            <Clock
                                                class="w-3 h-3 text-slate-400 shrink-0"
                                            />
                                            <span
                                                class="text-xs text-slate-500 font-mono"
                                            >
                                                {{ appt.start_time }} →
                                                {{ appt.end_time }}
                                            </span>
                                        </div>

                                        <!-- Actes (masqués si RDV trop court) -->
                                        <div
                                            v-if="apptHeight(appt) > 80"
                                            class="flex items-center gap-1.5"
                                        >
                                            <Stethoscope
                                                class="w-3 h-3 text-slate-400 shrink-0"
                                            />
                                            <span
                                                class="text-xs text-slate-500 truncate"
                                            >
                                                {{
                                                    appt.acts.length
                                                        ? appt.acts
                                                              .map(
                                                                  (a) => a.name,
                                                              )
                                                              .join(" · ")
                                                        : "Aucun acte précisé"
                                                }}
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
import { ref, computed, onMounted } from "vue";
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

const {
    appointments,
    catalogActs,
    stats,
    loading,
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
} = useAppointments();

// ─── État local ───────────────────────────────────────────────────
const showModal = ref(false);
const modalSlot = ref(null);
const editingAppointment = ref(null);
const selectedAppointment = ref(null);

onMounted(async () => {
    await fetchAppointments();
    await fetchCatalogActs();
});
//
// ─── Constantes timeline ──────────────────────────────────────────
const SLOT_HEIGHT = 60; // px pour 30 minutes
const START_HOUR = 9;
const END_HOUR = 18;

// Hauteur totale de la zone timeline
const totalHeight = computed(() => (END_HOUR - START_HOUR) * 2 * SLOT_HEIGHT);

// Marqueurs de temps toutes les 30min
const workHours = computed(() => {
    const slots = [];
    for (let h = START_HOUR; h < END_HOUR; h++) {
        slots.push(`${String(h).padStart(2, "0")}:00`);
        slots.push(`${String(h).padStart(2, "0")}:30`);
    }
    return slots;
});

// Convertit "HH:MM" en minutes depuis START_HOUR
function timeToMinutes(time) {
    const [h, m] = time.split(":").map(Number);
    return (h - START_HOUR) * 60 + m;
}
// ─── Calcul des colonnes pour les RDV qui se chevauchent ─────────
// Inspiré de Google Calendar : les RDV qui se chevauchent
// sont affichés côte à côte au lieu de se superposer
const appointmentsWithLayout = computed(() => {
    // Convertit les heures en minutes pour faciliter les comparaisons
    const appts = appointments.value
        .map((a) => {
            const [sh, sm] = a.start_time.slice(0, 5).split(":").map(Number);
            const [eh, em] = a.end_time.slice(0, 5).split(":").map(Number);
            return { ...a, startMin: sh * 60 + sm, endMin: eh * 60 + em };
        })
        .sort((a, b) => a.startMin - b.startMin);

    // Pour chaque RDV, trouve ceux qui se chevauchent avec lui
    const withOverlap = appts.map((appt) => ({
        ...appt,
        overlapping: appts.filter(
            (o) =>
                o.id !== appt.id &&
                o.startMin < appt.endMin &&
                o.endMin > appt.startMin,
        ),
    }));

    // Assigne une colonne à chaque RDV
    const assigned = {};
    for (const appt of withOverlap) {
        const takenCols = appt.overlapping
            .filter((o) => assigned[o.id] !== undefined)
            .map((o) => assigned[o.id]);
        let col = 0;
        while (takenCols.includes(col)) col++;
        assigned[appt.id] = col;
    }

    // Calcule le nombre max de colonnes dans chaque groupe
    return withOverlap.map((appt) => {
        const groupCols = new Set([assigned[appt.id]]);
        appt.overlapping.forEach((o) => {
            if (assigned[o.id] !== undefined) groupCols.add(assigned[o.id]);
        });
        const maxCols = Math.max(...groupCols) + 1;
        return { ...appt, col: assigned[appt.id], maxCols };
    });
});
// Position top d'un slot (en px)
function slotTop(slot) {
    return timeToMinutes(slot) * (SLOT_HEIGHT / 30);
}

// Position top d'un RDV (en px)
function apptTop(appt) {
    return timeToMinutes(appt.start_time) * (SLOT_HEIGHT / 30);
}

// Hauteur d'un RDV proportionnelle à sa durée
function apptHeight(appt) {
    const [sh, sm] = appt.start_time.split(":").map(Number);
    const [eh, em] = appt.end_time.split(":").map(Number);
    const durationMin = eh * 60 + em - (sh * 60 + sm);
    return Math.max(durationMin * (SLOT_HEIGHT / 30), 52);
}

// ─── Ouvrir modal ─────────────────────────────────────────────────
function openModal(slot, appointment = null) {
    modalSlot.value = slot;
    editingAppointment.value = appointment;
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
        if (isEdit) await updateAppointment(data.id, data);
        else await createAppointment(data);
        showModal.value = false; // ferme seulement si succès
        modalError.value = null;
    } catch (e) {
        // Passe l'erreur à la modal → elle l'affiche
        modalError.value = e.message;
    }
}

// ─── Changer statut ───────────────────────────────────────────────
async function handleStatusChange(id, status) {
    await changeStatus(id, status);
    // Rafraîchit le panneau ouvert avec les nouvelles données
    const updated = appointments.value.find((a) => a.id === id);
    if (updated) selectedAppointment.value = { ...updated };
}

// ─── Annuler ─────────────────────────────────────────────────────
async function handleCancel(id) {
    if (!confirm("Annuler ce rendez-vous ?")) return;
    await cancelAppointment(id);
    selectedAppointment.value = null;
}

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
