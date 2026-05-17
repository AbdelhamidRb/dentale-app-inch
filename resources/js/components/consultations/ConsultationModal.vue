<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="close"
            >
                <!-- Fond -->
                <div
                    class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                ></div>

                <!-- Contenu -->
                <div
                    class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden"
                >
                    <!-- ── Header ────────────────────────────────────────── -->
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-slate-100"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">
                                {{
                                    isEdit
                                        ? "Modifier la consultation"
                                        : "Nouvelle consultation"
                                }}
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{
                                    isEdit
                                        ? `Consultation #${form.id}`
                                        : "Remplissez les informations ci-dessous"
                                }}
                            </p>
                        </div>
                        <button
                            @click="close"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                        >
                            <svg
                                class="w-5 h-5"
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

                    <!-- ── Body ─────────────────────────────────────────── -->
                    <div class="flex flex-1 overflow-hidden">
                        <!-- Colonne gauche : infos + actes -->
                        <div
                            class="flex-1 overflow-y-auto px-6 py-5 space-y-5 border-r border-slate-100"
                        >
                            <!-- Patient + Statut -->
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Patient avec recherche -->
                                <div class="relative" ref="patientDropdownRef">
                                    <label
                                        class="block text-xs font-medium text-slate-500 mb-1.5"
                                        >Patient *</label
                                    >

                                    <!-- Input recherche -->
                                    <div class="relative">
                                        <input
                                            v-model="patientSearch"
                                            @focus="showPatientDropdown = true"
                                            @input="showPatientDropdown = true"
                                            :disabled="!!preselectedPatient"
                                            :placeholder="
                                                selectedPatientLabel ||
                                                'Rechercher un patient…'
                                            "
                                            :class="[
                                                'w-full border rounded-xl px-3 py-2.5 text-sm',
                                                'focus:outline-none focus:ring-2 focus:ring-blue-300',
                                                'disabled:bg-slate-50 disabled:text-slate-400',
                                                form.patient_id
                                                    ? 'border-blue-300 bg-blue-50'
                                                    : 'border-slate-200 bg-white',
                                            ]"
                                        />
                                        <button
                                            v-if="
                                                form.patient_id &&
                                                !preselectedPatient
                                            "
                                            @click.stop="clearPatient"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                        >
                                            ✕
                                        </button>
                                    </div>

                                    <!-- Dropdown résultats -->
                                    <div
                                        v-if="
                                            showPatientDropdown &&
                                            filteredPatients.length
                                        "
                                        class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"
                                    >
                                        <button
                                            v-for="p in filteredPatients"
                                            :key="p.id"
                                            @mousedown.prevent="
                                                selectPatient(p)
                                            "
                                            class="w-full text-left px-3 py-2.5 hover:bg-blue-50 transition-colors border-b border-slate-50 last:border-0"
                                        >
                                            <p
                                                class="text-sm font-medium text-slate-700"
                                            >
                                                {{
                                                    p.full_name ??
                                                    `${p.first_name} ${p.last_name}`
                                                }}
                                            </p>
                                            <p class="text-xs text-slate-400">
                                                {{ p.numero_dossier }}
                                            </p>
                                        </button>
                                    </div>

                                    <!-- Aucun résultat -->
                                    <div
                                        v-if="
                                            showPatientDropdown &&
                                            patientSearch &&
                                            !filteredPatients.length
                                        "
                                        class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg px-3 py-3 text-xs text-slate-400 text-center"
                                    >
                                        Aucun patient trouvé pour "{{
                                            patientSearch
                                        }}"
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-slate-500 mb-1.5"
                                        >Statut</label
                                    >
                                    <select
                                        v-model="form.status"
                                        class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300"
                                    >
                                        <option value="BROUILLON">
                                            Brouillon
                                        </option>
                                        <option value="EN_COURS">
                                            En cours
                                        </option>
                                        <option value="TERMINE">
                                            Terminée
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Lien RDV (optionnel) -->
                            <div v-if="!appointmentPatient">
                                <label
                                    class="block text-xs font-medium text-slate-500 mb-1.5"
                                >
                                    Lier à un rendez-vous
                                    <span class="text-slate-300 font-normal"
                                        >(optionnel)</span
                                    >
                                </label>
                                <select
                                    v-model="form.appointment_id"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300"
                                >
                                    <option :value="null">
                                        Sans rendez-vous
                                    </option>
                                    <option
                                        v-for="a in filteredAppointments"
                                        :key="a.id"
                                        :value="a.id"
                                    >
                                        {{ a.scheduled_date }}
                                        {{ a.start_time?.slice(0, 5) }} →
                                        {{ a.end_time?.slice(0, 5) }}
                                    </option>
                                </select>
                            </div>

                            <!-- Notes globales -->
                            <div>
                                <label
                                    class="block text-xs font-medium text-slate-500 mb-1.5"
                                    >Notes cliniques</label
                                >
                                <textarea
                                    v-model="form.notes"
                                    placeholder="Observations générales sur cette consultation…"
                                    rows="3"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-blue-300 placeholder:text-slate-300"
                                ></textarea>
                            </div>

                            <!-- ── Section Actes ───────────────────────── -->
                            <div>
                                <div
                                    class="flex items-center justify-between mb-3"
                                >
                                    <h3
                                        class="text-sm font-semibold text-slate-700"
                                    >
                                        Interventions
                                    </h3>
                                    <span class="text-xs text-slate-400">
                                        Total :
                                        <strong class="text-blue-700"
                                            >{{ totalFormatted }} MAD</strong
                                        >
                                    </span>
                                </div>

                                <!-- Ajout d'un acte -->
                                <div
                                    class="p-4 bg-blue-50 border border-blue-200 rounded-xl mb-4"
                                >
                                    <p
                                        class="text-xs font-medium text-blue-700 mb-3"
                                    >
                                        Ajouter une intervention
                                    </p>
                                    <div class="flex gap-2 mb-3">
                                        <select
                                            v-model="newAct.catalog_act_id"
                                            @change="onActSelected"
                                            class="flex-1 border border-blue-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300"
                                        >
                                            <option value="">
                                                Choisir un acte du catalogue…
                                            </option>
                                            <option
                                                v-for="ca in catalogActs"
                                                :key="ca.id"
                                                :value="ca.id"
                                            >
                                                {{ ca.code }} —
                                                {{ ca.name }} ({{
                                                    ca.base_price
                                                }}
                                                MAD)
                                            </option>
                                        </select>

                                        <!-- Prix par dent (éditable) -->
                                        <div
                                            class="flex flex-col gap-1 shrink-0"
                                        >
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <input
                                                    type="number"
                                                    v-model.number="
                                                        newAct.pricePerTooth
                                                    "
                                                    min="0"
                                                    placeholder="0"
                                                    class="w-24 border border-blue-200 rounded-lg px-2 py-2 text-sm text-slate-700 bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-300"
                                                />
                                                <span
                                                    class="text-xs text-slate-400"
                                                    >MAD</span
                                                >
                                            </div>
                                            <span
                                                class="text-[10px] text-blue-500 text-right"
                                            >
                                                par dent
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Total calculé -->
                                    <div
                                        v-if="newAct.catalog_act_id"
                                        class="flex items-center justify-between px-3 py-2 bg-white border border-blue-200 rounded-lg mb-3"
                                    >
                                        <span class="text-xs text-slate-500">
                                            {{ newAct.pricePerTooth }} MAD ×
                                            {{ newAct.teeth.length || 1 }}
                                            dent(s)
                                        </span>
                                        <span
                                            class="text-sm font-bold text-blue-700"
                                        >
                                            = {{ newActTotal }} MAD
                                        </span>
                                    </div>

                                    <!-- Dents sélectionnées pour ce nouvel acte -->
                                    <div
                                        v-if="newAct.catalog_act_id"
                                        class="mb-3"
                                    >
                                        <p class="text-xs text-blue-600 mb-2">
                                            Sélectionnez les dents concernées
                                            <span class="text-blue-400"
                                                >(optionnel — laissez vide pour
                                                acte global)</span
                                            >
                                        </p>
                                        <div
                                            class="flex flex-wrap gap-1.5 mb-2"
                                            v-if="newAct.teeth.length"
                                        >
                                            <span
                                                v-for="t in newAct.teeth"
                                                :key="t"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-200 text-blue-800 text-xs font-semibold"
                                            >
                                                🦷 {{ t }}
                                                <button
                                                    @click="removeTooth(t)"
                                                    class="text-blue-500 hover:text-blue-800 ml-0.5"
                                                >
                                                    ×
                                                </button>
                                            </span>
                                        </div>
                                        <p
                                            class="text-xs text-slate-400 italic"
                                            v-else
                                        >
                                            Aucune dent sélectionnée — cliquez
                                            sur le schéma →
                                        </p>
                                    </div>

                                    <button
                                        @click="addAct"
                                        :disabled="
                                            !newAct.catalog_act_id ||
                                            newAct.price < 0
                                        "
                                        class="w-full py-2 rounded-lg text-sm font-medium transition-colors bg-blue-600 text-white hover:bg-blue-700 disabled:bg-blue-200 disabled:text-blue-400 disabled:cursor-not-allowed"
                                    >
                                        + Ajouter cette intervention
                                    </button>
                                </div>

                                <!-- Liste des actes ajoutés -->
                                <div v-if="form.acts.length" class="space-y-3">
                                    <ConsultationActRow
                                        v-for="(act, i) in form.acts"
                                        :key="i"
                                        :act="act"
                                        :editable="true"
                                        @remove="form.acts.splice(i, 1)"
                                        @update:price="
                                            form.acts[i].price = $event
                                        "
                                        @update:notes="
                                            form.acts[i].notes = $event
                                        "
                                    />
                                </div>
                                <p
                                    v-else
                                    class="text-center text-xs text-slate-400 py-4"
                                >
                                    Aucune intervention ajoutée
                                </p>
                            </div>
                        </div>

                        <!-- Colonne droite : schéma dentaire -->
                        <div
                            class="w-72 shrink-0 overflow-y-auto p-4 bg-slate-50"
                        >
                            <p class="text-xs font-medium text-slate-500 mb-3">
                                Schéma dentaire
                                <span
                                    v-if="newAct.catalog_act_id"
                                    class="text-blue-600"
                                >
                                    — cliquez pour sélectionner
                                </span>
                            </p>
                            <ToothChart
                                mode="select"
                                v-model:selectedTeeth="newAct.teeth"
                                @tooth-clicked="recalcPrice"
                            />
                        </div>
                    </div>

                    <!-- ── Footer ────────────────────────────────────────── -->
                    <div
                        class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-white"
                    >
                        <!-- Total -->
                        <div class="text-sm text-slate-500">
                            <span>{{ form.acts.length }} intervention(s)</span>
                            <span class="mx-2 text-slate-200">|</span>
                            <span class="font-semibold text-slate-800">
                                Total : {{ totalFormatted }} MAD
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3">
                            <button
                                @click="close"
                                class="px-4 py-2 text-sm text-slate-500 hover:text-slate-700 rounded-xl hover:bg-slate-100 transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                @click="submit"
                                :disabled="!form.patient_id || loading"
                                class="px-5 py-2 text-sm font-medium rounded-xl transition-colors bg-blue-600 text-white hover:bg-blue-700 disabled:bg-blue-200 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg
                                    v-if="loading"
                                    class="w-4 h-4 animate-spin"
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
                                    isEdit
                                        ? "Enregistrer"
                                        : "Créer la consultation"
                                }}
                            </button>
                        </div>
                    </div>

                    <!-- Erreur -->
                    <div
                        v-if="error"
                        class="mx-6 mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600"
                    >
                        {{ error }}
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
// APRÈS
import ConsultationActRow from "./ConsultationActRow.vue"; // ✅ même dossier
import ToothChart from "./ToothChart.vue"; // ✅ même dossier
import { useConsultations } from "../../composables/useConsultations"; // ⬆️ 2 niveaux

