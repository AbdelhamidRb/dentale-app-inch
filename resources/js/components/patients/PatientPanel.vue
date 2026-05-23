<template>
    <div class="flex flex-col h-full">
        <!-- ── En-tête du panneau ────────────────────────────────────── -->
        <div
            class="flex items-start justify-between p-5 border-b border-slate-100"
        >
            <div class="flex items-center gap-3">
                <!-- Avatar initiales -->
                <div
                    class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center shrink-0"
                >
                    <span class="text-sm font-bold text-white">{{
                        initials
                    }}</span>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-800">
                        {{ patient.full_name }}
                    </h2>
                    <p class="text-xs text-slate-400 font-mono">
                        {{ patient.numero_dossier }}
                    </p>

                    <!-- Badge solde -->
                    <div
                        v-if="balance && !balanceLoading"
                        :class="[
                            'inline-flex items-center gap-1.5 mt-1.5 px-2 py-0.5 rounded-lg border text-xs font-medium',
                            balanceColor,
                        ]"
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
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3
                                     2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0
                                     1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9
                                     0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        {{ balanceLabel }}
                    </div>
                    <div
                        v-else-if="balanceLoading"
                        class="mt-1.5 w-24 h-4 bg-slate-100 rounded animate-pulse"
                    ></div>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <!-- Bouton modifier -->
                <button
                    @click="$emit('edit')"
                    class="p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-slate-600 transition-colors"
                >
                    <Pencil class="w-4 h-4" />
                </button>
                <!-- Bouton archiver -->
                <button
                    @click="$emit('archive', patient.id)"
                    class="p-2 hover:bg-red-50 rounded-lg text-slate-400 hover:text-red-500 transition-colors"
                >
                    <Archive class="w-4 h-4" />
                </button>
                <!-- Bouton fermer -->
                <button
                    @click="$emit('close')"
                    class="p-2 hover:bg-slate-100 rounded-lg text-slate-400 transition-colors"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- ── Onglets ───────────────────────────────────────────────── -->
        <div class="flex border-b border-slate-100 shrink-0">
            <button
                @click="activeTab = 'fiche'"
                :class="['flex-1 py-2.5 text-xs font-medium transition-colors flex items-center justify-center gap-1.5',
                         activeTab === 'fiche'
                           ? 'text-blue-600 border-b-2 border-blue-500 -mb-px'
                           : 'text-slate-400 hover:text-slate-600']"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Fiche patient
            </button>
            <button
                @click="activeTab = 'schema'"
                :class="['flex-1 py-2.5 text-xs font-medium transition-colors flex items-center justify-center gap-1.5',
                         activeTab === 'schema'
                           ? 'text-blue-600 border-b-2 border-blue-500 -mb-px'
                           : 'text-slate-400 hover:text-slate-600']"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                </svg>
                Schéma dentaire
            </button>
        </div>

        <!-- ── Contenu scrollable ─────────────────────────────────────── -->
        <div v-if="activeTab === 'schema'" class="flex-1 overflow-y-auto">
            <ToothChart :patient-id="patient.id" />
        </div>
        <div v-else class="flex-1 overflow-y-auto p-5 space-y-5">
            <!-- ── Section : Alertes critiques (en haut si présentes) ─── -->
            <div
                v-if="hasCriticalAlerts"
                class="p-3 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2"
            >
                <span class="text-red-500 text-lg shrink-0"
                    ><AlertTriangle class="w-5 h-5 text-red-500"
                /></span>
                <div>
                    <p class="text-sm font-medium text-red-700">
                        Alertes critiques
                    </p>
                    <ul class="mt-1 space-y-0.5">
                        <li
                            v-for="a in criticalAlerts"
                            :key="a.id"
                            class="text-xs text-red-600"
                        >
                            • {{ a.description }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ── Section : Infos personnelles ─────────────────────── -->
            <div>
                <h3
                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3"
                >
                    Informations
                </h3>
                <div class="grid grid-cols-2 gap-y-3 gap-x-4">
                    <InfoRow label="Téléphone" :value="patient.phone" />
                    <InfoRow
                        label="Âge"
                        :value="patient.age ? patient.age + ' ans' : '—'"
                    />
                    <InfoRow
                        label="Sexe"
                        :value="
                            patient.gender === 'M'
                                ? 'Masculin'
                                : patient.gender === 'F'
                                  ? 'Féminin'
                                  : '—'
                        "
                    />
                    <InfoRow label="Couverture" :value="patient.couverture" />
                    <InfoRow
                        label="Patient depuis"
                        :value="patient.created_at"
                    />
                    <InfoRow
                        label="No-shows"
                        :value="patient.no_show_count + ' fois'"
                    />
                </div>
                <div class="mt-3 p-3 bg-slate-50 rounded-lg">
                    <p class="text-xs text-slate-500 font-medium mb-1">Notes</p>
                    <p
                        class="text-sm"
                        :class="
                            patient.notes
                                ? 'text-slate-700'
                                : 'text-slate-400 italic'
                        "
                    >
                        {{ patient.notes || "Aucune information" }}
                    </p>
                </div>
            </div>

            <!-- ── Section : Alertes médicales ───────────────────────── -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3
                        class="text-xs font-semibold text-slate-500 uppercase tracking-wide"
                    >
                        Alertes médicales
                    </h3>
                    <button
                        @click="showAlertForm = !showAlertForm"
                        class="text-xs text-blue-600 hover:underline flex items-center gap-1"
                    >
                        <Plus class="w-3 h-3" />
                        Ajouter
                    </button>
                </div>

                <!-- Formulaire ajout alerte -->
                <div
                    v-if="showAlertForm"
                    class="mb-3 p-3 border border-slate-200 rounded-lg space-y-2"
                >
                    <select
                        v-model="alertForm.type"
                        class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs"
                    >
                        <option value="ALLERGIE">Allergie</option>
                        <option value="MALADIE">Maladie chronique</option>
                        <option value="MEDICATION">Médicament en cours</option>
                    </select>
                    <input
                        v-model="alertForm.description"
                        placeholder="Description de l'alerte..."
                        class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs"
                    />
                    <select
                        v-model="alertForm.severity"
                        class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs"
                    >
                        <option value="ROUGE">🔴 Critique</option>
                        <option value="ORANGE">🟠 Modéré</option>
                        <option value="JAUNE">🟡 Information</option>
                    </select>
                    <div class="flex gap-2">
                        <button
                            @click="showAlertForm = false"
                            class="flex-1 py-1.5 border border-slate-300 rounded text-xs text-slate-600"
                        >
                            Annuler
                        </button>
                        <button
                            @click="submitAlert"
                            class="flex-1 py-1.5 bg-blue-600 text-white rounded text-xs"
                        >
                            Ajouter
                        </button>
                    </div>
                </div>

                <!-- Liste des alertes -->
                <div class="space-y-2">
                    <div
                        v-for="alert in data.medical_alerts"
                        :key="alert.id"
                        class="flex items-start gap-2 p-2.5 rounded-lg border"
                        :class="{
                            'bg-red-50 border-red-200':
                                alert.severity === 'ROUGE',
                            'bg-amber-50 border-amber-200':
                                alert.severity === 'ORANGE',
                            'bg-yellow-50 border-yellow-200':
                                alert.severity === 'JAUNE',
                        }"
                    >
                        <AlertTriangle
                            class="w-4 h-4 shrink-0"
                            :class="{
                                'text-red-500': alert.severity === 'ROUGE',
                                'text-amber-500': alert.severity === 'ORANGE',
                                'text-yellow-400': alert.severity === 'JAUNE',
                            }"
                        />
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-slate-700">
                                {{ alert.type }}
                            </p>
                            <p class="text-xs text-slate-600 mt-0.5">
                                {{ alert.description }}
                            </p>
                        </div>
                        <button
                            @click="
                                $emit('alert-deleted', patient.id, alert.id)
                            "
                            class="text-slate-300 hover:text-red-400 transition-colors shrink-0"
                        >
                            <X class="w-3 h-3" />
                        </button>
                    </div>

                    <p
                        v-if="data.medical_alerts?.length === 0"
                        class="text-xs text-slate-400 text-center py-3"
                    >
                        Aucune alerte médicale
                    </p>
                </div>
            </div>

            <!-- ── Section : Documents & Radios ───────────────────────── -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3
                        class="text-xs font-semibold text-slate-500 uppercase tracking-wide"
                    >
                        Documents & Radios
                    </h3>
                    <label
                        class="text-xs text-blue-600 hover:underline cursor-pointer flex items-center gap-1"
                    >
                        <Upload class="w-3 h-3" />
                        Uploader
                        <input
                            type="file"
                            class="hidden"
                            accept=".jpg,.jpeg,.png,.pdf,.webp"
                            @change="handleUpload"
                        />
                    </label>
                </div>

                <!-- Sélecteur type document -->
                <div
                    v-if="uploadingFile"
                    class="mb-3 p-3 border border-slate-200 rounded-lg space-y-2"
                >
                    <p class="text-xs text-slate-600 font-medium">
                        {{ uploadingFile.name }}
                    </p>
                    <select
                        v-model="docType"
                        class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs"
                    >
                        <option value="RADIO">Radio</option>
                        <option value="PHOTO">Photo intrabuccale</option>
                        <option value="CONSENTEMENT">Consentement signé</option>
                        <option value="PDF">Document PDF</option>
                        <option value="AUTRE">Autre</option>
                    </select>
                    <div class="flex gap-2">
                        <button
                            @click="uploadingFile = null"
                            class="flex-1 py-1.5 border border-slate-300 rounded text-xs"
                        >
                            Annuler
                        </button>
                        <button
                            @click="confirmUpload"
                            :disabled="uploading"
                            class="flex-1 py-1.5 bg-blue-600 text-white rounded text-xs disabled:opacity-50"
                        >
                            {{ uploading ? "Upload..." : "Confirmer" }}
                        </button>
                    </div>
                </div>

                <!-- Grille documents -->
                <div class="grid grid-cols-2 gap-2">
                    <div
                        v-for="doc in data.documents"
                        :key="doc.id"
                        class="border border-slate-200 rounded-lg overflow-hidden group"
                    >
                        <!-- @click sur le conteneur entier, pas sur l'image seule -->
                        <div
                            v-if="['RADIO', 'PHOTO'].includes(doc.type)"
                            class="aspect-video bg-slate-100 relative cursor-pointer"
                            @click="previewDoc = doc"
                        >
                            <img
                                :src="doc.url"
                                :alt="doc.original_name"
                                class="w-full h-full object-cover"
                            />
                            <!-- pointer-events-none → l'overlay ne bloque plus le clic -->
                            <div
                                class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100 pointer-events-none"
                            >
                                <Eye class="w-5 h-5 text-white" />
                            </div>
                        </div>

                        <!-- Aperçu PDF -->
                        <div
                            v-else
                            class="aspect-video bg-slate-50 flex items-center justify-center"
                        >
                            <FileText class="w-8 h-8 text-slate-300" />
                        </div>

                        <!-- Infos doc -->
                        <div class="p-2 flex items-center justify-between">
                            <div class="min-w-0">
                                <p
                                    class="text-[10px] font-medium text-slate-700 truncate"
                                >
                                    {{ doc.original_name }}
                                </p>
                                <p class="text-[10px] text-slate-400">
                                    {{ doc.type }} · {{ doc.size }}
                                </p>
                            </div>
                            <button
                                @click="
                                    $emit(
                                        'document-deleted',
                                        patient.id,
                                        doc.id,
                                    )
                                "
                                class="text-slate-300 hover:text-red-400 transition-colors shrink-0 ml-1"
                            >
                                <X class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                </div>

                <p
                    v-if="data.documents?.length === 0"
                    class="text-xs text-slate-400 text-center py-3"
                >
                    Aucun document
                </p>
            </div>

            <!-- ── Section : Historique consultations (placeholder) ───── -->
            <!-- ── Section : Solde & consultations ───────────────────── -->
            <div v-if="balance">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Récapitulatif financier
                    </h3>
                    <button @click="goToPayments"
                        class="text-xs font-medium text-blue-600 hover:text-blue-700
                               flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ajouter paiement
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="text-center p-2 bg-slate-50 rounded-xl">
                        <p class="text-sm font-bold text-slate-800">
                            {{
                                Number(
                                    balance.total_consultations ?? 0,
                                ).toFixed(0)
                            }}
                        </p>
                        <p class="text-[10px] text-slate-400 mt-0.5">
                            Total MAD
                        </p>
                    </div>
                    <div class="text-center p-2 bg-emerald-50 rounded-xl">
                        <p class="text-sm font-bold text-emerald-700">
                            {{ Number(balance.total_paid ?? 0).toFixed(0) }}
                        </p>
                        <p class="text-[10px] text-emerald-500 mt-0.5">
                            Payé MAD
                        </p>
                    </div>
                    <div
                        class="text-center p-2 rounded-xl"
                        :class="
                            (balance.balance ?? 0) < 0
                                ? 'bg-red-50'
                                : 'bg-blue-50'
                        "
                    >
                        <p
                            class="text-sm font-bold"
                            :class="
                                (balance.balance ?? 0) < 0
                                    ? 'text-red-600'
                                    : 'text-blue-600'
                            "
                        >
                            {{ (balance.balance ?? 0) > 0 ? "+" : ""
                            }}{{ Number(balance.balance ?? 0).toFixed(0) }}
                        </p>
                        <p class="text-[10px] mt-0.5 text-slate-400">
                            Solde MAD
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- fin onglet fiche -->

        <!-- ── Aperçu image fullscreen ─────────────────────────────── -->
        <!-- Teleport → sort du DOM du composant → z-index illimité -->
        <Teleport to="body">
            <div
                v-if="previewDoc"
                class="fixed inset-0 bg-black/80 z-[9999] flex items-center justify-center p-4"
                @click="previewDoc = null"
            >
                <button
                    @click="previewDoc = null"
                    class="absolute top-4 right-4 text-white/70 hover:text-white"
                >
                    <X class="w-6 h-6" />
                </button>
                <img
                    :src="previewDoc.url"
                    class="max-w-full max-h-full object-contain rounded-lg shadow-2xl"
                    @click.stop
                />
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import ToothChart from './ToothChart.vue';

