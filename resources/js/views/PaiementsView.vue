<!-- resources/js/views/PaiementsView.vue -->
<template>
    <div class="flex flex-col lg:flex-row flex-1 min-h-0 overflow-hidden bg-slate-50">
        <!-- ══════════════════════════════════════════════════════
             COLONNE GAUCHE — Liste patients
        ═══════════════════════════════════════════════════════ -->
        <div
            :class="[
                'flex flex-col bg-white min-h-0 transition-all duration-300',
                selectedPatient
                    ? 'hidden lg:flex lg:w-full lg:max-w-md lg:border-r border-slate-200'
                    : 'flex-1',
            ]"
        >
            <!-- Header compact -->
            <div class="px-4 pt-4 pb-3 border-b border-slate-100">
                <!-- Ligne 1 : titre -->
                <div class="flex items-center justify-between mb-2">
                    <h1 class="text-base font-semibold text-slate-800">Paiements</h1>
                </div>

                <!-- Stats en grid 3 colonnes -->
                <div class="grid grid-cols-3 gap-1 mb-3 bg-slate-50 rounded-xl p-2">
                    <div v-for="s in statCards" :key="s.label" class="text-center px-1">
                        <p :class="['text-sm font-bold leading-tight', s.color]">{{ fmt(s.value) }}</p>
                        <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ s.label }}</p>
                    </div>
                </div>

                <!-- Ligne 2 : recherche -->
                <div class="relative mb-2">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input v-model="searchQuery" placeholder="Patient…"
                           class="w-full pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-300 bg-slate-50"/>
                </div>

                <!-- Ligne 3 : filtres -->
                <div class="flex gap-1.5 flex-wrap">
                    <button
                        v-for="f in filters"
                        :key="f.value"
                        @click="activeFilter = activeFilter === f.value ? '' : f.value"
                        :class="[
                            'px-2.5 py-1 text-xs font-medium rounded-lg border transition-colors',
                            activeFilter === f.value
                                ? f.active
                                : 'border-slate-200 text-slate-500 hover:bg-slate-50',
                        ]"
                    >
                        {{ f.label }}
                        <span v-if="filterCount(f.value)" class="ml-0.5 font-bold">
                            {{ filterCount(f.value) }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Liste patients -->
            <div class="flex-1 overflow-y-auto pb-20 lg:pb-0">
                <!-- Skeleton -->
                <div v-if="loading" class="divide-y divide-slate-100">
                    <div
                        v-for="i in 6"
                        :key="i"
                        class="px-5 py-4 animate-pulse"
                    >
                        <div class="flex items-center justify-between">
                            <div class="space-y-2">
                                <div
                                    class="w-32 h-4 bg-slate-200 rounded-full"
                                ></div>
                                <div
                                    class="w-20 h-3 bg-slate-100 rounded-full"
                                ></div>
                            </div>
                            <div class="w-16 h-6 bg-slate-200 rounded"></div>
                        </div>
                    </div>
                </div>

                <!-- Vide -->
                <div
                    v-else-if="!filteredPatients.length"
                    class="flex flex-col items-center justify-center h-48 px-6 text-center"
                >
                    <div
                        class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3"
                    >
                        <svg
                            class="w-7 h-7 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-600">
                        Aucun patient trouvé
                    </p>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        Les patients apparaissent ici<br>dès qu'ils ont une consultation
                    </p>
                </div>

                <button
                    v-for="p in filteredPatients"
                    :key="p.id"
                    @click="selectPatient(p)"
                    :class="[
                        'w-full text-left px-4 py-3 border-b border-slate-100',
                        'hover:bg-slate-50 transition-colors',
                        selectedPatient?.id === p.id
                            ? 'bg-blue-50 border-l-2 border-l-blue-500'
                            : '',
                    ]"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <!-- Ligne 1 -->
                            <div class="flex items-center gap-1.5 mb-1">
                                <PaymentStatusBadge
                                    :status="p.balance_status"
                                />
                                <span
                                    class="text-xs font-medium text-slate-700 truncate"
                                >
                                    {{ p.full_name }}
                                </span>
                            </div>
                            <!-- Ligne 2 : barre + infos -->
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-1.5 bg-slate-100 rounded-full overflow-hidden w-24 shrink-0"
                                >
                                    <div
                                        class="h-full rounded-full"
                                        :class="progressColor(p.balance_status)"
                                        :style="`width: ${progressPct(p)}%`"
                                    ></div>
                                </div>
                                <span class="text-[10px] text-slate-400">
                                    {{ p.consultations_count }} consult.
                                </span>
                            </div>
                        </div>
                        <!-- Solde -->
                        <div class="text-right shrink-0">
                            <p
                                :class="[
                                    'text-sm font-bold',
                                    p.balance < 0
                                        ? 'text-red-600'
                                        : p.balance > 0
                                          ? 'text-blue-600'
                                          : 'text-emerald-600',
                                ]"
                            >
                                {{ p.balance > 0 ? "+" : ""
                                }}{{ fmt(p.balance) }} MAD
                            </p>
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════
             COLONNE DROITE — Panneau patient
        ═══════════════════════════════════════════════════════ -->
        <div v-if="selectedPatient" class="flex-1 min-h-0 flex flex-col overflow-hidden">
            <PaymentPanel
                :patient="selectedPatient"
                :is-dentist="isDentist"
                @close="selectedPatient = null"
                @updated="onUpdated"
                @patch-patient="(data) => selectedPatient && Object.assign(selectedPatient, data)"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import PaymentStatusBadge from "../components/payments/PaymentStatusBadge.vue";