// ─── Props ────────────────────────────────────────────────────────
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    // Si ouvert depuis AppointmentPanel, on pré-remplit patient + rdv
    appointmentPatient: { type: Object, default: null },
    appointmentId: { type: Number, default: null },
    // Si édition
    consultationToEdit: { type: Object, default: null },
    // Données nécessaires
    patients: { type: Array, default: () => [] },
    catalogActs: { type: Array, default: () => [] },
    availableAppointments: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:modelValue", "saved"]);

// ─── RDV filtrés par patient ──────────────────────────────────────
const filteredAppointments = ref([]);

function filterAppointmentsForPatient(patientId) {
    if (!patientId) {
        filteredAppointments.value = [];
        return;
    }
    filteredAppointments.value = props.availableAppointments.filter(
        (a) =>
            String(a.patient?.id) === String(patientId) ||
            String(a.patient_id) === String(patientId),
    );
}

const { createConsultation, updateConsultation } = useConsultations();

// ─── État du formulaire ───────────────────────────────────────────
const loading = ref(false);
const error = ref(null);

// ─── Recherche patient ────────────────────────────────────────────
const patientSearch = ref("");
const showPatientDropdown = ref(false);
const patientDropdownRef = ref(null);

const filteredPatients = computed(() => {
    const q = patientSearch.value.toLowerCase().trim();
    if (!q) return props.patients.slice(0, 20); // affiche les 20 premiers par défaut
    return props.patients
        .filter((p) => {
            const name = (
                p.full_name ?? `${p.first_name} ${p.last_name}`
            ).toLowerCase();
            const num = (p.numero_dossier ?? "").toLowerCase();
            return name.includes(q) || num.includes(q);
        })
        .slice(0, 20);
});

