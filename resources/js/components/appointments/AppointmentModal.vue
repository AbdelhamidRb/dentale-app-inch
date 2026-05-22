<template>
    <div
        class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
    >
        <div
            class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
        >
            <!-- En-tête -->
            <div
                class="flex items-center justify-between p-5 border-b border-slate-100"
            >
                <h2 class="font-semibold text-slate-800">
                    {{
                        isEdit
                            ? "Modifier le rendez-vous"
                            : "Nouveau rendez-vous"
                    }}
                </h2>
                <button
                    @click="$emit('close')"
                    class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-400 transition-colors"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
                <!-- Recherche patient -->
                <div ref="patientDropdownRef" class="relative">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">
                        Patient <span class="text-red-400">*</span>
                    </label>

                    <!-- Patient déjà sélectionné -->
                    <div
                        v-if="selectedPatient"
                        class="flex items-center gap-2 px-3 py-2.5 bg-blue-50 border border-blue-200 rounded-lg"
                    >
                        <UserCheck class="w-4 h-4 text-blue-600 shrink-0" />
                        <span class="text-sm font-medium text-blue-800 flex-1">
                            {{ selectedPatient.full_name }}
                        </span>
                        <button
                            type="button"
                            @click="clearPatient"
                            class="text-blue-400 hover:text-blue-600 transition-colors"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Champ de recherche (masqué si patient sélectionné) -->
                    <div v-else class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                        <input
                            v-model="patientSearch"
                            type="text"
                            placeholder="Tapez un nom ou numéro de dossier..."
                            @input="searchPatients"
                            @focus="searchPatients"
                            autocomplete="off"
                            class="w-full pl-9 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />

                        <!-- Dropdown résultats (absolu, ne déplace pas le contenu) -->
                        <div
                            v-if="dropdownOpen"
                            class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg z-50 max-h-52 overflow-y-auto"
                        >
                            <!-- Chargement -->
                            <div v-if="searchLoading" class="px-3 py-3 text-xs text-slate-400 text-center">
                                Recherche...
                            </div>
                            <!-- Aucun résultat -->
                            <div v-else-if="patientResults.length === 0" class="px-3 py-3 text-xs text-slate-400 text-center">
                                Aucun patient trouvé
                            </div>
                            <!-- Résultats -->
                            <div
                                v-else
                                v-for="p in patientResults"
                                :key="p.id"
                                @mousedown.prevent="selectPatient(p)"
                                class="px-3 py-2.5 hover:bg-blue-50 cursor-pointer border-b border-slate-100 last:border-0 transition-colors"
                            >
                                <p class="text-sm font-medium text-slate-800">{{ p.full_name }}</p>
                                <p class="text-xs text-slate-400">{{ p.phone }} · {{ p.numero_dossier }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Heure début + fin -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="block text-xs font-medium text-slate-600 mb-1.5"
                        >
                            Heure de début <span class="text-red-400">*</span>
                        </label>
                        <input
                            v-model="form.start_time"
                            type="time"
                            required
                            @change="autoEndTime"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-slate-600 mb-1.5"
                        >
                            Heure de fin <span class="text-red-400">*</span>
                        </label>
                        <input
                            v-model="form.end_time"
                            type="time"
                            required
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 -mt-2">
                    Durée par défaut : 30 minutes. Modifiable si besoin.
                </p>

                <!-- Sélection des actes -->
                <div>
                    <label
                        class="block text-xs font-medium text-slate-600 mb-1.5"
                    >
                        Actes prévus
                    </label>
                    <div
                        class="border border-slate-200 rounded-lg max-h-40 overflow-y-auto"
                    >
                        <div
                            v-for="act in catalogActs"
                            :key="act.id"
                            @click="toggleAct(act.id)"
                            class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0 transition-colors"
                            :class="
                                form.act_ids.includes(act.id)
                                    ? 'bg-blue-50'
                                    : ''
                            "
                        >
                            <!-- Checkbox visuel -->
                            <div
                                class="w-4 h-4 rounded border shrink-0 flex items-center justify-center transition-colors"
                                :class="
                                    form.act_ids.includes(act.id)
                                        ? 'bg-blue-600 border-blue-600'
                                        : 'border-slate-300'
                                "
                            >
                                <Check
                                    v-if="form.act_ids.includes(act.id)"
                                    class="w-3 h-3 text-white"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-slate-700">
                                    {{ act.name }}
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{ act.base_price }} MAD
                                </p>
                            </div>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">
                        {{ form.act_ids.length }} acte(s) sélectionné(s)
                    </p>
                </div>

                <!-- Notes -->
                <div>
                    <label
                        class="block text-xs font-medium text-slate-600 mb-1.5"
                        >Notes</label
                    >
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        placeholder="Remarques optionnelles..."
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                    ></textarea>
                </div>

                <!-- Erreur locale (validation) ou externe (API Laravel) -->
                <div
                    v-if="error || props.externalError"
                    class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm flex items-center gap-2"
                >
                    <AlertCircle class="w-4 h-4 shrink-0" />
                    {{ error || props.externalError }}
                </div>

                <!-- Boutons -->
                <div class="flex gap-2 pt-1">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="flex-1 py-2.5 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        :disabled="loading || !selectedPatient"
                        class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
                    >
                        <svg
                            v-if="loading"
                            class="animate-spin w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                            />
                        </svg>
                        {{
                            loading
                                ? "Enregistrement..."
                                : isEdit
                                  ? "Modifier"
                                  : "Créer le RDV"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { X, Search, Check, UserCheck, AlertCircle } from "lucide-vue-next";

const props = defineProps({
    slotTime: { type: String, default: null },
    appointment: { type: Object, default: null },
    catalogActs: { type: Array, default: () => [] },
    selectedDate: { type: String, required: true },
    externalError: { type: String, default: null },
});
const emit = defineEmits(["close", "saved"]);

const isEdit = computed(() => !!props.appointment);
const loading = ref(false);
const error = ref(null);

// ─── Recherche patient ────────────────────────────────────────────
const patientSearch = ref("");
const patientResults = ref([]);
const selectedPatient = ref(null);
const searchLoading = ref(false);
const dropdownOpen = ref(false);
const patientDropdownRef = ref(null);

let searchTimer = null;
async function searchPatients() {
    dropdownOpen.value = true;
    if (patientSearch.value.length < 1) {
        patientResults.value = [];
        searchLoading.value = false;
        return;
    }
    clearTimeout(searchTimer);
    searchLoading.value = true;
    searchTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/api/patients?search=${encodeURIComponent(patientSearch.value)}`, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                    Accept: "application/json",
                },
            });
            const data = await res.json();
            patientResults.value = data.data || [];
        } finally {
            searchLoading.value = false;
        }
    }, 250);
}

function selectPatient(p) {
    selectedPatient.value = p;
    patientSearch.value = "";
    patientResults.value = [];
    dropdownOpen.value = false;
}

function clearPatient() {
    selectedPatient.value = null;
    patientSearch.value = "";
    patientResults.value = [];
    dropdownOpen.value = false;
}

// Ferme le dropdown si clic en dehors
function onClickOutside(e) {
    if (patientDropdownRef.value && !patientDropdownRef.value.contains(e.target)) {
        dropdownOpen.value = false;
    }
}
onMounted(() => document.addEventListener("mousedown", onClickOutside));
onBeforeUnmount(() => document.removeEventListener("mousedown", onClickOutside));

// ─── Formulaire ───────────────────────────────────────────────────
const form = reactive({
    start_time: "",
    end_time: "",
    act_ids: [],
    notes: "",
    status: "PLANIFIE",
});

// Pré-remplit si créneau cliqué ou modification
watch(
    () => props.slotTime,
    (slot) => {
        if (slot) {
            form.start_time = slot;
            autoEndTime();
        }
    },
    { immediate: true },
);

watch(
    () => props.appointment,
    (appt) => {
        if (appt) {
            form.start_time = appt.start_time?.slice(0, 5);
            form.end_time = appt.end_time?.slice(0, 5);
            form.act_ids = (appt.acts ?? []).map((a) => a.id);
            form.notes = appt.notes || "";
            selectedPatient.value = appt.patient;
        }
    },
    { immediate: true },
);

// ─── Calcul automatique heure de fin (+30 min) ────────────────────
function autoEndTime() {
    if (!form.start_time) return;
    const [h, m] = form.start_time.split(":").map(Number);
    const end = new Date(2000, 0, 1, h, m + 30);
    form.end_time = `${String(end.getHours()).padStart(2, "0")}:${String(end.getMinutes()).padStart(2, "0")}`;
}

// ─── Toggle sélection d'un acte ───────────────────────────────────
function toggleAct(id) {
    const idx = form.act_ids.indexOf(id);
    if (idx === -1) form.act_ids.push(id);
    else form.act_ids.splice(idx, 1);
}

// ─── Soumission ───────────────────────────────────────────────────
async function handleSubmit() {
    error.value = null;

    // ─── Validations côté client ──────────────────────────────────
    if (!selectedPatient.value) {
        error.value = "Veuillez sélectionner un patient.";
        return;
    }

    if (!form.start_time || !form.end_time) {
        error.value = "Les heures de début et de fin sont obligatoires.";
        return;
    }

    // Vérifie que fin > début
    const [sh, sm] = form.start_time.split(":").map(Number);
    const [eh, em] = form.end_time.split(":").map(Number);
    const startMin = sh * 60 + sm;
    const endMin = eh * 60 + em;

    if (endMin <= startMin) {
        error.value = "L'heure de fin doit être après l'heure de début.";
        return;
    }

    if (endMin - startMin < 15) {
        error.value = "La durée minimale est de 15 minutes.";
        return;
    }

    loading.value = true;

    try {
        const data = {
            patient_id: selectedPatient.value.id,
            scheduled_date: props.selectedDate, // ← ajoute la date depuis les props
            start_time: form.start_time,
            end_time: form.end_time,
            act_ids: form.act_ids,
            notes: form.notes,
            status: form.status,
        };
        if (isEdit.value) data.id = props.appointment.id;
        emit("saved", data, isEdit.value);
    } catch (e) {
        // Erreurs backend (conflit de créneau, etc.)
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}
</script>
