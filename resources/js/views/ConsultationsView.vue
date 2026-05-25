<template>
    <div class="flex flex-col lg:flex-row flex-1 min-h-0 overflow-hidden bg-slate-50">
        <!-- ══════════════════════════════════════════════════════════
             Colonne gauche : liste des consultations
        ═══════════════════════════════════════════════════════════ -->
        <div
            :class="[
                'flex flex-col bg-white min-h-0 transition-all duration-300',
                selectedConsultation
                    ? 'hidden lg:flex lg:w-full lg:max-w-xl lg:border-r border-slate-200'
                    : 'flex-1',
            ]"
        >
            <!-- Header compact -->
            <div class="px-4 pt-4 pb-3 border-b border-slate-100">
                <!-- Ligne 1 : titre + bouton -->
                <div class="flex items-center justify-between mb-2">
                    <h1 class="text-base font-semibold text-slate-800">Consultations</h1>
                    <button v-if="isDentist" @click="openCreateModal()"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors shrink-0">
                        <Plus class="w-3.5 h-3.5" />
                        Nouvelle
                    </button>
                </div>

                <!-- Stats (ligne 2) -->
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span v-for="stat in stats" :key="stat.label" class="flex items-center gap-1">
                        <span :class="['text-sm font-bold', stat.color]">{{ stat.value }}</span>
                        <span class="text-xs text-slate-400">{{ stat.label }}</span>
                    </span>
                </div>

                <!-- Dates -->
                <div class="flex gap-2 mb-2">
                    <div class="flex items-center gap-1 flex-1">
                        <label class="text-[10px] text-slate-400 shrink-0"
                            >Du</label
                        >
                        <input
                            type="date"
                            v-model="filters.date_from"
                            class="flex-1 border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-300 bg-white"
                        />
                    </div>
                    <div class="flex items-center gap-1 flex-1">
                        <label class="text-[10px] text-slate-400 shrink-0"
                            >Au</label
                        >
                        <input
                            type="date"
                            v-model="filters.date_to"
                            class="flex-1 border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-300 bg-white"
                        />
                    </div>
                </div>

                <!-- Ligne 2 : recherche -->
                <div class="relative mb-2">
                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                    <input v-model="filters.search" placeholder="Patient…"
                           class="w-full pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-300 bg-white"/>
                </div>

                <!-- Ligne 3 : filtres statut -->
                <div class="flex gap-1.5 flex-wrap">
                    <button
                        v-for="s in statusOptions"
                        :key="s.value"
                        @click="filters.status = filters.status === s.value ? '' : s.value"
                        :class="[
                            'px-2.5 py-1 text-xs font-medium rounded-lg border transition-colors',
                            filters.status === s.value
                                ? s.activeClass
                                : 'border-slate-200 text-slate-500 hover:bg-slate-50',
                        ]"
                    >
                        {{ s.label }}
                    </button>
                </div>
            </div>

            <!-- Liste -->
            <div class="flex-1 overflow-y-auto pb-20 lg:pb-0">
                <!-- Loading -->
                <!-- Skeleton loading -->
                <div v-if="loading" class="divide-y divide-slate-100">
                    <div
                        v-for="i in 5"
                        :key="i"
                        class="px-5 py-4 animate-pulse"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                class="w-16 h-4 bg-slate-200 rounded-full"
                            ></div>
                            <div
                                class="w-24 h-4 bg-slate-100 rounded-full"
                            ></div>
                        </div>
                        <div class="w-48 h-4 bg-slate-200 rounded mb-2"></div>
                        <div class="flex gap-1 mb-2">
                            <div class="w-8 h-5 bg-slate-100 rounded"></div>
                            <div class="w-8 h-5 bg-slate-100 rounded"></div>
                        </div>
                        <div class="w-32 h-3 bg-slate-100 rounded"></div>
                    </div>
                </div>

                <!-- Vide -->
                <div
                    v-else-if="!filteredConsultations.length"
                    class="flex flex-col items-center justify-center h-48 text-center px-6"
                >
                    <div
                        class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3"
                    >
                        <ClipboardList class="w-6 h-6 text-slate-400" />
                    </div>
                    <p class="text-sm font-medium text-slate-500">
                        Aucune consultation trouvée
                    </p>
                    <p class="text-xs text-slate-400 mt-1">
                        Modifiez les filtres ou créez une nouvelle consultation
                    </p>
                </div>

                <!-- Cards -->
                <div v-else>
                    <button
                        v-for="c in filteredConsultations"
                        :key="c.id"
                        @click="selectConsultation(c)"
                        :class="[
                            'w-full text-left px-4 py-3.5 border-b border-slate-100',
                            'hover:bg-slate-50 transition-colors',
                            selectedConsultation?.id === c.id
                                ? 'bg-blue-50 border-l-2 border-l-blue-500'
                                : '',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <!-- Ligne 1 : statut + patient -->
                                <div class="flex items-center gap-2 mb-1">
                                    <ConsultationStatusBadge :status="c.status" />
                                    <span class="text-sm font-semibold text-slate-800 truncate">
                                        {{ c.patient?.full_name }}
                                    </span>
                                </div>
                                <!-- Ligne 2 : actes -->
                                <p class="text-xs text-slate-500 truncate mb-2">
                                    {{ actesSummary(c) }}
                                </p>
                                <!-- Ligne 3 : dents + date -->
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span
                                        v-for="t in teethInConsultation(c).slice(0, 4)"
                                        :key="t"
                                        class="px-1.5 py-0.5 bg-blue-100 text-blue-700
                                               text-[10px] font-semibold rounded"
                                    >{{ t }}</span>
                                    <span
                                        v-if="teethInConsultation(c).length > 4"
                                        class="text-[10px] text-slate-400"
                                    >+{{ teethInConsultation(c).length - 4 }}</span>
                                    <span class="text-[10px] text-slate-300 ml-auto">
                                        {{ c.created_at }}
                                    </span>
                                </div>
                            </div>
                            <!-- Total + séances -->
                            <div class="text-right shrink-0 pt-0.5">
                                <p class="text-sm font-bold text-slate-800">
                                    {{ c.total_price?.toFixed(0) ?? 0 }} MAD
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5">
                                    {{ c.sessions_count }} séance{{ c.sessions_count !== 1 ? 's' : '' }}
                                </p>
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-100 shrink-0">
                    <p class="text-xs text-slate-400">Page {{ meta.current_page }} / {{ meta.last_page }}</p>
                    <div class="flex gap-1">
                        <button @click="goToPage(meta.current_page - 1)" :disabled="meta.current_page === 1"
                            class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            ← Préc.
                        </button>
                        <button @click="goToPage(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page"
                            class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            Suiv. →
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
             Colonne droite : panneau détail
        ═══════════════════════════════════════════════════════════ -->
        <div v-if="selectedConsultation" class="flex-1 min-h-0 flex flex-col overflow-hidden">
            <ConsultationPanel
                v-if="selectedConsultation"
                :consultation="selectedConsultation"
                :is-dentist="isDentist"
                @close="selectedConsultation = null"

                @updated="onUpdated"
                @deleted="onDeleted"
            />

            <!-- Placeholder vide -->
        </div>

        <!-- ══════════════════════════════════════════════════════════
             Modal création / édition
        ═══════════════════════════════════════════════════════════ -->
        <ConsultationModal
            v-model="showModal"

            :patients="patients"
            :catalog-acts="catalogActs"
            :available-appointments="appointments"
            @saved="onSaved"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { Plus, Search, ClipboardList } from 'lucide-vue-next';
