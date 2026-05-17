<template>
    <Transition name="panel">
        <div
            v-if="consultation"
            class="h-full flex flex-col bg-white border-l border-slate-200 overflow-hidden"
        >
            <!-- ── Header ──────────────────────────────────────────────── -->
            <div
                class="flex items-start justify-between px-5 py-4 border-b border-slate-100"
            >
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <ConsultationStatusBadge
                            :status="consultation.status"
                        />
                        <span class="text-xs text-slate-400"
                            >#{{ consultation.id }}</span
                        >
                    </div>
                    <h2 class="text-base font-semibold text-slate-800 truncate">
                        {{ consultation.patient?.full_name }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Créée le {{ consultation.created_at }} ·
                        {{ consultation.sessions_count }} séance(s)
                    </p>
                </div>
                <button
                    @click="emit('close')"
                    class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors ml-2"
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
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- ── Corps scrollable ────────────────────────────────────── -->
            <div class="flex-1 overflow-y-auto">
                <!-- Dates des séances -->
                <div class="px-5 py-4 border-b border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-medium text-slate-500">
                            Séances
                        </p>
                        <button
                            v-if="
                                consultation.status === 'EN_COURS' && isDentist
                            "
                            @click="showAddSession = true"
                            class="text-xs text-blue-600 hover:text-blue-700 font-medium"
                        >
                            + Ajouter séance
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="(date, i) in consultation.session_dates"
                            :key="i"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-medium"
                        >
                            <svg
                                class="w-3 h-3"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            {{ formatDate(date) }}
                        </span>
                        <span
                            v-if="!consultation.session_dates?.length"
                            class="text-xs text-slate-400 italic"
                            >Aucune séance enregistrée</span
                        >
                    </div>

                    <!-- Form ajout séance -->
                    <div v-if="showAddSession" class="mt-3 flex gap-2">
                        <input
                            type="date"
                            v-model="newSessionDate"
                            :max="today"
                            class="flex-1 border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
                        />
                        <button
                            @click="submitAddSession"
                            :disabled="!newSessionDate || addingSession"
                            class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 disabled:bg-blue-200 transition-colors"
                        >
                            OK
                        </button>
                        <button
                            @click="
                                showAddSession = false;
                                newSessionDate = '';
                            "
                            class="px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100 rounded-lg transition-colors"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Notes globales -->
                <div
                    v-if="consultation.notes"
                    class="px-5 py-4 border-b border-slate-100"
                >
                    <p class="text-xs font-medium text-slate-500 mb-1.5">
                        Notes cliniques
                    </p>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        {{ consultation.notes }}
                    </p>
                </div>

                <!-- Schéma dentaire (mode view) -->
                <!-- Schéma dentaire (mode view) -->
                <div class="px-5 py-4 border-b border-slate-100">
                    <p class="text-xs font-medium text-slate-500 mb-3">
                        Dents traitées
                    </p>
                    <div class="overflow-y-auto" style="max-height: 420px">
                        <ToothChart
                            mode="view"
                            :actsByTooth="actsByTooth"
                            class="w-full"
                            style="aspect-ratio: 3027 / 4850"
                        />
                    </div>
                </div>

                <!-- Liste des actes -->
                <div class="px-5 py-4">
                    <p class="text-xs font-medium text-slate-500 mb-3">
                        Interventions
                        <span class="text-slate-300 font-normal"
                            >({{ consultation.acts?.length ?? 0 }})</span
                        >
                    </p>

                    <div class="space-y-3" v-if="consultation.acts?.length">
                        <ConsultationActRow
                            v-for="act in consultation.acts"
                            :key="act.id"
                            :act="act"
                            :editable="false"
                        />
                    </div>
                    <p
                        v-else
                        class="text-xs text-slate-400 italic text-center py-4"
                    >
                        Aucune intervention enregistrée
                    </p>

                    <!-- Total -->
                    <div
                        class="mt-4 flex justify-between items-center pt-3 border-t border-slate-100"
                    >
                        <span class="text-sm font-medium text-slate-500"
                            >Total consultation</span
                        >
                        <span class="text-lg font-bold text-slate-800">
                            {{ consultation.total_price?.toFixed(0) ?? 0 }} MAD
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── Actions ─────────────────────────────────────────────── -->
            <div class="border-t border-slate-100 px-5 py-4 space-y-2">
                <!-- Clôturer -->
                <button
                    v-if="consultation.status !== 'TERMINE' && isDentist"
                    @click="handleClose"
                    :disabled="actionLoading"
                    class="w-full py-2.5 rounded-xl text-sm font-medium transition-colors bg-emerald-600 text-white hover:bg-emerald-700 disabled:bg-emerald-200 flex items-center justify-center gap-2"
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
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    Clôturer la consultation
                </button>

                <!-- Modifier -->
                <button
                    v-if="consultation.status !== 'TERMINE'"
                    @click="emit('edit', consultation)"
                    class="w-full py-2.5 rounded-xl text-sm font-medium transition-colors bg-blue-50 text-blue-700 hover:bg-blue-100"
                >
                    Modifier
                </button>

                <!-- Supprimer -->
                <button
                    v-if="isDentist"
                    @click="handleDelete"
                    :disabled="actionLoading"
                    class="w-full py-2 rounded-xl text-sm font-medium transition-colors text-red-500 hover:bg-red-50"
                >
                    Supprimer
                </button>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed } from "vue";
