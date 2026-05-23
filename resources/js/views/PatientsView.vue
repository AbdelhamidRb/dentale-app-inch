<template>
    <div class="flex h-full gap-5">
        <!-- ══════════════════════════════════════════════════════════
         COLONNE GAUCHE — Liste des patients
         ══════════════════════════════════════════════════════════ -->
        <div
            class="flex flex-col min-w-0 transition-all duration-300"
            :class="selected || panelLoading ? 'w-[420px] shrink-0' : 'flex-1'"
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
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <Plus class="w-4 h-4" />
                    Nouveau patient
                </button>
            </div>

            <!-- ── Barre de recherche + filtres ─────────────────────── -->
            <div class="flex gap-2 mb-3">
                <div class="relative flex-1">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                    />
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Nom, téléphone, numéro dossier..."
                        class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <button
                        v-if="filters.search"
                        @click="filters.search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Filtre statut -->
                <select
                    v-model="filters.status"
                    class="px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                >
                    <option value="">Tous</option>
                    <option value="ACTIF">Actifs</option>
                    <option value="INACTIF">Inactifs</option>
                </select>

                <!-- Toggle archivés -->
                <button
                    @click="filters.archived = !filters.archived"
                    :class="
                        filters.archived
                            ? 'bg-amber-100 text-amber-700 border-amber-300'
                            : 'bg-white text-slate-600 border-slate-300'
                    "
                    class="px-3 py-2 border rounded-lg text-sm transition-colors"
                >
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
                <!-- En-tête du tableau -->
                <div
                    class="grid grid-cols-[auto_1fr_auto_auto_auto] gap-3 px-4 py-2.5 border-b border-slate-100 bg-slate-50"
                >
                    <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide">N° Dossier</span>
                    <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide">Patient</span>
                    <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide">Âge</span>
                    <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide">Téléphone</span>
                    <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide">Alertes</span>
                </div>

                <!-- ── Skeleton loader pendant le chargement ─────────── -->
                <template v-if="loading">
                    <div
                        v-for="i in 8"
                        :key="i"
                        class="grid grid-cols-[auto_1fr_auto_auto_auto] gap-3 px-4 py-3 border-b border-slate-50 animate-pulse"
                    >
                        <div class="w-20 h-4 bg-slate-100 rounded"></div>
                        <div class="h-4 bg-slate-100 rounded"></div>
                        <div class="w-8 h-4 bg-slate-100 rounded"></div>
                        <div class="w-24 h-4 bg-slate-100 rounded"></div>
                        <div class="w-6 h-4 bg-slate-100 rounded"></div>
                    </div>
                </template>

                <!-- ── Liste des patients ─────────────────────────────── -->
                <template v-else>
                    <div
                        v-for="patient in patients"
                        :key="patient.id"
                        @click="openPatient(patient.id)"
                        class="grid grid-cols-[auto_1fr_auto_auto_auto] gap-3 px-4 py-3 border-b border-slate-50 cursor-pointer transition-colors hover:bg-slate-50"
                        :class="
                            selected?.patient?.id === patient.id
                                ? 'bg-blue-50 hover:bg-blue-50'
                                : ''
                        "
                    >
                        <!-- Numéro dossier -->
                        <span class="text-xs font-mono text-slate-500">{{
                            patient.numero_dossier
                        }}</span>

                        <!-- Nom + badge statut + bouton réactiver intégré -->
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">
                                {{ patient.full_name }}
                            </p>
                            <div class="flex items-center gap-2">
                                <p class="text-xs text-slate-400">
                                    {{ patient.couverture }}
                                </p>
                                <button
                                    v-if="filters.archived"
                                    @click.stop="handleRestore(patient.id)"
                                    class="text-[10px] text-green-600 hover:text-green-700 font-medium hover:underline flex items-center gap-0.5"
                                >
                                    <RotateCcw class="w-2.5 h-2.5" />
                                    Réactiver
                                </button>
                            </div>
                        </div>

                        <!-- Âge -->
                        <span class="text-sm text-slate-600 text-right">
                            {{ patient.age ? patient.age + " ans" : "—" }}
                        </span>

                        <!-- Téléphone -->
                        <span class="text-sm text-slate-600">{{
                            patient.phone
                        }}</span>

                        <!-- Alerte critique -->
                        <div class="flex items-center justify-center">
                            <AlertTriangle
                                v-if="patient.has_critical_alerts"
                                class="w-4 h-4 text-red-500"
                                title="Alertes critiques"
                            />
                            <Bell
                                v-else-if="patient.alerts_count > 0"
                                class="w-4 h-4 text-amber-400"
                                :title="`${patient.alerts_count} alerte(s) médicale(s)`"
                            />
                            <span v-else class="text-slate-300 text-xs">—</span>
                        </div>
                    </div>

                    <!-- État vide -->
                    <div
                        v-if="patients.length === 0"
                        class="flex-1 flex flex-col items-center justify-center py-16 text-slate-400"
                    >
                        <span class="text-4xl mb-3">👤</span>
                        <p class="text-sm font-medium">Aucun patient trouvé</p>
                        <p v-if="filters.search" class="text-xs mt-1">
                            Essayez avec d'autres mots-clés
                        </p>
                    </div>
                </template>
            </div>

            <!-- ── Pagination ─────────────────────────────────────────── -->
            <div
                v-if="meta.last_page > 1"
                class="flex items-center justify-between mt-3"
            >
                <p class="text-xs text-slate-400">
                    Page {{ meta.current_page }} sur {{ meta.last_page }}
                </p>
                <div class="flex gap-1">
                    <button
                        @click="filters.page--"
                        :disabled="filters.page === 1"
                        class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg disabled:opacity-40 hover:bg-slate-50 transition-colors"
                    >
                        ← Précédent
                    </button>
                    <button
                        @click="filters.page++"
                        :disabled="filters.page === meta.last_page"
                        class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg disabled:opacity-40 hover:bg-slate-50 transition-colors"
                    >
                        Suivant →
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
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import {
    Search,
    Plus,
    X,
    AlertTriangle,
    Bell,
    RotateCcw,
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

async function handleArchive(id) {
    if (!confirm("Archiver ce patient ?")) return;
    await archivePatient(id);
}

async function handleRestore(id) {
    if (!confirm("Réactiver ce patient ?")) return;
    await restorePatient(id);
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