import { useRoute } from "vue-router";

const route = useRoute();
import ConsultationStatusBadge from "../components/consultations/ConsultationStatusBadge.vue";
import ConsultationPanel from "../components/consultations/ConsultationPanel.vue";
import ConsultationModal from "../components/consultations/ConsultationModal.vue";
import { useConsultations } from "../composables/useConsultations";
import { consultationsApi } from "../api/consultations";
import { patientsApi } from "../api/patients";
import { appointmentsApi } from "../api/appointments";

// ─── Auth ─────────────────────────────────────────────────────────
import { authStore } from "../stores/auth";
const isDentist = computed(() => authStore.isDentist());

// ─── Données ──────────────────────────────────────────────────────
const patients = ref([]);
const catalogActs = ref([]);
const appointments = ref([]);

const {
    consultations,
    loading,
    meta,
    fetchConsultations,
    fetchConsultation,
    invalidateCache,
    goToPage,
} = useConsultations();

// ─── Sélection ────────────────────────────────────────────────────
const selectedConsultation = ref(null);
const consultationCache = new Map();

async function selectConsultation(c) {
    // 1. Affiche IMMÉDIATEMENT les données déjà connues (depuis la liste)
    selectedConsultation.value = c;

    // 2. Si déjà en cache → pas d'appel API
    if (consultationCache.has(c.id)) {
        selectedConsultation.value = consultationCache.get(c.id);
        return;
    }

    // 3. Charge les détails complets en arrière-plan (actes + dents)
    try {
        const res = await consultationsApi.get(c.id);
        consultationCache.set(c.id, res.consultation);
        selectedConsultation.value = res.consultation;
    } catch (e) {
        error.value = e.message ?? "Impossible de charger la consultation.";
    }
}

