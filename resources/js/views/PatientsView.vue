<template>
    <div class="flex flex-1 min-h-0">
        <!-- ══════════════════════════════════════════════════════════
         COLONNE GAUCHE — Liste des patients
         ══════════════════════════════════════════════════════════ -->
        <div
            class="min-w-0 transition-all duration-300"
            :class="selected || panelLoading
                ? 'hidden lg:flex lg:flex-col lg:w-[420px] lg:shrink-0 lg:mr-5'
                : 'flex flex-col flex-1'"
        >
            <!-- ── En-tête : titre + bouton nouveau ─────────────────── -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-base font-semibold text-slate-800">
                        Patients
                    </h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ meta.total }} patients au total
                    </p>
                </div>
                <button
                    @click="openForm(null)"
                    class="flex items-center gap-2 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <Plus class="w-4 h-4" />
                    <span class="hidden sm:inline">Nouveau patient</span>
                </button>
            </div>

            <!-- ── Barre de recherche + filtres ─────────────────────── -->
            <div class="flex gap-2 mb-3">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Nom, téléphone…"
                        class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <button v-if="filters.search" @click="filters.search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Filtre statut -->
                <select v-model="filters.status"
                    class="px-2 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">Tous</option>
                    <option value="ACTIF">Actifs</option>
                    <option value="INACTIF">Inactifs</option>
                </select>

                <!-- Toggle archivés -->
                <button @click="filters.archived = !filters.archived"
                    :class="filters.archived ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-white text-slate-500 border-slate-200'"
                    class="px-2.5 py-2 border rounded-lg text-sm transition-colors shrink-0">
                    Archivés
                </button>
            </div>

            <!-- ── Alerte erreur ──────────────────────────────────────── -->
            <div
                v-if="error"
                class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm"
            >
                {{ error }}
            </div>

            <!-- ── Tableau patients ───────────────────────────────────── -->
            <div
                class="flex-1 bg-white rounded-xl border border-slate-200 overflow-hidden flex flex-col"
            >
                <!-- En-tête du tableau (desktop uniquement) -->
                <div class="hidden lg:grid grid-cols-[6rem_1fr_5rem_7rem_2rem] gap-3 px-4 py-2.5 border-b border-slate-100 bg-slate-50 shrink-0">
                    <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide">N° Dossier</span>
                    <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide">Patient</span>
                    <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide">Âge</span>
                    <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide">Téléphone</span>
                    <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide">!</span>
                </div>

                <!-- Conteneur scrollable -->
                <div class="flex-1 overflow-y-auto min-h-0">

                <!-- ── Skeleton loader ─────────────────────────────── -->
                <template v-if="loading">
                    <!-- Mobile -->
                    <div v-for="i in 8" :key="`ms${i}`" class="lg:hidden flex items-center gap-3 px-4 py-3.5 border-b border-slate-50 animate-pulse">
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-slate-100 rounded w-3/4"></div>
                            <div class="h-3 bg-slate-100 rounded w-2/5"></div>
                        </div>
                        <div class="w-4 h-4 bg-slate-100 rounded-full shrink-0"></div>
                    </div>
                    <!-- Desktop -->
                    <div v-for="i in 8" :key="i" class="hidden lg:grid grid-cols-[6rem_1fr_5rem_7rem_2rem] gap-3 px-4 py-3 border-b border-slate-50 animate-pulse">
                        <div class="h-4 bg-slate-100 rounded"></div>
                        <div class="h-4 bg-slate-100 rounded"></div>
                        <div class="w-12 h-4 bg-slate-100 rounded"></div>
                        <div class="h-4 bg-slate-100 rounded"></div>
                        <div class="w-4 h-4 bg-slate-100 rounded-full"></div>
                    </div>
                </template>

                <!-- ── Liste des patients ─────────────────────────────── -->
                <template v-else>
                    <div
                        v-for="patient in patients"
                        :key="patient.id"
                        @click="openPatient(patient.id)"
                        class="flex items-center gap-3 px-4 py-3 border-b border-slate-50 cursor-pointer transition-colors hover:bg-slate-50"
                        :class="selected?.patient?.id === patient.id ? 'bg-blue-50 hover:bg-blue-50' : ''"
                    >
                        <!-- N° dossier (desktop) -->
                        <span class="hidden lg:block text-xs font-mono text-slate-400 w-24 shrink-0">{{ patient.numero_dossier }}</span>

                        <!-- Nom + sous-ligne -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ patient.full_name }}</p>
                            <!-- Mobile : téléphone + âge + réactiver -->
                            <div class="flex items-center gap-1.5 lg:hidden mt-0.5">
                                <span class="text-xs text-slate-400">{{ patient.phone }}</span>
                                <span class="text-slate-200 text-xs">·</span>
                                <span class="text-xs text-slate-400">{{ patient.age ? patient.age + ' ans' : '—' }}</span>
                                <template v-if="filters.archived">
                                    <span class="text-slate-200 text-xs">·</span>
                                    <button @click.stop="handleRestore(patient.id)"
                                        class="text-[10px] text-green-600 font-medium flex items-center gap-0.5">
                                        <RotateCcw class="w-2.5 h-2.5" /> Réactiver
                                    </button>
                                </template>
                            </div>
                            <!-- Desktop : couverture + réactiver -->
                            <div class="hidden lg:flex items-center gap-2 mt-0.5">
                                <p class="text-xs text-slate-400">{{ patient.couverture }}</p>
                                <button v-if="filters.archived" @click.stop="handleRestore(patient.id)"
                                    class="text-[10px] text-green-600 hover:text-green-700 font-medium hover:underline flex items-center gap-0.5">
                                    <RotateCcw class="w-2.5 h-2.5" /> Réactiver
                                </button>
                            </div>
                        </div>

                        <!-- Âge (desktop) -->
                        <span class="hidden lg:block text-sm text-slate-500 w-20 shrink-0 text-right">{{ patient.age ? patient.age + ' ans' : '—' }}</span>

                        <!-- Téléphone (desktop) -->
                        <span class="hidden lg:block text-sm text-slate-600 w-28 shrink-0">{{ patient.phone }}</span>

                        <!-- Alertes (toujours visible) -->
                        <div class="flex items-center justify-center w-5 shrink-0">
                            <AlertTriangle v-if="patient.has_critical_alerts" class="w-4 h-4 text-red-500" title="Alertes critiques"/>
                            <Bell v-else-if="patient.alerts_count > 0" class="w-4 h-4 text-amber-400" :title="`${patient.alerts_count} alerte(s)`"/>
                            <span v-else class="hidden lg:block text-slate-200 text-xs">—</span>
                        </div>

                        <!-- Chevron (mobile uniquement) -->
                        <ChevronRight class="lg:hidden w-4 h-4 text-slate-300 shrink-0"/>
                    </div>

                    <!-- État vide -->
                    <div v-if="patients.length === 0" class="flex flex-col items-center justify-center py-16 text-slate-400">
                        <span class="text-4xl mb-3">👤</span>
                        <p class="text-sm font-medium">Aucun patient trouvé</p>
                        <p v-if="filters.search" class="text-xs mt-1">Essayez avec d'autres mots-clés</p>
                    </div>
                    <!-- Espace pour la nav mobile fixe -->
                    <div class="h-16 lg:hidden" aria-hidden="true"></div>
                </template>

                </div><!-- fin conteneur scrollable -->
            </div>

            <!-- ── Pagination ─────────────────────────────────────────── -->
            <div v-if="meta.last_page > 1" class="flex items-center justify-between mt-3 px-1">
                <p class="text-xs text-slate-400">Page {{ meta.current_page }} / {{ meta.last_page }}</p>
                <div class="flex gap-1">
                    <button @click="filters.page--" :disabled="filters.page === 1"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                        ← Préc.
                    </button>
                    <button @click="filters.page++" :disabled="filters.page === meta.last_page"
                        class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                        Suiv. →
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
         COLONNE DROITE — Panneau détail patient
         ══════════════════════════════════════════════════════════ -->
        <Transition name="slide">
            <div
                v-if="selected || panelLoading"
                class="flex-1 bg-white rounded-xl border border-slate-200 overflow-y-auto"
            >
                <!-- Skeleton pendant l'ouverture d'un patient -->
                <div v-if="panelLoading" class="p-5 space-y-4 animate-pulse">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-slate-100 rounded-full shrink-0"></div>
                        <div class="space-y-2 flex-1">
                            <div class="w-40 h-4 bg-slate-100 rounded"></div>
                            <div class="w-24 h-3 bg-slate-100 rounded"></div>
                        </div>
                    </div>
                    <div class="h-px bg-slate-100"></div>
                    <div class="space-y-2">
                        <div class="w-32 h-3 bg-slate-100 rounded"></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="h-8 bg-slate-100 rounded"></div>
                            <div class="h-8 bg-slate-100 rounded"></div>
                            <div class="h-8 bg-slate-100 rounded"></div>
                            <div class="h-8 bg-slate-100 rounded"></div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="w-32 h-3 bg-slate-100 rounded"></div>
                        <div class="h-16 bg-slate-100 rounded"></div>
                    </div>
                </div>

                <PatientPanel
                    v-else-if="selected"
                    :data="selected"
                    :upload-document="uploadDocument"
                    :delete-document="deleteDocument"
                    :add-alert="addAlert"
                    :delete-alert="deleteAlert"
                    @close="closePanel"
                    @edit="openForm(selected.patient)"
                    @archive="handleArchive"
                />
            </div>
        </Transition>

        <!-- ══════════════════════════════════════════════════════════
         MODAL — Formulaire création / modification
         ══════════════════════════════════════════════════════════ -->
        <PatientFormModal
            v-if="showForm"
            :patient="editingPatient"
            @close="showForm = false"
            @saved="handleSaved"
        />

        <ConfirmModal
            :model-value="!!confirmArchiveId"
            @update:model-value="v => { if (!v) confirmArchiveId = null }"
            title="Archiver le patient"
            message="Ce patient sera archivé et n'apparaîtra plus dans les listes actives."
            confirm-label="Archiver"
            @confirm="doArchive" />

        <ConfirmModal
            :model-value="!!confirmRestoreId"
            @update:model-value="v => { if (!v) confirmRestoreId = null }"
            title="Réactiver le patient"
            message="Ce patient sera remis dans la liste des patients actifs."
            confirm-label="Réactiver"
            @confirm="doRestore" />
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import ConfirmModal from "../components/ui/ConfirmModal.vue";
import {
    Search,
    Plus,
    X,
    AlertTriangle,
    Bell,
    RotateCcw,
    ChevronRight,
} from "lucide-vue-next";
import { usePatients } from "../composables/usePatients";
import PatientPanel from "../components/patients/PatientPanel.vue";
import PatientFormModal from "../components/patients/PatientFormModal.vue";

const {
    patients,
    selected,
    loading,
    panelLoading,
    error,
    meta,
    filters,
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
} = usePatients();

const showForm      = ref(false);
const editingPatient = ref(null);

onMounted(() => fetchPatients());

function openForm(patient) {
    editingPatient.value = patient;
    showForm.value       = true;
}

async function handleSaved(data, isEdit) {
    try {
        if (isEdit) {
            await updatePatient(data.id, data);
        } else {
            await createPatient(data);
        }
        showForm.value = false;
    } catch (e) {
        error.value = e.message;
    }
}

const confirmArchiveId  = ref(null);
const confirmRestoreId  = ref(null);

function handleArchive(id) { confirmArchiveId.value = id; }
function handleRestore(id) { confirmRestoreId.value = id; }

async function doArchive() {
    await archivePatient(confirmArchiveId.value);
    confirmArchiveId.value = null;
}

async function doRestore() {
    await restorePatient(confirmRestoreId.value);
    confirmRestoreId.value = null;
}
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
