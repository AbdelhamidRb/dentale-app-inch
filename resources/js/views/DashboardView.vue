<!-- resources/js/views/DashboardView.vue -->
<template>
    <div class="h-full overflow-y-auto bg-slate-50 px-6 py-6">
        <!-- ── En-tête ──────────────────────────────────────────── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">
                    Tableau de bord
                </h1>
                <p class="text-sm text-slate-400 mt-0.5">{{ todayLabel }}</p>
            </div>
            <button
                @click="fetchDashboard"
                :disabled="loading"
                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-500 border border-slate-200 rounded-lg hover:bg-white transition-colors disabled:opacity-50"
            >
                <svg
                    :class="['w-3.5 h-3.5', loading ? 'animate-spin' : '']"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9
                             m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
                Actualiser
            </button>
        </div>

        <!-- Skeleton -->
        <div v-if="loading" class="space-y-4">
            <div class="grid grid-cols-4 gap-4">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="h-24 bg-white rounded-2xl animate-pulse border border-slate-100"
                ></div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="h-48 bg-white rounded-2xl animate-pulse border border-slate-100"
                ></div>
            </div>
        </div>

        <template v-else-if="data">
            <!-- ══════════════════════════════════════════════════
                 SECTION FINANCES
            ═══════════════════════════════════════════════════ -->
            <p
                class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2"
            >
                Finances
            </p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <!-- Recettes encaissées du jour -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4">
                    <div
                        class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center mb-3"
                    >
                        <svg
                            class="w-5 h-5 text-emerald-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">
                        {{ fmt(data.revenue.day) }}
                        <span class="text-sm font-normal text-slate-400"
                            >MAD</span
                        >
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Encaissé aujourd'hui
                    </p>
                </div>

                <!-- CA consultations du jour -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4">
                    <div
                        class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center mb-3"
                    >
                        <svg
                            class="w-5 h-5 text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                                     a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">
                        {{ fmt(data.today.ca_consult_today) }}
                        <span class="text-sm font-normal text-slate-400"
                            >MAD</span
                        >
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        CA consultations du jour
                    </p>
                </div>

                <!-- Impayés -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4">
                    <div
                        class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center mb-3"
                    >
                        <svg
                            class="w-5 h-5 text-red-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667
                                     1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464
                                     0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-red-600">
                        {{ fmt(data.revenue.unpaid) }}
                        <span class="text-sm font-normal text-slate-400"
                            >MAD</span
                        >
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">Impayés cumulés</p>
                </div>

                <!-- Taux absentéisme -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4">
                    <div
                        class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center mb-3"
                    >
                        <svg
                            class="w-5 h-5 text-amber-600"
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
                    </div>
                    <p class="text-2xl font-bold text-slate-800">
                        {{ data.absence_rate.rate
                        }}<span class="text-sm font-normal text-slate-400"
                            >%</span
                        >
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Absentéisme ce mois
                    </p>
                </div>
            </div>
            <!-- ══════════════════════════════════════════════════
                 SECTION PATIENTS
            ═══════════════════════════════════════════════════ -->
            <p
                class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2"
            >
                Patients
            </p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <!-- Patients aujourd'hui -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div
                            class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center"
                        >
                            <svg
                                class="w-5 h-5 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857
                                         M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                                         m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                        <span
                            class="text-xs text-emerald-600 font-medium bg-emerald-50 px-2 py-0.5 rounded-full"
                        >
                            +{{ data.today.new_patients }} nouveaux
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">
                        {{ data.today.patients }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Patients aujourd'hui
                    </p>
                </div>

                <!-- RDV terminés -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4">
                    <div
                        class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center mb-3"
                    >
                        <svg
                            class="w-5 h-5 text-emerald-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">
                        {{ data.today.rdv.termine }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        RDV terminés aujourd'hui
                    </p>
                </div>

                <!-- Consultations EN_COURS -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4">
                    <div
                        class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center mb-3"
                    >
                        <svg
                            class="w-5 h-5 text-amber-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-amber-600">
                        {{ consultations_en_cours }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Consultations en cours
                    </p>
                </div>

                <!-- Absentéisme -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4">
                    <div
                        class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center mb-3"
                    >
                        <svg
                            class="w-5 h-5 text-red-500"
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
                    </div>
                    <p class="text-2xl font-bold text-slate-800">
                        {{ data.absence_rate.rate
                        }}<span class="text-sm font-normal text-slate-400"
                            >%</span
                        >
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Absentéisme ce mois
                    </p>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════
                 LIGNE 2 — RDV du jour + CA + Top actes
            ═══════════════════════════════════════════════════ -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- RDV du jour -->
                
                <!-- RDV + Consultations + Absentéisme en liste verticale -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">
                        Activité du jour
                    </h3>
                    <div class="space-y-3">

                        <!-- RDV total -->
                        <div class="flex items-center justify-between py-2
                                    border-b border-slate-50">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                                <span class="text-sm text-slate-600">RDV total</span>
                            </div>
                            <span class="text-sm font-bold text-slate-800">
                                {{ data.today.rdv.total }}
                            </span>
                        </div>

                        <!-- RDV terminés -->
                        <div class="flex items-center justify-between py-2
                                    border-b border-slate-50">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                                <span class="text-sm text-slate-600">RDV terminés aujourd'hui</span>
                            </div>
                            <span class="text-sm font-bold text-emerald-700">
                                {{ data.today.rdv.termine }}
                            </span>
                        </div>

                        <!-- Consultations EN_COURS -->
                        <div class="flex items-center justify-between py-2
                                    border-b border-slate-50">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                                <span class="text-sm text-slate-600">Consultations en cours</span>
                            </div>
                            <span class="text-sm font-bold text-amber-600">
                                {{ consultations_en_cours }}
                            </span>
                        </div>

                        <!-- Absents -->
                        <div class="flex items-center justify-between py-2
                                    border-b border-slate-50">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-red-400"></div>
                                <span class="text-sm text-slate-600">Absents / Annulés</span>
                            </div>
                            <span class="text-sm font-bold text-red-600">
                                {{ data.today.rdv.absent }}
                            </span>
                        </div>

                        <!-- Absentéisme mois -->
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-orange-400"></div>
                                <span class="text-sm text-slate-600">
                                    Absentéisme ce mois
                                    <span class="text-xs text-slate-400">
                                        ({{ data.absence_rate.absences }}/{{ data.absence_rate.total }})
                                    </span>
                                </span>
                            </div>
                            <span class="text-sm font-bold text-orange-600">
                                {{ data.absence_rate.rate }} %
                            </span>
                        </div>

                        <!-- Barre visuelle -->
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden flex mt-1">
                            <div class="bg-emerald-400 h-full transition-all"
                                 :style="`width:${pct(data.today.rdv.termine, data.today.rdv.total)}%`"/>
                            <div class="bg-amber-400 h-full transition-all"
                                 :style="`width:${pct(data.today.rdv.planifie, data.today.rdv.total)}%`"/>
                            <div class="bg-red-300 h-full transition-all"
                                 :style="`width:${pct(data.today.rdv.absent, data.today.rdv.total)}%`"/>
                        </div>
                    </div>
                </div>

                <!-- Chiffre d'affaires -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">
                        Chiffre d'affaires
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-for="period in revenuePeriods"
                            :key="period.label"
                        >
                            <div
                                class="flex justify-between text-xs text-slate-500 mb-1"
                            >
                                <span>{{ period.label }}</span>
                                <span class="font-semibold text-slate-700">
                                    {{ fmt(period.value) }} MAD
                                </span>
                            </div>
                            <div
                                class="h-2 bg-slate-100 rounded-full overflow-hidden"
                            >
                                <div
                                    class="h-full bg-blue-500 rounded-full transition-all"
                                    :style="`width:${period.pct}%`"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 actes -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">
                        Top 5 traitements
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="(act, i) in data.top_acts"
                            :key="i"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center shrink-0"
                                :class="topActColor(i)"
                            >
                                {{ i + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <div
                                    class="flex justify-between items-center mb-0.5"
                                >
                                    <p
                                        class="text-xs font-medium text-slate-700 truncate"
                                    >
                                        {{ act.name }}
                                    </p>
                                    <span
                                        class="text-xs text-slate-500 shrink-0 ml-2"
                                    >
                                        {{ act.count }}×
                                    </span>
                                </div>
                                <div
                                    class="h-1.5 bg-slate-100 rounded-full overflow-hidden"
                                >
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="topActBarColor(i)"
                                        :style="`width:${pct(act.count, data.top_acts[0]?.count || 1)}%`"
                                    ></div>
                                </div>
                            </div>
                        </div>
                        <p
                            v-if="!data.top_acts.length"
                            class="text-xs text-slate-400 italic text-center py-2"
                        >
                            Aucun acte enregistré
                        </p>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════
                 LIGNE 3 — Graphique CA + Démographie
            ═══════════════════════════════════════════════════ -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Graphique CA 6 mois -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">
                        Revenus — 6 derniers mois
                    </h3>
                    <div class="flex items-end gap-2 h-32">
                        <div
                            v-for="m in data.monthly_chart"
                            :key="m.month"
                            class="flex-1 flex flex-col items-center gap-1"
                        >
                            <span class="text-[10px] text-slate-500">
                                {{ fmt(m.revenue) }}
                            </span>
                            <div
                                class="w-full bg-blue-100 rounded-t-md transition-all relative"
                                :style="`height:${barHeight(m.revenue)}px`"
                            >
                                <div
                                    class="absolute inset-0 bg-blue-500 rounded-t-md opacity-80"
                                ></div>
                            </div>
                            <span class="text-[10px] text-slate-400 capitalize">
                                {{ m.month }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Démographie -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">
                        Répartition patients
                        <span class="text-xs text-slate-400 font-normal ml-1">
                            ({{ data.demographics.total }} actifs)
                        </span>
                    </h3>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Sexe -->
                        <div>
                            <p class="text-xs text-slate-400 mb-2">Par sexe</p>
                            <div class="space-y-2">
                                <div v-for="g in genderItems" :key="g.label">
                                    <div
                                        class="flex justify-between text-xs mb-0.5"
                                    >
                                        <span class="text-slate-600">{{
                                            g.label
                                        }}</span>
                                        <span
                                            class="font-medium text-slate-700"
                                        >
                                            {{ g.value }}
                                            <span
                                                class="text-slate-400 font-normal"
                                            >
                                                ({{ g.pct }}%)
                                            </span>
                                        </span>
                                    </div>
                                    <div
                                        class="h-2 bg-slate-100 rounded-full overflow-hidden"
                                    >
                                        <div
                                            class="h-full rounded-full"
                                            :class="g.color"
                                            :style="`width:${g.pct}%`"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Âge -->
                        <div>
                            <p class="text-xs text-slate-400 mb-2">Par âge</p>
                            <div class="space-y-2">
                                <div v-for="a in ageItems" :key="a.label">
                                    <div
                                        class="flex justify-between text-xs mb-0.5"
                                    >
                                        <span class="text-slate-600">{{
                                            a.label
                                        }}</span>
                                        <span
                                            class="font-medium text-slate-700"
                                        >
                                            {{ a.value }}
                                            <span
                                                class="text-slate-400 font-normal"
                                            >
                                                ({{ a.pct }}%)
                                            </span>
                                        </span>
                                    </div>
                                    <div
                                        class="h-2 bg-slate-100 rounded-full overflow-hidden"
                                    >
                                        <div
                                            class="h-full bg-violet-400 rounded-full"
                                            :style="`width:${a.pct}%`"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Erreur -->
        <div
            v-else-if="error"
            class="flex items-center justify-center h-48 text-sm text-red-500"
        >
            {{ error }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";

const data = ref(null);
const consultations_en_cours = ref(0);
const loading = ref(false);
const error = ref(null);

const todayLabel = computed(() => {
    return new Date().toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
});

// ─── Fetch ────────────────────────────────────────────────────────
async function fetchDashboard() {
    loading.value = true;
    error.value = null;
    try {
        const res = await fetch("/api/dashboard", {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
                Accept: "application/json",
            },
        });
        if (!res.ok) throw new Error("Erreur serveur");
        data.value = await res.json();
        // Charge le nombre de consultations EN_COURS
        fetch("/api/consultations?status=EN_COURS", {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
                Accept: "application/json",
            },
        })
            .then((r) => r.json())
            .then((d) => {
                consultations_en_cours.value =
                    d.meta?.total ?? d.data?.length ?? 0;
            })
            .catch(() => {});
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

// ─── Computed items ───────────────────────────────────────────────
const rdvItems = computed(() => {
    if (!data.value) return [];
    const r = data.value.today.rdv;
    return [
        { label: "Total", value: r.total, dot: "bg-slate-400" },
        { label: "Terminés", value: r.termine, dot: "bg-emerald-400" },
        { label: "Planifiés", value: r.planifie, dot: "bg-amber-400" },
        { label: "Absents", value: r.absent, dot: "bg-red-400" },
    ];
});

const revenuePeriods = computed(() => {
    if (!data.value) return [];
    const r = data.value.revenue;
    const max = Math.max(r.day, r.week, r.month, 1);
    return [
        {
            label: "Journalier",
            value: r.day,
            pct: Math.round((r.day / max) * 100),
        },
        {
            label: "Hebdomadaire",
            value: r.week,
            pct: Math.round((r.week / max) * 100),
        },
        {
            label: "Mensuel",
            value: r.month,
            pct: Math.round((r.month / max) * 100),
        },
    ];
});

const genderItems = computed(() => {
    if (!data.value) return [];
    const g = data.value.demographics.gender;
    const total = data.value.demographics.total || 1;
    return [
        {
            label: "Hommes",
            value: g.M,
            pct: Math.round((g.M / total) * 100),
            color: "bg-blue-400",
        },
        {
            label: "Femmes",
            value: g.F,
            pct: Math.round((g.F / total) * 100),
            color: "bg-pink-400",
        },
    ];
});

const ageItems = computed(() => {
    if (!data.value) return [];
    const a = data.value.demographics.age_groups;
    const total = data.value.demographics.total || 1;
    return [
        {
            label: "0-18 ans",
            value: a["0-18"],
            pct: Math.round((a["0-18"] / total) * 100),
        },
        {
            label: "18-30 ans",
            value: a["18-30"],
            pct: Math.round((a["18-30"] / total) * 100),
        },
        {
            label: "30-50 ans",
            value: a["30-50"],
            pct: Math.round((a["30-50"] / total) * 100),
        },
        {
            label: "50+ ans",
            value: a["50+"],
            pct: Math.round((a["50+"] / total) * 100),
        },
    ];
});

// ─── Helpers ──────────────────────────────────────────────────────
function fmt(val) {
    return Number(val ?? 0).toLocaleString("fr-FR");
}

function pct(val, total) {
    if (!total) return 0;
    return Math.round((val / total) * 100);
}

function barHeight(val) {
    if (!data.value) return 0;
    const max = Math.max(...data.value.monthly_chart.map((m) => m.revenue), 1);
    return Math.round((val / max) * 100);
}

function topActColor(i) {
    return (
        [
            "bg-amber-100 text-amber-700",
            "bg-slate-100 text-slate-600",
            "bg-orange-100 text-orange-600",
            "bg-blue-50 text-blue-500",
            "bg-slate-50 text-slate-400",
        ][i] ?? "bg-slate-50 text-slate-400"
    );
}

function topActBarColor(i) {
    return (
        [
            "bg-amber-400",
            "bg-slate-400",
            "bg-orange-300",
            "bg-blue-300",
            "bg-slate-200",
        ][i] ?? "bg-slate-200"
    );
}

onMounted(() => fetchDashboard());
</script>