// ─── Filtres locaux ───────────────────────────────────────────────
// Dates par défaut : 30 derniers jours
const defaultDateFrom = () => {
    const d = new Date();
    d.setDate(d.getDate() - 30);
    return d.toISOString().split("T")[0];
};
const defaultDateTo = () => new Date().toISOString().split("T")[0];

const filters = ref({
    status: "",
    search: "",
    date_from: defaultDateFrom(),
    date_to: defaultDateTo(),
});

const filteredConsultations = computed(() => {
    let list = consultations.value;
    if (filters.value.status) {
        list = list.filter((c) => c.status === filters.value.status);
    }
    if (filters.value.search.trim()) {
        const q = filters.value.search.toLowerCase();
        list = list.filter((c) =>
            c.patient?.full_name?.toLowerCase().includes(q),
        );
    }
    return list;
});

// Recharge depuis API quand les dates changent
watch([() => filters.value.date_from, () => filters.value.date_to], () => {
    invalidateCache();
    fetchConsultations(
        { date_from: filters.value.date_from, date_to: filters.value.date_to },
        true,
    );
});

// ─── Stats rapides ────────────────────────────────────────────────
const stats = computed(() => [
    {
        label: "Total",
        value: consultations.value.length,
        color: "text-slate-700",
    },
    {
        label: "En cours",
        value: consultations.value.filter((c) => c.status === "EN_COURS")
            .length,
        color: "text-amber-600",
    },
    {
        label: "Terminées",
        value: consultations.value.filter((c) => c.status === "TERMINE").length,
        color: "text-green-600",
    },
]);

// ─── Options statut ───────────────────────────────────────────────
const statusOptions = [
    {
        value: "EN_COURS",
        label: "En cours",
        activeClass: "border-amber-400 bg-amber-50  text-amber-700",
    },
    {
        value: "TERMINE",
        label: "Terminées",
        activeClass: "border-green-400 bg-green-50 text-green-700",
    },
];

// ─── Modal ────────────────────────────────────────────────────────
const showModal = ref(false);
async function openCreateModal() {
    showModal.value = true;
    // Charge les RDV seulement à l'ouverture de la modale
    if (!appointments.value.length) {
        await loadAppointments();
    }
}

async function loadAppointments() {
    const token = localStorage.getItem("token");
    const h = { Authorization: `Bearer ${token}`, Accept: "application/json" };
    try {
        const results = [];
        const today = new Date();
        const dates = Array.from({ length: 14 }, (_, i) => {
            const d = new Date(today);
            d.setDate(today.getDate() - 7 + i);
            return d.toISOString().split("T")[0];
        });
        const responses = await Promise.allSettled(
            dates.map((dateStr) =>
                fetch(`/api/appointments?date=${dateStr}`, { headers: h }).then(
                    (r) => r.json(),
                ),
            ),
        );
        responses.forEach((res) => {
            if (res.status === "fulfilled") {
                (res.value.appointments ?? []).forEach((rdv) => {
                    if (!results.find((x) => x.id === rdv.id))
                        results.push(rdv);
                });
            }
        });
        appointments.value = results.sort(
            (a, b) => new Date(b.scheduled_date) - new Date(a.scheduled_date),
        );
    } catch (e) {
        error.value = e.message ?? "Impossible de charger les rendez-vous.";
    }
}


