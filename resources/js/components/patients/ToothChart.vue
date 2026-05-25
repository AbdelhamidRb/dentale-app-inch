<template>
    <div class="p-4">

        <!-- ── Loading ───────────────────────────────────────────── -->
        <div v-if="loading" class="h-40 flex items-center justify-center">
            <div class="w-6 h-6 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"/>
        </div>

        <template v-else>
            <!-- ── Résumé ─────────────────────────────────────────── -->
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs text-slate-500">
                    <span class="font-semibold text-slate-700">{{ treatedCount }}</span>
                    dent{{ treatedCount !== 1 ? 's' : '' }} traitée{{ treatedCount !== 1 ? 's' : '' }}
                    <span v-if="grandTotal > 0">
                        · Total :
                        <span class="font-semibold text-slate-700">
                            {{ grandTotal.toLocaleString('fr-FR') }} MAD
                        </span>
                    </span>
                </p>
            </div>

            <!-- ── Aperçu miniature ───────────────────────────────── -->
            <div class="flex justify-center">
            <div class="relative bg-white rounded-2xl border border-slate-100 overflow-hidden w-full"
                 style="aspect-ratio: 3027 / 4850; max-height: 340px; max-width: calc(340px * 3027 / 4850);">

                <div class="w-full h-full pointer-events-none select-none opacity-90">
                    <ConsultationToothChart
                        mode="view"
                        :acts-by-tooth="actsByTooth"
                        :hide-legend="true"
                    />
                </div>

                <!-- Overlay gradient + bouton -->
                <div class="absolute inset-0 bg-gradient-to-t from-white/70 via-transparent to-transparent
                            flex flex-col items-center justify-end pb-5 gap-2">
                    <p class="text-[10px] text-slate-400">Cliquez pour interagir avec le schéma</p>
                    <button
                        @click="openFullscreen"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-xs font-semibold rounded-xl shadow-lg transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                        Ouvrir le schéma dentaire
                    </button>
                </div>
            </div>
            </div>
        </template>

        <!-- ════════════════════════════════════════════════════════
             Modal plein écran — 2 colonnes
        ═══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showFullscreen"
                     class="fixed inset-0 bg-black/60 z-[300] flex items-center justify-center p-4"
                     @click.self="showFullscreen = false">

                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="showFullscreen"
                             class="bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden"
                             style="width: min(1040px, 96vw); height: 90vh;">

                            <!-- En-tête -->
                            <div class="flex items-center justify-between px-5 py-3.5
                                        border-b border-slate-100 shrink-0">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800">Schéma dentaire</h3>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ treatedCount }} dent{{ treatedCount !== 1 ? 's' : '' }} traitée{{ treatedCount !== 1 ? 's' : '' }}
                                        <span v-if="grandTotal > 0"> · {{ grandTotal.toLocaleString('fr-FR') }} MAD</span>
                                    </p>
                                </div>
                                <button @click="showFullscreen = false"
                                        class="p-2 rounded-xl hover:bg-slate-100 text-slate-400
                                               hover:text-slate-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Corps : SVG haut + historique bas (mobile) / SVG gauche + historique droite (desktop) -->
                            <div class="flex flex-col sm:flex-row flex-1 min-h-0">

                                <!-- Colonne SVG -->
                                <div class="flex-1 min-h-0 flex items-center justify-center p-3
                                            overflow-hidden border-b sm:border-b-0 sm:border-r border-slate-100">
                                    <div style="aspect-ratio: 3027/4850; height: 100%; max-width: 100%;">
                                        <ConsultationToothChart
                                            mode="view"
                                            :acts-by-tooth="actsByTooth"
                                            :active-tooth="activeTooth"
                                            :show-popup="false"
                                            :hide-legend="true"
                                            @tooth-clicked="toggleTooth"
                                        />
                                    </div>
                                </div>

                                <!-- Colonne historique -->
                                <div class="h-56 sm:h-auto sm:w-72 shrink-0 flex flex-col overflow-hidden">

                                    <!-- Légende couleurs (toujours visible en haut) -->
                                    <div class="px-4 py-2.5 border-b border-slate-100 shrink-0 bg-slate-50/60">
                                        <div class="flex flex-wrap gap-3">
                                            <div class="flex items-center gap-1.5">
                                                <div class="w-3 h-3 rounded-sm" style="background:#f8f6f0;border:1.5px solid #cbd5e1"></div>
                                                <span class="text-[11px] text-slate-400">Sans acte</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <div class="w-3 h-3 rounded-sm bg-blue-200 border border-blue-400"></div>
                                                <span class="text-[11px] text-slate-400">1 acte</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <div class="w-3 h-3 rounded-sm bg-orange-200 border border-orange-400"></div>
                                                <span class="text-[11px] text-slate-400">Plusieurs actes</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- État vide — aucune dent sélectionnée -->
                                    <div v-if="!activeTooth"
                                         class="flex-1 flex flex-col items-center justify-center gap-3 p-6 text-center">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                      d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-500">Sélectionnez une dent</p>
                                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                                Cliquez sur une dent<br>pour voir son historique
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Historique de la dent sélectionnée -->
                                    <template v-else>
                                        <!-- En-tête dent -->
                                        <div class="px-4 py-3 border-b border-slate-100 shrink-0 bg-slate-50/50">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                                                    <span class="text-violet-700 text-sm font-bold">{{ activeTooth }}</span>
                                                </div>
                                                <div class="min-w-0">
                                                    <h4 class="text-sm font-semibold text-slate-800">Dent {{ activeTooth }}</h4>
                                                    <p class="text-xs text-slate-400">
                                                        {{ currentActs.length }} acte{{ currentActs.length !== 1 ? 's' : '' }}
                                                        <span v-if="toothTotal > 0"> · {{ toothTotal.toLocaleString('fr-FR') }} MAD</span>
                                                    </p>
                                                </div>
                                                <button @click="activeTooth = null"
                                                        class="ml-auto p-1 rounded-lg hover:bg-slate-100 text-slate-300
                                                               hover:text-slate-500 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Liste des actes -->
                                        <div class="flex-1 overflow-y-auto p-3 space-y-2">
                                            <div v-if="!currentActs.length"
                                                 class="flex flex-col items-center justify-center py-10 gap-2">
                                                <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                                <p class="text-xs text-slate-400 text-center">Aucun acte<br>enregistré</p>
                                            </div>

                                            <div v-else v-for="(act, i) in currentActs" :key="i"
                                                 class="p-3 bg-white rounded-xl border border-slate-100 shadow-sm">
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <p class="text-xs font-semibold text-slate-700 leading-tight">
                                                        {{ act.catalog_act?.name }}
                                                    </p>
                                                    <span class="text-xs font-bold text-blue-600 shrink-0">
                                                        {{ Number(act.price || 0).toLocaleString('fr-FR') }} MAD
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-slate-400">{{ act.date }}</p>
                                                <p v-if="act.notes"
                                                   class="text-[11px] text-slate-500 mt-1 italic leading-tight">
                                                    {{ act.notes }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Pied : total -->
                                        <div v-if="currentActs.length > 1"
                                             class="px-4 py-3 border-t border-slate-100 shrink-0 bg-slate-50/50">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-slate-500">Total dent {{ activeTooth }}</span>
                                                <span class="text-sm font-bold text-slate-700">
                                                    {{ toothTotal.toLocaleString('fr-FR') }} MAD
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import ConsultationToothChart from '../consultations/ToothChart.vue';

// ─── Props ────────────────────────────────────────────────────────
const props = defineProps({
    patientId: { type: Number, required: true },
});

// ─── État ─────────────────────────────────────────────────────────
const loading        = ref(true);
const rawData        = ref({});
const showFullscreen = ref(false);
const activeTooth    = ref(null);

// ─── Format API → format ToothChart consultation ──────────────────
const actsByTooth = computed(() => {
    const result = {};
    for (const [tooth, acts] of Object.entries(rawData.value)) {
        result[tooth] = acts.map(a => ({
            catalog_act: { name: a.act_name, code: a.act_code },
            date:        a.date,
            price:       a.price,
            notes:       a.notes,
        }));
    }
    return result;
});

// ─── Stats globales ───────────────────────────────────────────────
const treatedCount = computed(() =>
    Object.keys(rawData.value).filter(k => rawData.value[k]?.length).length
);
const grandTotal = computed(() =>
    Object.values(rawData.value).flat().reduce((s, a) => s + (a.price ?? 0), 0)
);

// ─── Dent active ─────────────────────────────────────────────────
const currentActs = computed(() => actsByTooth.value[activeTooth.value] ?? []);
const toothTotal  = computed(() =>
    currentActs.value.reduce((s, a) => s + Number(a.price || 0), 0)
);

function toggleTooth(id) {
    activeTooth.value = activeTooth.value === id ? null : id;
}

function openFullscreen() {
    showFullscreen.value = true;
}

// Réinitialise la sélection à la fermeture
watch(showFullscreen, v => { if (!v) activeTooth.value = null; });

// ─── Fetch ────────────────────────────────────────────────────────
async function fetchTeeth(id) {
    loading.value = true;
    rawData.value = {};
    try {
        const res = await fetch(`/api/patients/${id}/teeth`, {
            headers: {
                Authorization: `Bearer ${localStorage.getItem('token')}`,
                Accept: 'application/json',
            },
        });
        if (!res.ok) return;
        rawData.value = (await res.json()).teeth ?? {};
    } catch {}
    finally { loading.value = false; }
}

onMounted(() => fetchTeeth(props.patientId));
watch(() => props.patientId, id => { if (id) fetchTeeth(id); });
</script>
