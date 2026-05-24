<template>
    <div class="h-full overflow-y-auto bg-slate-50 px-3 sm:px-6 py-4 sm:py-6">

        <!-- ── En-tête ────────────────────────────────────────────── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Tableau de bord</h1>
                <p class="text-sm text-slate-400 mt-0.5 capitalize">{{ todayLabel }}</p>
            </div>
            <button
                @click="fetchDashboard"
                :disabled="loading"
                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-500
                       border border-slate-200 rounded-lg hover:bg-white transition-colors disabled:opacity-50"
            >
                <svg :class="['w-3.5 h-3.5', loading ? 'animate-spin' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0
                             0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Actualiser
            </button>
        </div>

        <!-- ── Skeleton ───────────────────────────────────────────── -->
        <template v-if="loading">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div v-for="i in 4" :key="i" class="h-28 bg-white rounded-2xl animate-pulse border border-slate-100"/>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div v-for="i in 4" :key="i" class="h-28 bg-white rounded-2xl animate-pulse border border-slate-100"/>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="i in 3" :key="i" class="h-52 bg-white rounded-2xl animate-pulse border border-slate-100"/>
            </div>
        </template>

        <template v-else-if="data">

            <!-- ══════════════════════════════════════════════════════
                 BLOC 1 — Vue d'aujourd'hui
            ══════════════════════════════════════════════════════ -->
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-2">Aujourd'hui</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

                <!-- RDV du jour -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5
                                         a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span v-if="data.today.rdv.absent > 0"
                              class="text-[11px] font-medium text-red-500 bg-red-50 px-2 py-0.5 rounded-full">
                            {{ data.today.rdv.absent }} absent{{ data.today.rdv.absent > 1 ? 's' : '' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-800">{{ data.today.rdv.total }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">RDV aujourd'hui</p>
                        <!-- mini barre -->
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden flex mt-2">
                            <div class="bg-emerald-400 h-full" :style="`width:${pct(data.today.rdv.termine, data.today.rdv.total)}%`"/>
                            <div class="bg-blue-300 h-full"    :style="`width:${pct(data.today.rdv.planifie, data.today.rdv.total)}%`"/>
                            <div class="bg-red-300 h-full"     :style="`width:${pct(data.today.rdv.absent, data.today.rdv.total)}%`"/>
                        </div>
                        <div class="flex gap-3 mt-1.5">
                            <span class="text-[10px] text-emerald-600">✓ {{ data.today.rdv.termine }}</span>
                            <span class="text-[10px] text-blue-500">◷ {{ data.today.rdv.planifie }}</span>
                            <span class="text-[10px] text-red-400">✗ {{ data.today.rdv.absent }}</span>
                        </div>
                    </div>
                </div>

                <!-- Prochain RDV -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between">
                    <div class="w-9 h-9 bg-violet-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div v-if="data.today.prochain_rdv">
                        <p class="text-2xl font-bold text-slate-800">{{ data.today.prochain_rdv.start_time }}</p>
                        <p class="text-xs font-medium text-slate-600 mt-0.5 truncate">{{ data.today.prochain_rdv.patient }}</p>
                        <p class="text-xs text-slate-400">Prochain rendez-vous</p>
                    </div>
                    <div v-else>
                        <p class="text-sm font-medium text-slate-400 italic">Aucun RDV à venir</p>
                        <p class="text-xs text-slate-400 mt-0.5">Prochain rendez-vous</p>
                    </div>
                </div>

                <!-- Consultations en cours -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between">
                    <div class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                                     a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-amber-600">{{ data.today.consult_en_cours }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Consultations en cours</p>
                        <p class="text-[11px] text-slate-300 mt-1">Traitements multi-séances actifs</p>
                    </div>
                </div>

                <!-- Nouveaux patients ce mois -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3
                                         20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <span v-if="data.today.new_patients > 0"
                              class="text-[11px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                            +{{ data.today.new_patients }} auj.
                        </span>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-800">{{ data.patients.new_this_month }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Nouveaux patients ce mois</p>
                        <p class="text-[11px] text-slate-300 mt-1">{{ data.patients.total }} patients actifs au total</p>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════
                 BLOC 2 — Finances
            ══════════════════════════════════════════════════════ -->
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-2">Finances</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

                <!-- CA mois -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0
                                         002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2
                                         2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2
                                         2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <span v-if="data.revenue.variation_pct !== null"
                              :class="['text-[11px] font-semibold px-2 py-0.5 rounded-full',
                                       data.revenue.variation_pct >= 0
                                         ? 'text-emerald-600 bg-emerald-50'
                                         : 'text-red-500 bg-red-50']">
                            {{ data.revenue.variation_pct >= 0 ? '▲' : '▼' }}
                            {{ Math.abs(data.revenue.variation_pct) }}%
                        </span>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-800">
                            {{ fmt(data.revenue.month) }}
                            <span class="text-sm font-normal text-slate-400">MAD</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">Encaissé ce mois</p>
                        <p class="text-[11px] text-slate-300 mt-1">
                            Mois préc. : {{ fmt(data.revenue.prev_month) }} MAD
                        </p>
                    </div>
                </div>

                <!-- CA consultations mois -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between">
                    <div class="w-9 h-9 bg-indigo-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                                     a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-800">
                            {{ fmt(data.revenue.ca_consult_month) }}
                            <span class="text-sm font-normal text-slate-400">MAD</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">CA consultations ce mois</p>
                        <p class="text-[11px] text-slate-300 mt-1">Consultations terminées</p>
                    </div>
                </div>

                <!-- Encaissé aujourd'hui -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between">
                    <div class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2
                                     4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2
                                     2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-800">
                            {{ fmt(data.revenue.day) }}
                            <span class="text-sm font-normal text-slate-400">MAD</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">Encaissé aujourd'hui</p>
                        <p class="text-[11px] text-slate-300 mt-1">Semaine : {{ fmt(data.revenue.week) }} MAD</p>
                    </div>
                </div>

                <!-- Impayés -->
                <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col justify-between">
                    <div class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732
                                     4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-600">
                            {{ fmt(data.revenue.unpaid) }}
                            <span class="text-sm font-normal text-slate-400">MAD</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">Impayés cumulés</p>
                        <p class="text-[11px] text-slate-300 mt-1">Consultations non soldées</p>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════
                 BLOC 3 — Graphique CA + Top actes + Absentéisme
            ══════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

                <!-- CA 6 mois -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5 md:col-span-2">
                    <h3 class="text-sm font-semibold text-slate-700 mb-1">Revenus — 6 derniers mois</h3>
                    <p class="text-xs text-slate-400 mb-4">Encaissements (bleu) vs CA consultations (violet)</p>
                    <div class="flex items-end gap-2" style="height:110px">
                        <div v-for="m in data.monthly_chart" :key="m.month"
                             class="flex-1 flex flex-col items-center gap-0.5">
                            <!-- valeur au-dessus -->
                            <span class="text-[9px] text-slate-400 leading-none">{{ fmtK(m.revenue) }}</span>
                            <!-- groupe de 2 barres -->
                            <div class="w-full flex items-end gap-0.5" style="height:80px">
                                <div class="flex-1 bg-blue-500 rounded-t-sm transition-all opacity-80"
                                     :style="`height:${barH(m.revenue)}px`"/>
                                <div class="flex-1 bg-violet-400 rounded-t-sm transition-all opacity-70"
                                     :style="`height:${barH(m.ca)}px`"/>
                            </div>
                            <span class="text-[9px] text-slate-400 capitalize leading-none">{{ m.month }}</span>
                        </div>
                    </div>
                </div>

                <!-- Top 5 actes -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Top 5 traitements</h3>
                    <div class="space-y-3">
                        <div v-for="(act, i) in data.top_acts" :key="i" class="flex items-center gap-3">
                            <span class="w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center shrink-0"
                                  :class="topActBadge(i)">{{ i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center mb-0.5">
                                    <p class="text-xs font-medium text-slate-700 truncate">{{ act.name }}</p>
                                    <span class="text-xs text-slate-400 shrink-0 ml-1">{{ act.count }}×</span>
                                </div>
                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all" :class="topActBar(i)"
                                         :style="`width:${pct(act.count, data.top_acts[0]?.count || 1)}%`"/>
                                </div>
                            </div>
                        </div>
                        <p v-if="!data.top_acts.length" class="text-xs text-slate-400 italic text-center py-2">
                            Aucun acte enregistré
                        </p>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════
                 BLOC 4 — Absentéisme + Démographie
            ══════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Absentéisme -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Absentéisme ce mois</h3>
                    <div class="flex items-center gap-6">
                        <!-- Gauge circulaire simple -->
                        <div class="relative w-24 h-24 shrink-0">
                            <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="3"/>
                                <circle cx="18" cy="18" r="15.9" fill="none"
                                        :stroke="data.absence_rate.rate > 20 ? '#f87171' : data.absence_rate.rate > 10 ? '#fb923c' : '#34d399'"
                                        stroke-width="3"
                                        stroke-dasharray="100"
                                        :stroke-dashoffset="100 - data.absence_rate.rate"
                                        stroke-linecap="round"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-xl font-bold text-slate-800">{{ data.absence_rate.rate }}%</span>
                            </div>
                        </div>
                        <div class="space-y-3 flex-1">
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-500">Absents (NO_SHOW)</span>
                                    <span class="font-semibold text-red-500">{{ data.absence_rate.absences }}</span>
                                </div>
                                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-400 rounded-full"
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
                                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-400 rounded-full"
                                         :style="`width:${pct(data.absence_rate.total - data.absence_rate.absences, data.absence_rate.total)}%`"/>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                Sur {{ data.absence_rate.total }} RDV ce mois
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Démographie -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">
                        Répartition patients
                        <span class="text-xs text-slate-400 font-normal ml-1">({{ data.demographics.total }} actifs)</span>
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-400 mb-2">Par sexe</p>
                            <div class="space-y-2">
                                <div v-for="g in genderItems" :key="g.label">
                                    <div class="flex justify-between text-xs mb-0.5">
                                        <span class="text-slate-600">{{ g.label }}</span>
                                        <span class="font-medium text-slate-700">
                                            {{ g.value }}
                                            <span class="text-slate-400 font-normal">({{ g.pct }}%)</span>
                                        </span>
                                    </div>
                                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full" :class="g.color" :style="`width:${g.pct}%`"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-2">Par âge</p>
                            <div class="space-y-2">
                                <div v-for="a in ageItems" :key="a.label">
                                    <div class="flex justify-between text-xs mb-0.5">
                                        <span class="text-slate-600">{{ a.label }}</span>
                                        <span class="font-medium text-slate-700">
                                            {{ a.value }}
                                            <span class="text-slate-400 font-normal">({{ a.pct }}%)</span>
                                        </span>
                                    </div>
                                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-violet-400 rounded-full" :style="`width:${a.pct}%`"/>
                                    </div>
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
import { ref, computed, onMounted } from "vue";

const data    = ref(null);
const loading = ref(false);
const error   = ref(null);

const todayLabel = computed(() =>
    new Date().toLocaleDateString("fr-FR", {
        weekday: "long", day: "numeric", month: "long", year: "numeric",
    })
);

// ─── Fetch ────────────────────────────────────────────────────────
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
        data.value = await res.json();
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

// ─── Démographie ──────────────────────────────────────────────────
const genderItems = computed(() => {
    if (!data.value) return [];
    const g = data.value.demographics.gender;
    const total = data.value.demographics.total || 1;
    return [
        { label: "Hommes", value: g.M, pct: Math.round(g.M / total * 100), color: "bg-blue-400" },
        { label: "Femmes", value: g.F, pct: Math.round(g.F / total * 100), color: "bg-pink-400" },
    ];
});

const ageItems = computed(() => {
    if (!data.value) return [];
    const a = data.value.demographics.age_groups;
    const total = data.value.demographics.total || 1;
    return [
        { label: "0-18 ans",  value: a["0-18"],  pct: Math.round(a["0-18"]  / total * 100) },
        { label: "18-30 ans", value: a["18-30"], pct: Math.round(a["18-30"] / total * 100) },
        { label: "30-50 ans", value: a["30-50"], pct: Math.round(a["30-50"] / total * 100) },
        { label: "50+ ans",   value: a["50+"],   pct: Math.round(a["50+"]   / total * 100) },
    ];
});

// ─── Helpers ──────────────────────────────────────────────────────
function fmt(val) {
    return Number(val ?? 0).toLocaleString("fr-FR");
}

function fmtK(val) {
    if (val >= 1000) return (val / 1000).toFixed(1) + "k";
    return val > 0 ? String(Math.round(val)) : "0";
}

function pct(val, total) {
    if (!total) return 0;
    return Math.round((val / total) * 100);
}

function barH(val) {
    if (!data.value) return 0;
    const all = data.value.monthly_chart.flatMap(m => [m.revenue, m.ca]);
    const max = Math.max(...all, 1);
    return Math.round((val / max) * 80);
}

const TOP_BADGES = [
    "bg-amber-100 text-amber-700",
    "bg-slate-100 text-slate-600",
    "bg-orange-100 text-orange-600",
    "bg-blue-50 text-blue-500",
    "bg-slate-50 text-slate-400",
];
const TOP_BARS = ["bg-amber-400", "bg-slate-400", "bg-orange-300", "bg-blue-300", "bg-slate-200"];

function topActBadge(i) { return TOP_BADGES[i] ?? "bg-slate-50 text-slate-400"; }
function topActBar(i)   { return TOP_BARS[i]   ?? "bg-slate-200"; }

onMounted(() => fetchDashboard());
</script>
