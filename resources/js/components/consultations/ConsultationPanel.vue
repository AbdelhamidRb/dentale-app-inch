<template>
    <Transition name="panel">
        <div v-if="consultation" class="flex-1 min-h-0 flex flex-col bg-white lg:border-l border-slate-200 overflow-hidden">

            <!-- ── Header ─────────────────────────────────────────────── -->
            <div class="flex items-start justify-between px-4 py-3 border-b border-slate-100 shrink-0">
                <!-- Bouton retour mobile -->
                <button @click="emit('close')"
                        class="lg:hidden flex items-center gap-1 mr-3 shrink-0 text-blue-600 hover:text-blue-800 transition-colors py-1">
                    <ChevronLeft class="w-4 h-4" />
                    <span class="text-xs font-medium">Retour</span>
                </button>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <ConsultationStatusBadge :status="consultation.status" />
                        <span class="text-xs text-slate-400">#{{ consultation.id }}</span>
                    </div>
                    <h2 class="text-base font-semibold text-slate-800 truncate">
                        {{ consultation.patient?.full_name }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ consultation.created_at }}
                        · {{ consultation.sessions_count }} séance{{ consultation.sessions_count !== 1 ? 's' : '' }}
                    </p>
                </div>

                <!-- Bouton fermer desktop -->
                <button @click="emit('close')"
                        class="hidden lg:flex shrink-0 w-8 h-8 items-center justify-center rounded-lg
                               text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors ml-2">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- ── Corps scrollable ───────────────────────────────────── -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 pb-16 lg:pb-0">

                <!-- ① Interventions — info principale en premier -->
                <div class="px-4 py-4">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-3">
                        Interventions
                        <span class="font-normal normal-case">
                            ({{ consultation.acts?.length ?? 0 }})
                        </span>
                    </p>

                    <div v-if="consultation.acts?.length" class="space-y-2">
                        <div v-for="act in consultation.acts" :key="act.id"
                             class="p-3 bg-slate-50 rounded-xl">
                            <div class="flex items-start justify-between gap-2 mb-1.5">
                                <p class="text-sm font-semibold text-slate-800 leading-tight">
                                    {{ act.catalog_act?.name }}
                                </p>
                                <span class="text-sm font-bold text-blue-600 shrink-0">
                                    {{ fmt(act.price) }} MAD
                                </span>
                            </div>
                            <!-- Dents de cet acte -->
                            <div v-if="act.teeth?.length" class="flex flex-wrap gap-1">
                                <span v-for="t in act.teeth" :key="t"
                                      class="px-1.5 py-0.5 bg-blue-100 text-blue-700
                                             text-[10px] font-semibold rounded">
                                    {{ t }}
                                </span>
                            </div>
                            <p v-else class="text-[10px] text-slate-400">Acte global</p>
                            <p v-if="act.notes" class="text-xs text-slate-500 italic mt-1">
                                {{ act.notes }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-xs text-slate-400 italic py-2">
                        Aucune intervention enregistrée
                    </p>

                    <!-- Total -->
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-500">Total consultation</span>
                        <span class="text-base font-bold text-slate-800">
                            {{ consultation.total_price?.toFixed(0) ?? 0 }} MAD
                        </span>
                    </div>
                </div>

                <!-- ② Séances -->
                <div class="px-4 py-4">
                    <div class="flex items-center justify-between mb-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">
                            Séances
                        </p>
                        <button v-if="consultation.status === 'EN_COURS' && isDentist"
                                @click="showAddSession = true"
                                class="text-xs text-blue-600 hover:text-blue-700 font-medium transition-colors">
                            + Ajouter
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="(date, i) in consultation.session_dates" :key="i"
                              class="inline-flex items-center gap-1.5 px-2.5 py-1
                                     rounded-full bg-blue-100 text-blue-700 text-xs font-medium">
                            <Calendar class="w-3 h-3 shrink-0" />
                            {{ formatDate(date) }}
                        </span>
                        <span v-if="!consultation.session_dates?.length"
                              class="text-xs text-slate-400 italic">
                            Aucune séance enregistrée
                        </span>
                    </div>

                    <!-- Form ajout séance -->
                    <div v-if="showAddSession" class="mt-3 flex gap-2">
                        <input type="date" v-model="newSessionDate" :max="today"
                               class="flex-1 border border-slate-200 rounded-lg px-3 py-1.5 text-sm bg-white
                                      focus:outline-none focus:ring-2 focus:ring-blue-300"/>
                        <button @click="submitAddSession"
                                :disabled="!newSessionDate || addingSession"
                                class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg
                                       hover:bg-blue-700 disabled:bg-blue-200 transition-colors">
                            OK
                        </button>
                        <button @click="showAddSession = false; newSessionDate = ''"
                                class="px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100 rounded-lg transition-colors">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- ③ Notes cliniques -->
                <div v-if="consultation.notes" class="px-4 py-4">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2">
                        Notes cliniques
                    </p>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        {{ consultation.notes }}
                    </p>
                </div>

                <!-- ④ Schéma dentaire — miniature + modal plein écran -->
                <div class="px-4 py-4">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-3">
                        Schéma dentaire
                    </p>

                    <!-- Miniature non-interactive -->
                    <div class="flex justify-center">
                        <div class="relative bg-white rounded-xl border border-slate-100 overflow-hidden w-full"
                             style="aspect-ratio: 3027/4850; max-height: 300px; max-width: calc(300px * 3027 / 4850);">
                            <div class="w-full h-full pointer-events-none select-none opacity-90">
                                <ToothChart mode="view" :actsByTooth="actsByTooth" :hide-legend="true" />
                            </div>
                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-white/70 via-transparent to-transparent
                                        flex flex-col items-center justify-end pb-4 gap-1.5">
                                <p class="text-[10px] text-slate-400">Cliquez pour interagir</p>
                                <button @click="showChart = true"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700
                                               text-white text-xs font-semibold rounded-lg shadow transition-colors">
                                    <Maximize2 class="w-3.5 h-3.5" />
                                    Ouvrir le schéma
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Actions ────────────────────────────────────────────── -->
            <div class="border-t border-slate-100 px-4 pt-3 pb-20 lg:pb-3 shrink-0 space-y-2">
                <!-- Clôturer : seulement si pas encore terminée -->
                <button v-if="consultation.status !== 'TERMINE' && isDentist"
                        @click="showConfirmClose = true"
                        :disabled="actionLoading"
                        class="w-full py-2.5 rounded-xl text-sm font-medium transition-colors
                               bg-green-600 text-white hover:bg-green-700
                               disabled:bg-green-200 flex items-center justify-center gap-2">
                    <ClipboardCheck class="w-4 h-4" />
                    Clôturer
                </button>

                <!-- Supprimer : dentiste uniquement -->
                <button v-if="isDentist"
                        @click="showConfirmDelete = true"
                        :disabled="actionLoading"
                        class="w-full py-2.5 rounded-xl text-sm font-medium transition-colors
                               border border-red-200 bg-red-50 text-red-600 hover:bg-red-100
                               disabled:opacity-50 flex items-center justify-center gap-2">
                    <Trash2 class="w-4 h-4" />
                    Supprimer
                </button>
            </div>
        </div>
    </Transition>

    <ConfirmModal v-model="showConfirmClose"
        title="Clôturer la consultation"
        message="La consultation sera marquée comme <strong>Terminée</strong>. Cette action est irréversible."
        confirm-label="Clôturer"
        @confirm="handleClose" />

    <ConfirmModal v-model="showConfirmDelete"
        title="Supprimer la consultation"
        message="Cette consultation et toutes ses interventions seront <strong>supprimées définitivement</strong>."
        confirm-label="Supprimer"
        @confirm="handleDelete" />

    <!-- ══════════════════════════════════════════════════════════
         Modal schéma dentaire — 2 colonnes
    ═══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
                    enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showChart"
                 class="fixed inset-0 bg-black/60 z-[300] flex items-center justify-center p-4"
                 @click.self="showChart = false">
                <Transition enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                    <div v-if="showChart" class="bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden"
                         style="width: min(1040px, 96vw); height: 90vh;">

                        <!-- Header -->
                        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 shrink-0">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-800">Schéma dentaire</h3>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ consultation.patient?.full_name }}
                                    · {{ consultation.acts?.length ?? 0 }} acte{{ (consultation.acts?.length ?? 0) !== 1 ? 's' : '' }}
                                </p>
                            </div>
                            <button @click="showChart = false"
                                    class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Corps : SVG haut + détail dent bas (mobile) / SVG gauche + détail dent droite (desktop) -->
                        <div class="flex flex-col sm:flex-row flex-1 min-h-0">

                            <!-- Colonne SVG -->
                            <div class="flex-1 min-h-0 flex items-center justify-center p-3 overflow-hidden border-b sm:border-b-0 sm:border-r border-slate-100">
                                <div style="aspect-ratio: 3027/4850; height: 100%; max-width: 100%;">
                                    <ToothChart
                                        mode="view"
                                        :actsByTooth="actsByTooth"
                                        :active-tooth="activeTooth"
                                        :show-popup="false"
                                        :hide-legend="true"
                                        @tooth-clicked="toggleTooth"
                                    />
                                </div>
                            </div>

                            <!-- Colonne historique dent -->
                            <div class="h-56 sm:h-auto sm:w-72 shrink-0 flex flex-col overflow-hidden">

                                <!-- Légende -->
                                <div class="px-4 py-2.5 border-b border-slate-100 shrink-0 bg-slate-50/60">
                                    <div class="flex flex-wrap gap-3">
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-3 h-3 rounded-sm" style="background:#f8f6f0;border:1.5px solid #cbd5e1"></div>
                                            <span class="text-[11px] text-slate-400">Sans acte</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-3 h-3 rounded-sm bg-blue-200 border border-blue-400"></div>
                                            <span class="text-[11px] text-slate-400">1 acte</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-3 h-3 rounded-sm bg-orange-200 border border-orange-400"></div>
                                            <span class="text-[11px] text-slate-400">Plusieurs actes</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- État vide -->
                                <div v-if="!activeTooth"
                                     class="flex-1 flex flex-col items-center justify-center gap-3 p-6 text-center">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
                                        <MousePointer class="w-5 h-5 text-slate-300" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-500">Sélectionnez une dent</p>
                                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                            Cliquez sur une dent<br>pour voir ses actes
                                        </p>
                                    </div>
                                </div>

                                <!-- Actes de la dent sélectionnée -->
                                <template v-else>
                                    <div class="px-4 py-3 border-b border-slate-100 shrink-0 bg-slate-50/50">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                                                <span class="text-violet-700 text-sm font-bold">{{ activeTooth }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-sm font-semibold text-slate-800">Dent {{ activeTooth }}</h4>
                                                <p class="text-xs text-slate-400">
                                                    {{ activeActs.length }} acte{{ activeActs.length !== 1 ? 's' : '' }}
                                                    <span v-if="activeTotal > 0"> · {{ activeTotal.toLocaleString('fr-FR') }} MAD</span>
                                                </p>
                                            </div>
                                            <button @click="activeTooth = null"
                                                    class="ml-auto p-1 rounded-lg hover:bg-slate-100 text-slate-300
                                                           hover:text-slate-500 transition-colors">
                                                <X class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex-1 overflow-y-auto p-3 space-y-2">
                                        <div v-if="!activeActs.length" class="flex flex-col items-center justify-center py-10 gap-2">
                                            <p class="text-xs text-slate-400 text-center">Aucun acte enregistré</p>
                                        </div>
                                        <div v-else v-for="(act, i) in activeActs" :key="i"
                                             class="p-3 bg-white rounded-xl border border-slate-100 shadow-sm">
                                            <div class="flex items-start justify-between gap-2 mb-1">
                                                <p class="text-xs font-semibold text-slate-700 leading-tight">
                                                    {{ act.catalog_act?.name }}
                                                </p>
                                                <span class="text-xs font-bold text-blue-600 shrink-0">
                                                    {{ fmt(act.price) }} MAD
                                                </span>
                                            </div>
                                            <p v-if="act.notes" class="text-[11px] text-slate-500 mt-1 italic leading-tight">
                                                {{ act.notes }}
                                            </p>
                                        </div>
                                    </div>

                                    <div v-if="activeActs.length > 1"
                                         class="px-4 py-3 border-t border-slate-100 shrink-0 bg-slate-50/50">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-500">Total dent {{ activeTooth }}</span>
                                            <span class="text-sm font-bold text-slate-700">
                                                {{ activeTotal.toLocaleString('fr-FR') }} MAD
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { ChevronLeft, X, ClipboardCheck, Trash2, Calendar, Maximize2, MousePointer } from 'lucide-vue-next';
import ConsultationStatusBadge from './ConsultationStatusBadge.vue';
import ToothChart from './ToothChart.vue';
import ConfirmModal from '../ui/ConfirmModal.vue';
import { useConsultations } from '../../composables/useConsultations';

const props = defineProps({
    consultation: { type: Object, default: null },
    isDentist:    { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'updated', 'deleted']);

const { addSession, closeConsultation, deleteConsultation } = useConsultations();

// ─── Séance ───────────────────────────────────────────────────────
const showAddSession = ref(false);
const newSessionDate = ref('');
const addingSession  = ref(false);
const actionLoading  = ref(false);
const today = new Date().toISOString().split('T')[0];

// ─── Modal schéma dentaire ────────────────────────────────────────
const showConfirmClose  = ref(false);
const showConfirmDelete = ref(false);
const showChart   = ref(false);
const activeTooth = ref(null);
const activeActs  = computed(() => actsByTooth.value[activeTooth.value] ?? []);
const activeTotal = computed(() =>
    activeActs.value.reduce((s, a) => s + Number(a.price || 0), 0)
);
function toggleTooth(id) {
    activeTooth.value = activeTooth.value === id ? null : id;
}
watch(showChart, v => { if (!v) activeTooth.value = null; });

// ─── actsByTooth pour ToothChart ──────────────────────────────────
const actsByTooth = computed(() => {
    const map = {};
    (props.consultation?.acts ?? []).forEach((act) => {
        const teethCount = (act.teeth ?? []).length || 1;
        const pricePerTooth = Number(act.price) / teethCount;
        (act.teeth ?? []).forEach((tooth) => {
            if (!map[tooth]) map[tooth] = [];
            map[tooth].push({
                ...act,
                price: pricePerTooth,
                date: props.consultation.session_dates?.[0] ?? props.consultation.created_at,
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
        newSessionDate.value = '';
        emit('updated');
    } finally {
        addingSession.value = false;
    }
}

async function handleClose() {
    actionLoading.value = true;
    try {
        await closeConsultation(props.consultation.id);
        emit('updated');
    } finally {
        actionLoading.value = false;
    }
}

async function handleDelete() {
    actionLoading.value = true;
    try {
        await deleteConsultation(props.consultation.id);
        emit('deleted');
        emit('close');
    } finally {
        actionLoading.value = false;
    }
}

// ─── Helpers ──────────────────────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return '—';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

function fmt(val) {
    return Math.round(Number(val ?? 0)).toLocaleString('fr-FR');
}
</script>

<style scoped>
.panel-enter-active, .panel-leave-active { transition: all 0.2s ease; }
.panel-enter-from, .panel-leave-to { transform: translateX(100%); opacity: 0; }
</style>
