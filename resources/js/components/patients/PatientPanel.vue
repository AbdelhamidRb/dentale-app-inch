<template>
    <div class="flex flex-col h-full">
        <!-- ── En-tête du panneau ────────────────────────────────────── -->
        <div
            class="flex items-start justify-between px-4 py-3 border-b border-slate-100"
        >
            <!-- Bouton retour mobile -->
            <button @click="$emit('close')"
                    class="lg:hidden flex items-center gap-1 mr-3 shrink-0 text-blue-600 hover:text-blue-800 transition-colors py-1 self-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="text-xs font-medium">Retour</span>
            </button>

            <div class="flex items-center gap-3 flex-1 min-w-0">
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
                        v-if="balance"
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
                </div>
            </div>

            <div class="flex items-center gap-1">
                <!-- Bouton modifier -->
                <button @click="$emit('edit')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
                    title="Modifier">
                    <Pencil class="w-4 h-4" />
                </button>
                <!-- Bouton archiver (dentiste uniquement) -->
                <button v-if="isDentist" @click="$emit('archive', patient.id)"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors"
                    title="Archiver">
                    <Archive class="w-4 h-4" />
                </button>
                <!-- Bouton fermer (desktop uniquement) -->
                <button @click="$emit('close')"
                    class="hidden lg:flex w-8 h-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
                    title="Fermer">
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
                v-if="isDentist"
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
        <div v-if="activeTab === 'schema'" class="flex-1 overflow-y-auto pb-24 lg:pb-0">
            <ToothChart :patient-id="patient.id" />
        </div>
        <div v-else class="flex-1 overflow-y-auto p-5 space-y-5 pb-24 lg:pb-5">
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
                    <!-- Erreur ajout alerte -->
                    <p v-if="alertError" class="text-xs text-red-500">{{ alertError }}</p>
                    <div class="flex gap-2">
                        <button
                            @click="showAlertForm = false; alertError = null"
                            class="flex-1 py-1.5 border border-slate-300 rounded text-xs text-slate-600"
                        >
                            Annuler
                        </button>
                        <button
                            @click="submitAlert"
                            :disabled="alertSubmitting"
                            class="flex-1 py-1.5 bg-blue-600 text-white rounded text-xs disabled:opacity-50"
                        >
                            {{ alertSubmitting ? "Ajout..." : "Ajouter" }}
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
                            v-if="isDentist"
                            @click="handleDeleteAlert(alert.id)"
                            class="text-slate-300 hover:text-red-400 transition-colors shrink-0"
                            title="Supprimer cette alerte"
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
                            ref="fileInput"
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
                        <span class="text-slate-400 font-normal ml-1">({{ (uploadingFile.size / 1024 / 1024).toFixed(1) }} Mo)</span>
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
                    <!-- Erreur upload -->
                    <p v-if="uploadError" class="text-xs text-red-500">{{ uploadError }}</p>
                    <div class="flex gap-2">
                        <button
                            @click="cancelUpload"
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
                            <div
                                class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100 pointer-events-none"
                            >
                                <Eye class="w-5 h-5 text-white" />
                            </div>
                        </div>

                        <!-- Aperçu PDF / autre -->
                        <div
                            v-else
                            class="aspect-video bg-slate-50 flex flex-col items-center justify-center gap-1 cursor-pointer hover:bg-slate-100 transition-colors group"
                            @click="openDoc(doc.url)"
                        >
                            <FileText class="w-8 h-8 text-slate-300 group-hover:text-blue-400 transition-colors" />
                            <span class="text-[10px] text-slate-400 group-hover:text-blue-500">Ouvrir</span>
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
                                v-if="isDentist"
                                @click="handleDeleteDocument(doc.id)"
                                class="text-slate-300 hover:text-red-400 transition-colors shrink-0 ml-1"
                                title="Supprimer ce document"
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

            <!-- ── Section : Récapitulatif financier ──────────────────── -->
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
                            {{ Number(balance.total_consultations ?? 0).toFixed(0) }}
                        </p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Total MAD</p>
                    </div>
                    <div class="text-center p-2 bg-emerald-50 rounded-xl">
                        <p class="text-sm font-bold text-emerald-700">
                            {{ Number(balance.total_paid ?? 0).toFixed(0) }}
                        </p>
                        <p class="text-[10px] text-emerald-500 mt-0.5">Payé MAD</p>
                    </div>
                    <div
                        class="text-center p-2 rounded-xl"
                        :class="(balance.balance ?? 0) < 0 ? 'bg-red-50' : 'bg-blue-50'"
                    >
                        <p
                            class="text-sm font-bold"
                            :class="(balance.balance ?? 0) < 0 ? 'text-red-600' : 'text-blue-600'"
                        >
                            {{ (balance.balance ?? 0) > 0 ? "+" : "" }}{{ Number(balance.balance ?? 0).toFixed(0) }}
                        </p>
                        <p class="text-[10px] mt-0.5 text-slate-400">Solde MAD</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- fin onglet fiche -->

        <!-- ── Aperçu image fullscreen ─────────────────────────────── -->
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

    <ConfirmModal
        :model-value="!!confirmDeleteAlertId"
        @update:model-value="v => { if (!v) confirmDeleteAlertId = null }"
        title="Supprimer l'alerte"
        message="Cette alerte médicale sera <strong>supprimée définitivement</strong>."
        confirm-label="Supprimer"
        @confirm="doDeleteAlert" />

    <ConfirmModal
        :model-value="!!confirmDeleteDocId"
        @update:model-value="v => { if (!v) confirmDeleteDocId = null }"
        title="Supprimer le document"
        message="Ce document sera <strong>supprimé définitivement</strong> du dossier patient."
        confirm-label="Supprimer"
        @confirm="doDeleteDocument" />
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useRouter } from "vue-router";
import {
    X,
    Pencil,
    Archive,
    Plus,
    Upload,
    Eye,
    FileText,
    AlertTriangle,
} from "lucide-vue-next";
import ToothChart from "./ToothChart.vue";
import ConfirmModal from "../ui/ConfirmModal.vue";

