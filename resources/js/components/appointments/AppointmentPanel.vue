<template>
    <div class="flex flex-col h-full">
        <!-- En-tête -->
        <div
            class="flex items-start justify-between p-4 border-b border-slate-100"
        >
            <div>
                <h2 class="font-semibold text-slate-800 text-sm">
                    {{ appointment.patient.full_name }}
                </h2>
                <p class="text-xs text-slate-400 mt-0.5 font-mono">
                    {{ appointment.start_time }} → {{ appointment.end_time }}
                </p>
            </div>
            <div class="flex items-center gap-1">
                <button @click="$emit('edit')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
                    title="Modifier">
                    <Pencil class="w-4 h-4" />
                </button>
                <button @click="$emit('close')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
                    title="Fermer">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4 pb-16 lg:pb-4">
            <!-- Badge statut actuel -->
            <div
                :class="`inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium ${statusStyle.badge}`"
            >
                <div :class="`w-2 h-2 rounded-full ${statusStyle.dot}`"></div>
                {{ statusLabel }}
            </div>

            <!-- Changer le statut -->
            <div>
                <p class="text-xs font-medium text-slate-500 mb-2">
                    Changer le statut
                </p>
                <div class="grid grid-cols-2 gap-1.5">
                    <button
                        v-for="s in availableStatuses"
                        :key="s.value"
                        @click="
                            $emit('status-changed', appointment.id, s.value)
                        "
                        :disabled="appointment.status === s.value"
                        :class="s.classes"
                        class="py-1.5 px-2 rounded-lg text-xs font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        {{ s.label }}
                    </button>
                </div>
            </div>

            <!-- Infos patient -->
            <div class="p-3 bg-slate-50 rounded-lg space-y-2">
                <p
                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide"
                >
                    Patient
                </p>
                <div class="flex items-center gap-2">
                    <User class="w-4 h-4 text-slate-400 shrink-0" />
                    <span class="text-sm text-slate-700">{{
                        appointment.patient.full_name
                    }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <Phone class="w-4 h-4 text-slate-400 shrink-0" />
                    <span class="text-sm text-slate-700">{{
                        appointment.patient.phone
                    }}</span>
                </div>
            </div>

            <!-- Actes prévus -->
            <div>
                <p
                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2"
                >
                    Actes prévus
                </p>
                <div v-if="appointment.acts.length > 0" class="space-y-1.5">
                    <div
                        v-for="act in appointment.acts"
                        :key="act.id"
                        class="flex items-center gap-2 px-3 py-2 bg-slate-50 rounded-lg"
                    >
                        <Stethoscope
                            class="w-3.5 h-3.5 text-slate-400 shrink-0"
                        />
                        <span class="text-sm text-slate-700">{{
                            act.name
                        }}</span>
                    </div>
                </div>
                <p v-else class="text-xs text-slate-400 italic">
                    Aucun acte précisé
                </p>
            </div>

            <!-- Notes -->
            <div v-if="appointment.notes">
                <p
                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2"
                >
                    Notes
                </p>
                <p class="text-sm text-slate-700 p-3 bg-slate-50 rounded-lg">
                    {{ appointment.notes }}
                </p>
            </div>

            <!-- Créé par -->
            <div class="pt-2 border-t border-slate-100">
                <p class="text-xs text-slate-400">
                    Créé par
                    <span class="font-medium">{{
                        appointment.created_by.name
                    }}</span>
                    le {{ appointment.created_at }}
                </p>
            </div>

            <!-- Supprimer = disparaît de l'agenda -->
            <!-- Créer une consultation -->
            <!-- Consultation : bouton principal -->
            <div v-if="isDentist && appointment?.status !== 'ANNULE'">
                <!-- Message succès continuation -->
                <div
                    v-if="continuationSuccess"
                    class="flex items-center gap-2 px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-700"
                >
                    <svg
                        class="w-4 h-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    Séance ajoutée à {{ continuationSuccess }}
                </div>

                <!-- Message erreur continuation -->
                <div
                    v-if="continuationError"
                    class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-xl text-xs text-red-600"
                >
                    ⚠️ {{ continuationError }}
                </div>
                <!-- Bouton déclencheur -->
                <button
                    v-if="!showConsultOptions"
                    @click="openConsultOptions"
                    class="w-full py-2.5 rounded-xl text-sm font-medium transition-colors bg-violet-50 text-violet-700 hover:bg-violet-100 flex items-center justify-center gap-2"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                                 a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    Consultation
                </button>

                <!-- Panel choix -->
                <div
                    v-else
                    class="border border-violet-200 rounded-xl overflow-hidden"
                >
                    <!-- En-tête -->
                    <div
                        class="flex items-center justify-between px-3 py-2 bg-violet-50 border-b border-violet-100"
                    >
                        <span class="text-xs font-medium text-violet-700">
                            Type de consultation
                        </span>
                        <button
                            @click="showConsultOptions = false"
                            class="text-violet-400 hover:text-violet-600 text-xs"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Option 1 : Nouvelle consultation -->
                    <button
                        @click="
                            showConsultOptions = false;
                            showConsultationModal = true;
                        "
                        class="w-full flex items-center gap-3 px-4 py-3 hover:bg-violet-50 transition-colors border-b border-slate-100"
                    >
                        <div
                            class="w-7 h-7 bg-violet-100 rounded-lg flex items-center justify-center shrink-0"
                        >
                            <svg
                                class="w-4 h-4 text-violet-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-medium text-slate-700">
                                Nouvelle consultation
                            </p>
                            <p class="text-xs text-slate-400">
                                Créer un nouveau dossier
                            </p>
                        </div>
                    </button>

                    <!-- Option 2 : Continuer une consultation -->
                    <div class="px-4 py-3">
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center shrink-0"
                            >
                                <svg
                                    class="w-4 h-4 text-amber-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582
                                             9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0
                                             01-15.357-2m15.357 2H15"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-700">
                                    Continuer une consultation
                                </p>
                                <p class="text-xs text-slate-400">
                                    Ajouter une séance à une consultation en
                                    cours
                                </p>
                            </div>
                        </div>

                        <!-- Loading -->
                        <div
                            v-if="loadingConsult"
                            class="flex items-center justify-center py-3"
                        >
                            <div
                                class="w-4 h-4 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"
                            ></div>
                        </div>

                        <!-- Erreur réseau -->
                        <p
                            v-else-if="consultError"
                            class="text-xs text-red-500 italic text-center py-2"
                        >
                            {{ consultError }}
                        </p>

                        <!-- Aucune EN_COURS -->
                        <p
                            v-else-if="!enCoursConsultations.length"
                            class="text-xs text-slate-400 italic text-center py-2"
                        >
                            Aucune consultation en cours pour ce patient
                        </p>

                        <!-- Liste consultations EN_COURS -->
                        <div
                            v-else
                            class="space-y-1.5 max-h-40 overflow-y-auto"
                        >
                            <button
                                v-for="c in enCoursConsultations"
                                :key="c.id"
                                @click="
                                    !continuationLoading &&
                                    continueConsultation(c)
                                "
                                :disabled="continuationLoading"
                                class="w-full text-left px-3 py-2 rounded-lg bg-amber-50 hover:bg-amber-100 border border-amber-200 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <p
                                        class="text-xs font-medium text-amber-800 truncate"
                                    >
                                        {{
                                            c.acts
                                                ?.map(
                                                    (a) => a.catalog_act?.name,
                                                )
                                                .join(", ") ||
                                            "Consultation #" + c.id
                                        }}
                                    </p>
                                    <span
                                        class="text-[10px] text-amber-600 shrink-0 ml-2"
                                    >
                                        {{ c.sessions_count }} séance(s)
                                    </span>
                                </div>
                                <p class="text-[10px] text-amber-600 mt-0.5">
                                    Créée le {{ c.created_at }}
                                </p>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supprimer — dentiste uniquement -->
            <button
                v-if="isDentist"
                @click="confirmDelete"
                class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl
                       text-sm font-medium text-red-500 hover:bg-red-50 transition-colors"
            >
                <Trash2 class="w-4 h-4" />
                Supprimer le rendez-vous
            </button>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <Teleport to="body">
        <div
            v-if="showDeleteConfirm"
            class="fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4"
            @click.self="showDeleteConfirm = false"
        >
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
                <!-- Icône d'avertissement -->
                <div class="flex flex-col items-center px-6 pt-6 pb-4 text-center">
                    <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <Trash2 class="w-7 h-7 text-red-500" />
                    </div>
                    <h3 class="font-semibold text-slate-800 text-base mb-1">
                        Supprimer ce rendez-vous ?
                    </h3>
                    <p class="text-sm text-slate-500">
                        Le rendez-vous de
                        <span class="font-medium text-slate-700">{{ appointment.patient.full_name }}</span>
                        sera définitivement supprimé. Cette action est irréversible.
                    </p>
                </div>
                <!-- Boutons -->
                <div class="flex border-t border-slate-100">
                    <button
                        @click="showDeleteConfirm = false"
                        class="flex-1 py-3.5 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors border-r border-slate-100"
                    >
                        Annuler
                    </button>
                    <button
                        @click="doDelete"
                        class="flex-1 py-3.5 text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors"
                    >
                        Supprimer
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <ConsultationModal
        v-model="showConsultationModal"
        :appointment-patient="appointment?.patient"
        :appointment-id="appointment?.id"
        :catalog-acts="catalogActs"
        :patients="appointment?.patient ? [appointment.patient] : []"
        @saved="onConsultationCreated"
    />
</template>

<script setup>
// Dans AppointmentPanel.vue
import { computed, ref, onMounted } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
import {
    X,
    Pencil,
    User,
    Phone,
    Stethoscope,
    XCircle,
    Trash2,
} from "lucide-vue-next";
// APRÈS
import ConsultationModal from "../consultations/ConsultationModal.vue"; // dossier voisin
import { consultationsApi } from "../../api/consultations"; // ⬆️ 2 niveaux

const props = defineProps({
    appointment: { type: Object, required: true },
});
const emit = defineEmits([
    "close",
    "edit",
    "status-changed",
    "cancelled",
    "consultation-created",
]);

// ─── Label du statut actuel ───────────────────────────────────────
const statusLabel = computed(
    () =>
        ({
            PLANIFIE: "Planifié",
            CONFIRME: "Confirmé",
            EN_COURS: "En cours",
            TERMINE: "Terminé",
            ANNULE: "Annulé",
            NO_SHOW: "Absent",
        })[props.appointment.status] || props.appointment.status,
);

// ─── Style du badge selon le statut ──────────────────────────────
const statusStyle = computed(
    () =>
        ({
            PLANIFIE: {
                badge: "bg-slate-100 text-slate-700",
                dot: "bg-slate-400",
            },
            CONFIRME: {
                badge: "bg-blue-100 text-blue-700",
                dot: "bg-blue-400",
            },
            EN_COURS: {
                badge: "bg-amber-100 text-amber-700",
                dot: "bg-amber-400",
            },
            TERMINE: {
                badge: "bg-green-100 text-green-700",
                dot: "bg-green-400",
            },
            ANNULE: { badge: "bg-red-100 text-red-700", dot: "bg-red-400" },
            NO_SHOW: {
                badge: "bg-orange-100 text-orange-700",
                dot: "bg-orange-400",
            },
        })[props.appointment.status],
);

// ─── Boutons de changement de statut disponibles ─────────────────
const availableStatuses = [
    {
        value: "PLANIFIE",
        label: "Planifié",
        classes: "bg-slate-50 text-slate-700 hover:bg-slate-100",
    },
    {
        value: "TERMINE",
        label: "Terminé",
        classes: "bg-green-50 text-green-700 hover:bg-green-100",
    },
    {
        value: "NO_SHOW",
        label: "Absent",
        classes: "bg-orange-50 text-orange-700 hover:bg-orange-100",
    },
];

const showConsultationModal = ref(false);
const catalogActs = ref([]);

// ─── Nouvelle vs Continuation ─────────────────────────────────────
const showConsultOptions = ref(false);
const enCoursConsultations = ref([]);
const loadingConsult = ref(false);

async function openConsultOptions() {
    showConsultOptions.value = true;
    enCoursConsultations.value = [];
    consultError.value = null;
    loadingConsult.value = true;
    try {
        const patientId = props.appointment?.patient?.id;
        if (!patientId) return;
        const res = await fetch(
            `/api/consultations?patient_id=${patientId}&status=EN_COURS`,
            {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                    Accept: "application/json",
                },
            },
        );
        const data = await res.json();
        enCoursConsultations.value = data.data ?? [];
    } catch {
        enCoursConsultations.value = [];
        // Affiche un message dans la zone "Aucune consultation"
        consultError.value = "Impossible de charger les consultations.";
    } finally {
        loadingConsult.value = false;
    }
}

