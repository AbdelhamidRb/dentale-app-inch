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
                    class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[95vh] flex flex-col overflow-hidden"
                >
                    <!-- ── Header ────────────────────────────────────────── -->
                    <div
                        class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-100 shrink-0"
                    >
                        <div>
                            <h2 class="text-sm sm:text-lg font-semibold text-slate-800">
                                {{ isEdit ? "Modifier" : "Nouvelle consultation" }}
                            </h2>
                            <p class="text-xs text-slate-400 hidden sm:block mt-0.5">
                                {{ isEdit ? `Consultation #${form.id}` : "Remplissez les informations ci-dessous" }}
                            </p>
                        </div>
                        <button
                            @click="close"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- ── Body ─────────────────────────────────────────── -->
                    <div class="flex flex-col sm:flex-row flex-1 min-h-0 overflow-hidden">
                        <!-- Colonne gauche : infos + actes -->
                        <div
                            class="flex-1 overflow-y-auto px-3 sm:px-6 py-3 sm:py-5 space-y-3 sm:space-y-5 sm:border-r border-slate-100"
                        >
                            <!-- Patient + Statut -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
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
                                                'w-full border rounded-xl px-3 py-2 sm:py-2.5 text-sm',
                                                'focus:outline-none focus:ring-2',
                                                'disabled:bg-slate-50 disabled:text-slate-400',
                                                submitAttempted && !form.patient_id
                                                    ? 'border-red-400 bg-white focus:ring-red-300'
                                                    : form.patient_id
                                                        ? 'border-blue-300 bg-blue-50 focus:ring-blue-300'
                                                        : 'border-slate-200 bg-white focus:ring-blue-300',
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

                                <!-- Statut (2e colonne du grid) -->
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1.5">Statut</label>
                                    <select v-model="form.status"
                                            class="w-full border border-slate-200 rounded-xl px-3 py-2 sm:py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300">
                                        <option value="EN_COURS">En cours</option>
                                        <option value="TERMINE">Terminée</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Date de consultation -->
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1.5">Date</label>
                                <input
                                    v-model="form.date"
                                    type="date"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 sm:py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300"
                                />
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
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 sm:py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300"
                                >
                                    <option :value="null">
                                        Sans rendez-vous
                                    </option>
                                    <option
                                        v-for="a in filteredAppointments"
                                        :key="a.id"
                                        :value="a.id"
                                    >
                                        {{ appointmentLabel(a) }}
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
                                    <!-- Ligne 1 : select acte (pleine largeur) -->
                                    <div class="mb-2">
                                        <select
                                            v-model="newAct.catalog_act_id"
                                            @change="onActSelected"
                                            class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300"
                                        >
                                            <option value="">Choisir un acte…</option>
                                            <option
                                                v-for="ca in catalogActs"
                                                :key="ca.id"
                                                :value="ca.id"
                                            >
                                                {{ ca.code }} — {{ ca.name }} ({{ ca.base_price }} MAD)
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Ligne 2 : prix par dent -->
                                    <div class="flex items-center gap-2 mb-3">
                                        <label class="text-xs text-blue-600 shrink-0">Prix / dent</label>
                                        <input
                                            type="number"
                                            v-model.number="newAct.pricePerTooth"
                                            min="0"
                                            placeholder="0"
                                            class="flex-1 border border-blue-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white text-right focus:outline-none focus:ring-2 focus:ring-blue-300"
                                        />
                                        <span class="text-xs text-slate-400 shrink-0">MAD</span>
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
                                        class="w-full py-2 rounded-lg text-sm font-medium transition-colors bg-blue-600 text-white hover:bg-blue-700 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
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
                        <div class="sm:w-72 shrink-0 bg-slate-50 border-t sm:border-t-0 sm:border-l border-slate-100">
                            <!-- Toggle visible uniquement sur mobile -->
                            <button
                                @click="showChartPanel = !showChartPanel"
                                class="sm:hidden w-full flex items-center justify-between px-4 py-2.5 text-xs font-medium text-slate-600"
                            >
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                                    </svg>
                                    Schéma dentaire
                                    <span v-if="newAct.catalog_act_id" class="text-blue-600">— cliquez pour sélectionner</span>
                                </span>
                                <svg :class="['w-3.5 h-3.5 text-slate-400 transition-transform', showChartPanel ? 'rotate-180' : '']"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div :class="['overflow-y-auto p-4', showChartPanel ? 'h-56' : 'hidden', 'sm:block sm:h-full']">
                                <p class="hidden sm:block text-xs font-medium text-slate-500 mb-3">
                                    Schéma dentaire
                                    <span v-if="newAct.catalog_act_id" class="text-blue-600">— cliquez pour sélectionner</span>
                                </p>
                                <ToothChart
                                    mode="select"
                                    v-model:selectedTeeth="newAct.teeth"
                                    @tooth-clicked="recalcPrice"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ── Footer ────────────────────────────────────────── -->
                    <div class="shrink-0 border-t border-slate-100 bg-white">
                        <!-- Erreur -->
                        <div v-if="error" class="mx-4 mt-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600">
                            {{ error }}
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 sm:px-6 py-3 sm:py-4">
                            <!-- Total (hidden on very small screens) -->
                            <div class="hidden sm:block text-sm text-slate-500">
                                <span>{{ form.acts.length }} intervention(s)</span>
                                <span class="mx-2 text-slate-200">|</span>
                                <span class="font-semibold text-slate-800">Total : {{ totalFormatted }} MAD</span>
                            </div>
                            <!-- Total compact on mobile -->
                            <p class="sm:hidden text-xs text-slate-500">
                                {{ form.acts.length }} acte(s) · <span class="font-semibold text-slate-700">{{ totalFormatted }} MAD</span>
                            </p>
                            <!-- Boutons -->
                            <div class="flex gap-2">
                                <button
                                    @click="close"
                                    class="flex-1 sm:flex-none px-4 py-2 text-sm text-slate-500 hover:text-slate-700 rounded-xl hover:bg-slate-100 transition-colors border border-slate-200 sm:border-0"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="submit"
                                    :disabled="!form.patient_id || loading"
                                    class="flex-1 sm:flex-none px-5 py-2 text-sm font-medium rounded-xl transition-colors bg-blue-600 text-white hover:bg-blue-700 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                >
                                    <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{ isEdit ? "Enregistrer" : "Créer" }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { X } from "lucide-vue-next";
