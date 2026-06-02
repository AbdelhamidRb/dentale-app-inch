<template>
    <div class="flex-1 min-h-0 overflow-y-auto bg-slate-50 px-3 sm:px-5 py-4 pb-24 lg:pb-6">

        <!-- ── En-tête ─────────────────────────────────────────────── -->
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-lg font-bold text-slate-800">Tableau de bord</h1>
                <p class="text-xs text-slate-400 mt-0.5 capitalize">{{ todayLabel }}</p>
            </div>
            <button @click="fetchDashboard" :disabled="loading"
                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-500
                       border border-slate-200 rounded-lg hover:bg-white transition-colors disabled:opacity-50">
                <RefreshCw :class="['w-3.5 h-3.5', loading ? 'animate-spin' : '']" />
                <span class="hidden sm:inline">Actualiser</span>
            </button>
        </div>

        <!-- ── Skeleton ────────────────────────────────────────────── -->
        <template v-if="loading">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div v-for="i in 4" :key="i" class="h-28 bg-white rounded-2xl animate-pulse border border-slate-100"/>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-4">
                <div class="lg:col-span-2 h-52 bg-white rounded-2xl animate-pulse border border-slate-100"/>
                <div class="h-52 bg-white rounded-2xl animate-pulse border border-slate-100"/>
            </div>
            <div class="h-44 bg-white rounded-2xl animate-pulse border border-slate-100"/>
            <div class="h-48 bg-white rounded-2xl animate-pulse border border-slate-100"/>
        </template>

        <template v-else-if="data">

            <!-- ═══════════════════════════════════════════════════════
                 BLOC 1 — 4 cartes stats (2×2 mobile, 4×1 desktop)
            ═══════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">

                <!-- Nouveaux patients -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between min-h-[6.5rem]">
                    <div class="flex items-start justify-between mb-2">
                        <div class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                            <UserPlus class="w-5 h-5 text-emerald-600" />
                        </div>
                        <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                            Ce mois
                        </span>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-800">{{ data.patients.new_this_month }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Nouveaux patients</p>
                        <p class="text-[11px] text-slate-300 mt-0.5">{{ data.patients.total }} au total</p>
                    </div>
                </div>

                <!-- Encaissé aujourd'hui -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between min-h-[6.5rem]">
                    <div class="flex items-start justify-between mb-2">
                        <div class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                            <Banknote class="w-5 h-5 text-emerald-600" />
                        </div>
                        <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                            {{ data.payments_today.count }} pmt{{ data.payments_today.count !== 1 ? 's' : '' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-slate-800 leading-tight">
                            {{ fmt(data.payments_today.amount) }}
                            <span class="text-xs font-normal text-slate-400">MAD</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">Encaissé aujourd'hui</p>
                    </div>
                </div>

                <!-- Encaissé ce mois -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between min-h-[6.5rem]">
                    <div class="flex items-start justify-between mb-2">
                        <div class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
                            <TrendingUp class="w-5 h-5 text-blue-600" />
                        </div>
                        <span v-if="data.revenue.variation_pct !== null"
                              :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full',
                                       data.revenue.variation_pct >= 0
                                         ? 'text-emerald-600 bg-emerald-50'
                                         : 'text-red-500 bg-red-50']">
                            {{ data.revenue.variation_pct >= 0 ? '▲' : '▼' }} {{ Math.abs(data.revenue.variation_pct) }}%
                        </span>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-slate-800 leading-tight">
                            {{ fmt(data.revenue.month) }}
                            <span class="text-xs font-normal text-slate-400">MAD</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">Encaissé ce mois</p>
                        <p class="text-[11px] text-slate-300 mt-0.5">Préc. : {{ fmt(data.revenue.prev_month) }}</p>
                    </div>
                </div>

                <!-- Reste à encaisser -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between min-h-[6.5rem]">
                    <div class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center mb-2 shrink-0">
                        <AlertCircle class="w-5 h-5 text-red-500" />
                    </div>
                    <div>
                        <p class="text-xl font-bold text-red-600 leading-tight">
                            {{ fmt(data.revenue.unpaid) }}
                            <span class="text-xs font-normal text-slate-400">MAD</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">Reste à encaisser</p>
                        <p class="text-[11px] text-slate-300 mt-0.5">Consultations non soldées</p>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════
                 BLOC 2 — Graphique + Absentéisme
            ═══════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-4">

                <!-- Graphique revenus -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 p-4 sm:p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-0.5">Revenus — 6 derniers mois</h3>
                    <p class="text-xs text-slate-400 mb-4">Encaissements par mois (MAD)</p>

                    <div class="flex items-end gap-1.5 sm:gap-2.5" style="height:120px">
                        <div v-for="m in data.monthly_chart" :key="m.month"
                             class="relative flex-1 flex flex-col items-center gap-1 group cursor-default">
                            <!-- Tooltip -->
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 hidden group-hover:flex
                                        bg-slate-800 text-white text-[10px] font-medium px-2 py-1 rounded-lg
                                        whitespace-nowrap z-10 shadow-lg pointer-events-none">
                                {{ m.revenue > 0 ? fmt(m.revenue) + ' MAD' : 'Aucun encaissement' }}
                            </div>
                            <!-- Valeur au-dessus -->
                            <span class="text-[10px] font-medium leading-none"
                                  :class="m.revenue > 0 ? (m.isCurrent ? 'text-blue-600' : 'text-slate-400') : 'text-slate-200'">
                                {{ m.revenue > 0 ? fmtK(m.revenue) : '–' }}
                            </span>
                            <!-- Barre -->
                            <div class="w-full flex items-end justify-center" style="height:80px">
                                <div class="w-full rounded-t-lg transition-all duration-500"
                                     :class="m.isCurrent ? 'bg-blue-500' : 'bg-blue-200 group-hover:bg-blue-300'"
                                     :style="`height:${barH(m.revenue)}px; min-height:${m.revenue > 0 ? 4 : 0}px`"/>
                            </div>
                            <!-- Label mois -->
                            <span class="text-[10px] font-medium leading-none w-full text-center"
                                  :class="m.isCurrent ? 'text-blue-600' : 'text-slate-400'">
                                {{ m.month }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-slate-50">
                        <span class="flex items-center gap-1.5 text-xs text-slate-500">
                            <span class="w-2.5 h-2.5 rounded-sm bg-blue-500 inline-block"/>Mois courant
                        </span>
                        <span class="flex items-center gap-1.5 text-xs text-slate-500">
                            <span class="w-2.5 h-2.5 rounded-sm bg-blue-200 inline-block"/>Mois précédents
                        </span>
                    </div>
                </div>

                <!-- Absentéisme -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Absentéisme ce mois</h3>

                    <div class="flex lg:flex-col items-center lg:items-center gap-4 lg:gap-0">
                        <!-- Gauge -->
                        <div class="relative w-20 h-20 lg:w-24 lg:h-24 shrink-0 lg:mb-4">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#fca5a5" stroke-width="3.2"/>
                                <circle cx="18" cy="18" r="15.9" fill="none"
                                        stroke="#34d399" stroke-width="3.2"
                                        stroke-dasharray="100"
                                        :stroke-dashoffset="Math.min(data.absence_rate.rate, 100)"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-lg font-bold text-slate-800">{{ data.absence_rate.rate }}%</span>
                            </div>
                        </div>

                        <!-- Barres -->
                        <div class="flex-1 lg:w-full space-y-2.5">
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-500">Absents / annulés</span>
                                    <span class="font-semibold text-red-500">{{ data.absence_rate.absences }}</span>
                                </div>
                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-400 rounded-full transition-all"
                                         :style="`width:${pct(data.absence_rate.absences, data.absence_rate.total)}%`"/>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-500">RDV honorés</span>
                                    <span class="font-semibold text-emerald-600">
                                        {{ data.absence_rate.total - data.absence_rate.absences }}
                                    </span>
                                </div>
                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-400 rounded-full transition-all"
                                         :style="`width:${pct(data.absence_rate.total - data.absence_rate.absences, data.absence_rate.total)}%`"/>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400">Sur {{ data.absence_rate.total }} RDV ce mois</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════
                 BLOC 3 — Top 5 actes par revenu
            ═══════════════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5 mb-4">
                <h3 class="text-sm font-semibold text-slate-700 mb-0.5">Top 5 actes — Revenus</h3>
                <p class="text-xs text-slate-400 mb-4">Consultations en cours et terminées</p>

                <div v-if="!data.top_acts.length" class="text-xs text-slate-400 text-center py-6">
                    Aucun acte enregistré
                </div>
                <div v-else class="space-y-3.5">
                    <div v-for="(act, i) in data.top_acts" :key="act.code" class="flex items-center gap-3">
                        <span class="text-xs font-bold text-slate-300 w-4 shrink-0 text-right">#{{ i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-baseline mb-1.5">
                                <span class="text-xs font-medium text-slate-700 truncate pr-2">{{ act.name }}</span>
                                <span class="text-xs shrink-0 tabular-nums">
                                    <span class="font-semibold text-slate-800">{{ fmt(act.revenue) }}</span>
                                    <span class="text-slate-400"> MAD</span>
                                    <span class="text-slate-300 mx-1">·</span>
                                    <span class="text-slate-400">{{ act.count }}x</span>
                                </span>
                            </div>
                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                     :class="i === 0 ? 'bg-blue-500' : 'bg-blue-300'"
                                     :style="`width:${topActPct(act.revenue)}%`"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════
                 BLOC 4 — Répartition patients
            ═══════════════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl border border-slate-100 p-4 sm:p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-0.5">Répartition patients</h3>
                <p class="text-xs text-slate-400 mb-4">{{ data.demographics.total }} patients actifs au total</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-8">
                    <!-- Par sexe -->
                    <div>
                        <p class="text-xs font-medium text-slate-500 mb-3">Par sexe</p>
                        <div class="space-y-3">
                            <div v-for="g in genderItems" :key="g.label">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-600 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full inline-block" :class="g.dot"/>
                                        {{ g.label }}
                                    </span>
                                    <span class="font-semibold text-slate-700">
                                        {{ g.value }}
                                        <span class="text-slate-400 font-normal ml-1">{{ g.pct }}%</span>
                                    </span>
                                </div>
                                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :class="g.bar" :style="`width:${g.pct}%`"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Par âge -->
                    <div>
                        <p class="text-xs font-medium text-slate-500 mb-3">Par tranche d'âge</p>
                        <div class="space-y-3">
                            <div v-for="a in ageItems" :key="a.label">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-600">{{ a.label }}</span>
                                    <span class="font-semibold text-slate-700">
                                        {{ a.value }}
                                        <span class="text-slate-400 font-normal ml-1">{{ a.pct }}%</span>
                                    </span>
                                </div>
                                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-violet-400 rounded-full transition-all duration-500"
                                         :style="`width:${a.pct}%`"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </template>

        <!-- Erreur -->
        <div v-else-if="error" class="flex items-center justify-center h-48 text-sm text-red-500">
            {{ error }}
        </div>

    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { RefreshCw, UserPlus, Banknote, TrendingUp, AlertCircle } from 'lucide-vue-next';
import { dashboardVersion } from '../stores/dashboard';

const data    = ref(null);
const loading = ref(false);
const error   = ref(null);

const todayLabel = computed(() =>
    new Date().toLocaleDateString("fr-FR", {
        weekday: "long", day: "numeric", month: "long", year: "numeric",
    })
);

async function fetchDashboard() {
    loading.value = true;
    error.value   = null;
    try {
        const res = await fetch("/api/dashboard", {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
                Accept: "application/json",
            },
        });
        if (!res.ok) throw new Error("Erreur serveur");
        const json = await res.json();
        json.monthly_chart = json.monthly_chart.map((m, i) => ({
            ...m,
            isCurrent: i === json.monthly_chart.length - 1,
        }));
        data.value = json;
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

const genderItems = computed(() => {
    if (!data.value) return [];
    const g = data.value.demographics.gender;
    const total = data.value.demographics.total || 1;
    return [
        { label: "Hommes", value: g.M, pct: Math.round(g.M / total * 100), dot: "bg-blue-400", bar: "bg-blue-400" },
        { label: "Femmes", value: g.F, pct: Math.round(g.F / total * 100), dot: "bg-pink-400", bar: "bg-pink-400" },
    ];
});

const ageItems = computed(() => {
    if (!data.value) return [];
    const a = data.value.demographics.age_groups;
    const total = data.value.demographics.total || 1;
    return [
        { label: "0 – 18 ans",  value: a["0-18"],  pct: Math.round(a["0-18"]  / total * 100) },
        { label: "18 – 30 ans", value: a["18-30"], pct: Math.round(a["18-30"] / total * 100) },
        { label: "30 – 50 ans", value: a["30-50"], pct: Math.round(a["30-50"] / total * 100) },
        { label: "50 ans +",    value: a["50+"],   pct: Math.round(a["50+"]   / total * 100) },
    ];
});

function fmt(val) { return Number(val ?? 0).toLocaleString("fr-FR"); }

function fmtK(val) {
    if (val >= 1000) return (val / 1000).toFixed(1) + "k";
    return val > 0 ? String(Math.round(val)) : "0";
}

function pct(val, total) {
    if (!total) return 0;
    return Math.round((val / total) * 100);
}

function topActPct(val) {
    if (!data.value?.top_acts?.length) return 0;
    const max = data.value.top_acts[0].revenue || 1;
    return Math.round((val / max) * 100);
}

function barH(val) {
    if (!data.value) return 0;
    const max = Math.max(...data.value.monthly_chart.map(m => m.revenue), 1);
    return Math.round((val / max) * 80);
}

onMounted(() => fetchDashboard());
watch(dashboardVersion, () => fetchDashboard());
</script>