const selectedPatientLabel = computed(() => {
    if (!form.value.patient_id) return "";
    const found = props.patients.find((p) => p.id === form.value.patient_id);
    return found
        ? (found.full_name ?? `${found.first_name} ${found.last_name}`)
        : "";
});

function selectPatient(p) {
    form.value.patient_id = p.id;
    patientSearch.value = "";
    showPatientDropdown.value = false;
    // Filtre les RDV pour ce patient
    filterAppointmentsForPatient(p.id);
}

function clearPatient() {
    form.value.patient_id = "";
    form.value.appointment_id = null;
    patientSearch.value = "";
    filteredAppointments.value = [];
}

// Ferme le dropdown en cliquant ailleurs
function handleClickOutside(e) {
    if (
        patientDropdownRef.value &&
        !patientDropdownRef.value.contains(e.target)
    ) {
        showPatientDropdown.value = false;
    }
}

onMounted(() => document.addEventListener("click", handleClickOutside));
onUnmounted(() => document.removeEventListener("click", handleClickOutside));

const defaultForm = () => ({
    id: null,
    patient_id: props.appointmentPatient?.id ?? "",
    appointment_id: props.appointmentId ?? null,
    status: "BROUILLON",
    notes: "",
    acts: [],
});

const form = ref(defaultForm());