async function onSaved() {
    consultationCache.clear();
    invalidateCache();
    await fetchConsultations({}, true); // force refresh
}

async function onUpdated() {
    consultationCache.clear();
    invalidateCache();
    await fetchConsultations({}, true);
    if (selectedConsultation.value) {
        const res = await consultationsApi.get(selectedConsultation.value.id);
        consultationCache.set(selectedConsultation.value.id, res.consultation);
        selectedConsultation.value = res.consultation;
    }
}

function onDeleted() {
    consultationCache.clear();
    selectedConsultation.value = null;
    fetchConsultations();
}

// ─── Helpers ─────────────────────────────────────────────────────
function actesSummary(c) {
    if (!c.acts?.length) return "Aucune intervention";
    return (
        c.acts
            .slice(0, 2)
            .map((a) => a.catalog_act?.name)
            .join(", ") + (c.acts.length > 2 ? ` +${c.acts.length - 2}` : "")
    );
}

function teethInConsultation(c) {
    const teeth = new Set();
    (c.acts ?? []).forEach((a) => (a.teeth ?? []).forEach((t) => teeth.add(t)));
    return [...teeth].sort((a, b) => a - b);
}

// ─── Init ─────────────────────────────────────────────────────────
onMounted(async () => {
    const token = localStorage.getItem("token");
    const h = { Authorization: `Bearer ${token}`, Accept: "application/json" };

    // ── Tout en parallèle dès le départ ───────────────────────────
    const [, pResult, caResult] = await Promise.allSettled([
        fetchConsultations({ date_from: filters.value.date_from, date_to: filters.value.date_to }),
        fetch("/api/patients?per_page=100", { headers: h }).then((r) =>
            r.json(),
        ),
        fetch("/api/catalog-acts", { headers: h }).then((r) => r.json()),
    ]);

    if (pResult.status === "fulfilled")
        patients.value = pResult.value?.data ?? [];

    if (caResult.status === "fulfilled") {
        const caData = caResult.value;
        catalogActs.value = Array.isArray(caData)
            ? caData
            : (caData.catalog_acts ?? []);
    }

    // ── Auto-sélection si ?id= dans l'URL ─────────────────────────
    const targetId = route.query.id;
    if (targetId) {
        const found = consultations.value.find(
            (c) => String(c.id) === String(targetId),
        );
        if (found) {
            await selectConsultation(found);
        } else {
            try {
                const res = await consultationsApi.get(targetId);
                if (res.consultation) {
                    selectedConsultation.value = res.consultation;
                    consultationCache.set(
                        res.consultation.id,
                        res.consultation,
                    );
                }
            } catch {}
        }
    }

    // ── RDV en arrière-plan (non bloquant) ────────────────────────
    (async () => {
        try {
            const results = [];
            const today = new Date();
            const dates = Array.from({ length: 14 }, (_, i) => {
                const d = new Date(today);
                d.setDate(today.getDate() - 7 + i);
                return d.toISOString().split("T")[0];
            });
            const responses = await Promise.allSettled(
                dates.map((dateStr) =>
                    fetch(`/api/appointments?date=${dateStr}`, {
                        headers: h,
                    }).then((r) => r.json()),
                ),
            );
            responses.forEach((res) => {
                if (res.status === "fulfilled") {
                    (res.value.appointments ?? []).forEach((rdv) => {
                        if (!results.find((x) => x.id === rdv.id))
                            results.push(rdv);
                    });
                }
            });
            appointments.value = results.sort(
                (a, b) =>
                    new Date(b.scheduled_date) - new Date(a.scheduled_date),
            );
        } catch {}
    })();
});
</script>