import PaymentPanel from "../components/payments/PaymentPanel.vue";
import { usePayments } from "../composables/usePayments";
import { useRoute } from "vue-router";
const route = useRoute();

// ─── Auth ─────────────────────────────────────────────────────────
const isDentist = computed(() => {
    try {
        return (
            JSON.parse(localStorage.getItem("user") || "{}").role === "DENTIST"
        );
    } catch {
        return false;
    }
});

// ─── État ─────────────────────────────────────────────────────────
const { patients, loading, stats, fetchPatients, invalidateCache } =
    usePayments();

const selectedPatient = ref(null);
const searchQuery = ref("");
const searchQueryDebounced = ref("");
let searchTimer = null;

watch(searchQuery, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searchQueryDebounced.value = val;
    }, 300); // attend 300ms avant de filtrer
});
const activeFilter = ref("");

// ─── Filtres ──────────────────────────────────────────────────────
const filters = [
    {
        value: "PARTIEL",
        label: "Partiels",
        active: "border-red-400 bg-red-50 text-red-700",
    },
    {
        value: "PAYÉ",
        label: "Soldés",
        active: "border-emerald-400 bg-emerald-50 text-emerald-700",
    },
    {
        value: "AVANCE",
        label: "Avances",
        active: "border-blue-400 bg-blue-50 text-blue-700",
    },
];

function filterCount(status) {
    return patients.value.filter((p) => p.balance_status === status).length;
}

// Filtre combiné : statut + recherche
const filteredPatients = computed(() => {
    let list = patients.value;

    if (activeFilter.value) {
        list = list.filter((p) => p.balance_status === activeFilter.value);
    }

    if (searchQueryDebounced.value.trim()) {
        const q = searchQueryDebounced.value.toLowerCase();
        list = list.filter(
            (p) =>
                p.full_name?.toLowerCase().includes(q) || p.phone?.includes(q),
        );
    }

    // Trie : partiels en premier (solde le plus négatif)
    return [...list].sort((a, b) => a.balance - b.balance);
});

// Recharge quand le filtre change
watch(activeFilter, () => {
    invalidateCache();
    fetchPatients({}, true);
});

// ─── Stats ────────────────────────────────────────────────────────
const statCards = computed(() => [
    {
        label: "Dû total",
        value: stats.value.total_du,
        color: "text-slate-700",
        isAmount: true,
    },
    {
        label: "Encaissé",
        value: stats.value.total_encaisse,
        color: "text-emerald-600",
        isAmount: true,
    },
    {
        label: "Restant",
        value: stats.value.total_restant,
        color: "text-red-500",
        isAmount: true,
    },
]);

// ─── Sélection patient ────────────────────────────────────────────
function selectPatient(p) {
    selectedPatient.value = p;
}

// ─── Callback après versement ─────────────────────────────────────
async function onUpdated() {
    invalidateCache();
    await fetchPatients({}, true);
    // Rafraîchit le patient sélectionné depuis la liste mise à jour
    if (selectedPatient.value) {
        const updated = patients.value.find(
            (p) => p.id === selectedPatient.value.id,
        );
        if (updated)
            selectedPatient.value = { ...selectedPatient.value, ...updated };
    }
}

// ─── Helpers ──────────────────────────────────────────────────────
function fmt(val) {
    return Number(val ?? 0).toFixed(0);
}

function progressPct(p) {
    if (!p.total_consultations) return 0;
    return Math.min(100, (p.total_paid / p.total_consultations) * 100);
}

function progressColor(status) {
    return (
        {
            PAYÉ: "bg-emerald-500",
            PARTIEL: "bg-red-400",
            AVANCE: "bg-blue-400",
        }[status] ?? "bg-slate-300"
    );
}

// ─── Init ─────────────────────────────────────────────────────────
onMounted(async () => {
    await fetchPatients()

    // Auto-sélection depuis PatientPanel (patient avec OU sans consultation)
    const pid = route.query.patient_id
    if (pid) {
        // Cherche dans la liste chargée
        const found = patients.value.find(p => String(p.id) === String(pid))
        if (found) {
            selectPatient(found)
        } else {
            // Patient sans consultation → charge directement depuis l'API
            try {
                const token = localStorage.getItem('token')
                const res = await fetch(`/api/payments/${pid}`, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: 'application/json'
                    }
                })
                const data = await res.json()
                if (data.patient) {
                    // Ajoute temporairement à la liste
                    patients.value.unshift(data.patient)
                    selectPatient(data.patient)
                }
            } catch (e) {
                error.value = e.message ?? "Impossible de charger le patient.";
            }
        }
    }
})
</script>