// Acte en cours d'ajout
const newAct = ref({
    catalog_act_id: "",
    pricePerTooth: 0, // prix par dent — éditable par le dentiste
    basePrice: 0, // prix catalogue d'origine — référence
    teeth: [],
    notes: "",
});

// Total calculé automatiquement = pricePerTooth × nombre de dents
const newActTotal = computed(
    () => newAct.value.pricePerTooth * Math.max(1, newAct.value.teeth.length),
);

// ─── Mode édition : pré-remplit le formulaire ─────────────────────
const isEdit = computed(() => !!props.consultationToEdit);

watch(
    () => props.consultationToEdit,
    (c) => {
        if (!c) {
            form.value = defaultForm();
            return;
        }
        form.value = {
            id: c.id,
            patient_id: c.patient?.id ?? "",
            appointment_id: c.appointment_id,
            status: c.status,
            notes: c.notes ?? "",
            acts: (c.acts ?? []).map((a) => ({
                catalog_act_id: a.catalog_act?.id,
                catalogActName: a.catalog_act?.name,
                catalogActCode: a.catalog_act?.code,
                catalog_act: a.catalog_act,
                teeth: a.teeth ?? [],
                price: a.price,
                notes: a.notes ?? "",
            })),
        };
    },
    { immediate: true },
);