const consultError = ref(null);
const continuationSuccess = ref(null);
const continuationError = ref(null);
const continuationLoading = ref(false);

async function continueConsultation(consultation) {
    continuationError.value = null;
    continuationLoading.value = true;
    try {
        const today = new Date().toISOString().split("T")[0];
        const res = await fetch(
            `/api/consultations/${consultation.id}/session`,
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({ date: today, appointment_id: props.appointment.id }),
            },
        );
        const data = await res.json();

        if (!res.ok) {
            continuationError.value = data.message ?? "Erreur";
            return;
        }

        showConsultOptions.value = false;
        emit("consultation-created", consultation);
        emit("status-changed", props.appointment.id, "TERMINE");

        // ── Redirige vers la fiche consultation ──────────────────
        router.push({
            name: "consultations",
            query: { id: consultation.id },
        });
    } catch (e) {
        continuationError.value = "Erreur réseau";
    } finally {
        continuationLoading.value = false;
    }
}

function onConsultationCreated(event) {
    emit("consultation-created", event);
    emit("status-changed", props.appointment.id, "TERMINE");
}

// isDentist manquant — à ajouter ici
const isDentist = computed(() => {
    try {
        const user = JSON.parse(localStorage.getItem("user") || "{}");
        return user.role === "DENTIST";
    } catch {
        return false;
    }
});

const showDeleteConfirm = ref(false);

function confirmDelete() {
    showDeleteConfirm.value = true;
}

function doDelete() {
    showDeleteConfirm.value = false;
    emit('cancelled', props.appointment.id);
}

// Charge le catalogue une fois au montage
onMounted(async () => {
    try {
        const res = await fetch("/api/catalog-acts", {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
                Accept: "application/json",
            },
        });
        const data = await res.json();
        catalogActs.value = Array.isArray(data) ? data : [];
    } catch {}
});
</script>