import ConsultationActRow from "./ConsultationActRow.vue";
import ToothChart from "./ToothChart.vue";
import { useConsultations } from "../../composables/useConsultations";

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
    const excluded = ['TERMINE', 'NO_SHOW', 'ANNULE'];
    const todayStr = today();
    filteredAppointments.value = props.availableAppointments
        .filter(
            (a) =>
                (String(a.patient?.id) === String(patientId) ||
                 String(a.patient_id) === String(patientId)) &&
                !excluded.includes(a.status),
        )
        .sort((a, b) => {
            const aToday = a.scheduled_date === todayStr ? 0 : 1;
            const bToday = b.scheduled_date === todayStr ? 0 : 1;
            if (aToday !== bToday) return aToday - bToday;
            return (a.scheduled_date + a.start_time).localeCompare(b.scheduled_date + b.start_time);
        });
}

function appointmentLabel(a) {
    const dateLabel = a.scheduled_date === today() ? "Aujourd'hui" : a.scheduled_date;
    return `${dateLabel}  ${a.start_time?.slice(0, 5)} → ${a.end_time?.slice(0, 5)}`;
}

const { createConsultation, updateConsultation } = useConsultations();

// ─── État du formulaire ───────────────────────────────────────────
const loading         = ref(false);
const error           = ref(null);
const showChartPanel  = ref(false);
const submitAttempted = ref(false);

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

function today() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}

const defaultForm = () => ({
    id: null,
    patient_id: props.appointmentPatient?.id ?? "",
    appointment_id: props.appointmentId ?? null,
    status: "EN_COURS",
    date: today(),
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
            date: c.session_dates?.[0] ?? today(),
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
    submitAttempted.value = true;
    if (!form.value.patient_id) return;
    loading.value = true;
    error.value = null;
    try {
        const payload = {
            patient_id: form.value.patient_id,
            appointment_id: form.value.appointment_id,
            status: form.value.status,
            date: form.value.date,
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