const router = useRouter()

function goToPayments() {
    router.push({
        name: 'paiements',
        query: { patient_id: props.data?.patient?.id }
    })
}
import {
    X,
    Pencil,
    Archive,
    Plus,
    Upload,
    Eye,
    FileText,
    RotateCcw,
    AlertTriangle,
    Bell,
    FileImage,
} from "lucide-vue-next";

// ─── Props (DOIT être en premier) ─────────────────────────────────
const props = defineProps({
    data: { type: Object, required: true },
});

const emit = defineEmits([
    "close",
    "edit",
    "archive",
    "alert-added",
    "alert-deleted",
    "document-uploaded",
    "document-deleted",
]);

// ─── Onglet actif ─────────────────────────────────────────────────
const activeTab = ref('fiche');
watch(() => props.data?.patient?.id, () => { activeTab.value = 'fiche'; });

const patient = computed(() => props.data.patient);

// ─── Solde patient ────────────────────────────────────────────────
const balance = ref(null);
const balanceLoading = ref(false);

async function fetchBalance() {
    const id = props.data?.patient?.id;
    if (!id) return;
    balanceLoading.value = true;
    balance.value = null;
    try {
        const res = await fetch(`/api/payments/${id}`, {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
                Accept: "application/json",
            },
        });
        if (!res.ok) return;
        const json = await res.json();
        balance.value = json.patient ?? null;
    } catch (e) {
        console.error("Balance fetch error:", e);
    } finally {
        balanceLoading.value = false;
    }
}