// APRÈS
import ConsultationStatusBadge from "./ConsultationStatusBadge.vue"; // ✅
import ConsultationActRow from "./ConsultationActRow.vue"; // ✅
import ToothChart from "./ToothChart.vue"; // ✅
import { useConsultations } from "../../composables/useConsultations"; // ⬆️ 2 niveaux

// ─── Props ────────────────────────────────────────────────────────
const props = defineProps({
    consultation: { type: Object, default: null },
    isDentist: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "edit", "updated", "deleted"]);

const { addSession, closeConsultation, deleteConsultation } =
    useConsultations();

// ─── Séance ───────────────────────────────────────────────────────
const showAddSession = ref(false);
const newSessionDate = ref("");
const addingSession = ref(false);
const actionLoading = ref(false);

const today = new Date().toISOString().split("T")[0];

// ─── actsByTooth — structure { 46: [{...}, ...] } pour ToothChart ──
const actsByTooth = computed(() => {
    const map = {};
    (props.consultation?.acts ?? []).forEach((act) => {
        const teethCount = (act.teeth ?? []).length || 1;
        const pricePerTooth = Number(act.price) / teethCount; // ← prix divisé par nb dents

        (act.teeth ?? []).forEach((tooth) => {
            if (!map[tooth]) map[tooth] = [];
            map[tooth].push({
                ...act,
                price: pricePerTooth, // ← 150 MAD par dent
                date:
                    props.consultation.session_dates?.[0] ??
                    props.consultation.created_at,
            });
        });
    });
    return map;
});

// ─── Actions ──────────────────────────────────────────────────────
async function submitAddSession() {
    if (!newSessionDate.value) return;
    addingSession.value = true;
    try {
        await addSession(props.consultation.id, newSessionDate.value);
        showAddSession.value = false;
        newSessionDate.value = "";
        emit("updated");
    } finally {
        addingSession.value = false;
    }
}

async function handleClose() {
    if (!confirm("Clôturer définitivement cette consultation ?")) return;
    actionLoading.value = true;
    try {
        await closeConsultation(props.consultation.id);
        emit("updated");
    } finally {
        actionLoading.value = false;
    }
}

async function handleDelete() {
    if (
        !confirm(
            "Supprimer cette consultation ? Cette action est irréversible.",
        )
    )
        return;
    actionLoading.value = true;
    try {
        await deleteConsultation(props.consultation.id);
        emit("deleted");
        emit("close");
    } finally {
        actionLoading.value = false;
    }
}

// ─── Helpers ──────────────────────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return "—";
    const [y, m, d] = dateStr.split("-");
    return `${d}/${m}/${y}`;
}
</script>

<style scoped>
.panel-enter-active,
.panel-leave-active {
    transition: all 0.2s ease;
}
.panel-enter-from {
    transform: translateX(100%);
    opacity: 0;
}
.panel-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
</style>