watch(
    () => props.modelValue,
    (isOpen) => {
        if (!isOpen) {
            error.value = null;
        } else if (!isEdit.value) {
            // Force la réinitialisation si c'est une nouvelle consultation
            form.value = defaultForm();
            newAct.value = {
                catalog_act_id: "",
                price: 0,
                basePrice: 0,
                teeth: [],
                notes: "",
            };
        }
    },
);

// ─── Total calculé ────────────────────────────────────────────────
const totalFormatted = computed(() =>
    form.value.acts.reduce((s, a) => s + Number(a.price || 0), 0).toFixed(0),
);

// ─── Sélection d'acte : pré-remplit le prix depuis le catalogue ───
function onActSelected() {
    const found = props.catalogActs.find(
        (ca) => String(ca.id) === String(newAct.value.catalog_act_id),
    );
    if (found) {
        newAct.value.basePrice = Number(found.base_price ?? 0);
        newAct.value.pricePerTooth = Number(found.base_price ?? 0);
    }
}

function removeTooth(tooth) {
    newAct.value.teeth = newAct.value.teeth.filter((t) => t !== tooth);
    recalcPrice();
}

// Plus besoin de recalcPrice — le total est géré par newActTotal (computed)
// On garde la fonction vide pour ne pas casser le @tooth-clicked
function recalcPrice() {}

// ─── Ajouter l'acte à la liste ────────────────────────────────────
function addAct() {
    if (!newAct.value.catalog_act_id) return;
    const found = props.catalogActs.find(
        (ca) => String(ca.id) === String(newAct.value.catalog_act_id),
    );
    form.value.acts.push({
        catalog_act_id: newAct.value.catalog_act_id,
        catalog_act: found,
        catalogActName: found?.name,
        catalogActCode: found?.code,
        teeth: [...newAct.value.teeth],
        price: newActTotal.value, // ← total = pricePerTooth × dents
        notes: newAct.value.notes,
    });
    // Reset
    newAct.value = {
        catalog_act_id: "",
        price: 0,
        pricePerTooth: 0,
        basePrice: 0,
        teeth: [],
        notes: "",
    };
}

// ─── Soumettre ───────────────────────────────────────────────────
async function submit() {
    if (!form.value.patient_id) return;
    loading.value = true;
    error.value = null;
    try {
        const payload = {
            patient_id: form.value.patient_id,
            appointment_id: form.value.appointment_id,
            status: form.value.status,
            notes: form.value.notes,
            acts: form.value.acts.map((a) => ({
                catalog_act_id: a.catalog_act_id ?? a.catalog_act?.id,
                teeth: a.teeth,
                price: a.price,
                notes: a.notes,
            })),
        };
        const saved = isEdit.value
            ? await updateConsultation(form.value.id, payload)
            : await createConsultation(payload);

        emit("saved", saved);
        close();
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

function close() {
    emit("update:modelValue", false);
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
    transform: scale(0.97);
}
</style>