const balanceColor = computed(
    () =>
        ({
            PARTIEL: "text-red-600 bg-red-50 border-red-200",
            PAYÉ: "text-emerald-600 bg-emerald-50 border-emerald-200",
            AVANCE: "text-blue-600 bg-blue-50 border-blue-200",
        })[balance.value?.balance_status] ??
        "text-slate-500 bg-slate-50 border-slate-200",
);

const balanceLabel = computed(() => {
    if (!balance.value) return null;
    const b = balance.value.balance;
    if (b < -0.01) return `Doit ${Math.abs(b).toFixed(0)} MAD`;
    if (b > 0.01) return `Avance ${b.toFixed(0)} MAD`;
    return "Compte soldé";
});

onMounted(() => fetchBalance());

watch(
    () => props.data?.patient?.id,
    (newId) => {
        if (newId) fetchBalance();
    },
);

// Initiales
const initials = computed(() =>
    patient.value.full_name
        .split(" ")
        .map((w) => w[0])
        .join("")
        .toUpperCase()
        .slice(0, 2),
);

// Alertes critiques
const criticalAlerts = computed(
    () =>
        props.data.medical_alerts?.filter((a) => a.severity === "ROUGE") || [],
);
const hasCriticalAlerts = computed(() => criticalAlerts.value.length > 0);

// ─── Alertes ──────────────────────────────────────────────────────
const showAlertForm = ref(false);
const alertForm = ref({ type: "ALLERGIE", description: "", severity: "ROUGE" });

async function submitAlert() {
    // Bloque si description vide
    if (!alertForm.value.description.trim()) {
        alert("La description est obligatoire.");
        return;
    }
    emit("alert-added", patient.value.id, alertForm.value);
    showAlertForm.value = false;
    alertForm.value = { type: "ALLERGIE", description: "", severity: "ROUGE" };
}

// ─── Documents ────────────────────────────────────────────────────
const uploadingFile = ref(null);
const docType = ref("RADIO");
const uploading = ref(false);
const previewDoc = ref(null);

function handleUpload(e) {
    uploadingFile.value = e.target.files[0];
}

async function confirmUpload() {
    if (!uploadingFile.value) return;
    uploading.value = true;
    emit(
        "document-uploaded",
        patient.value.id,
        uploadingFile.value,
        docType.value,
    );
    uploading.value = false;
    uploadingFile.value = null;
}
</script>