const router = useRouter();

const isDentist = computed(() => {
    try { return JSON.parse(localStorage.getItem('user') || '{}').role === 'DENTIST'; }
    catch { return false; }
});

// ─── Props ────────────────────────────────────────────────────────
const props = defineProps({
    data:           { type: Object,   required: true },
    uploadDocument: { type: Function, required: true },
    deleteDocument: { type: Function, required: true },
    addAlert:       { type: Function, required: true },
    deleteAlert:    { type: Function, required: true },
});

const emit = defineEmits(["close", "edit", "archive"]);

// ─── Onglet actif ─────────────────────────────────────────────────
const activeTab = ref("fiche");

const patient = computed(() => props.data.patient);

// ─── Solde patient — fourni par GET /api/patients/{id}, pas d'appel séparé ──
const balance = computed(() => props.data.balance ?? null);

watch(() => props.data?.patient?.id, () => { activeTab.value = "fiche"; });

const balanceColor = computed(
    () =>
        ({
            PARTIEL: "text-red-600 bg-red-50 border-red-200",
            PAYÉ:    "text-emerald-600 bg-emerald-50 border-emerald-200",
            AVANCE:  "text-blue-600 bg-blue-50 border-blue-200",
        })[balance.value?.balance_status] ??
        "text-slate-500 bg-slate-50 border-slate-200",
);

const balanceLabel = computed(() => {
    if (!balance.value) return null;
    const b = balance.value.balance;
    if (b < -0.01) return `Doit ${Math.abs(b).toFixed(0)} MAD`;
    if (b > 0.01)  return `Avance ${b.toFixed(0)} MAD`;
    return "Compte soldé";
});

function goToPayments() {
    router.push({ name: "paiements", query: { patient_id: patient.value?.id } });
}

// ─── Initiales ────────────────────────────────────────────────────
const initials = computed(() =>
    patient.value.full_name
        .split(" ")
        .map((w) => w[0])
        .join("")
        .toUpperCase()
        .slice(0, 2),
);

// ─── Alertes critiques ────────────────────────────────────────────
const criticalAlerts   = computed(() =>
    props.data.medical_alerts?.filter((a) => a.severity === "ROUGE") || [],
);
const hasCriticalAlerts = computed(() => criticalAlerts.value.length > 0);

// ─── Alertes médicales ────────────────────────────────────────────
const showAlertForm   = ref(false);
const alertSubmitting = ref(false);
const alertError      = ref(null);
const alertForm       = ref({ type: "ALLERGIE", description: "", severity: "ROUGE" });

async function submitAlert() {
    if (!alertForm.value.description.trim()) {
        alertError.value = "La description est obligatoire.";
        return;
    }
    alertSubmitting.value = true;
    alertError.value      = null;
    try {
        await props.addAlert(patient.value.id, { ...alertForm.value });
        showAlertForm.value = false;
        alertForm.value     = { type: "ALLERGIE", description: "", severity: "ROUGE" };
    } catch (e) {
        alertError.value = e.message || "Erreur lors de l'ajout.";
    } finally {
        alertSubmitting.value = false;
    }
}

const confirmDeleteAlertId = ref(null);
const confirmDeleteDocId   = ref(null);

function handleDeleteAlert(alertId) { confirmDeleteAlertId.value = alertId; }

async function doDeleteAlert() {
    await props.deleteAlert(patient.value.id, confirmDeleteAlertId.value);
    confirmDeleteAlertId.value = null;
}

// ─── Documents ────────────────────────────────────────────────────
function openDoc(url) {
    window.open(url, '_blank', 'noopener,noreferrer');
}
const fileInput    = ref(null);
const uploadingFile = ref(null);
const docType      = ref("RADIO");
const uploading    = ref(false);
const uploadError  = ref(null);
const previewDoc   = ref(null);

const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 Mo

function handleUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > MAX_FILE_SIZE) {
        alert(`Fichier trop volumineux (max 10 Mo). Taille : ${(file.size / 1024 / 1024).toFixed(1)} Mo`);
        e.target.value = "";
        return;
    }
    uploadingFile.value = file;
    uploadError.value   = null;
}

function cancelUpload() {
    uploadingFile.value = null;
    uploadError.value   = null;
    if (fileInput.value) fileInput.value.value = "";
}

async function confirmUpload() {
    if (!uploadingFile.value) return;
    uploading.value   = true;
    uploadError.value = null;
    try {
        await props.uploadDocument(patient.value.id, uploadingFile.value, docType.value);
        uploadingFile.value = null;
        if (fileInput.value) fileInput.value.value = "";
    } catch (e) {
        uploadError.value = e.message || "Erreur lors de l'upload.";
    } finally {
        uploading.value = false;
    }
}

function handleDeleteDocument(docId) { confirmDeleteDocId.value = docId; }

async function doDeleteDocument() {
    await props.deleteDocument(patient.value.id, confirmDeleteDocId.value);
    confirmDeleteDocId.value = null;
}
</script>
