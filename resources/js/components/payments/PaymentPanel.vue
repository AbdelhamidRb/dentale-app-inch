<!-- resources/js/components/payments/PaymentPanel.vue -->
<template>
    <Transition name="panel">
        <div
            v-if="patient"
            class="flex-1 min-h-0 flex flex-col bg-white lg:border-l border-slate-200 overflow-hidden"
        >
            <!-- ── Header ──────────────────────────────────────────── -->
            <div
                class="flex items-start justify-between px-4 py-3 border-b border-slate-100"
            >
                <!-- Bouton retour mobile -->
                <button
                    @click="emit('close')"
                    class="lg:hidden flex items-center gap-1 mr-3 shrink-0 text-blue-600 hover:text-blue-800 transition-colors py-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="text-xs font-medium">Retour</span>
                </button>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <PaymentStatusBadge :status="patient.balance_status" />
                    </div>
                    <h2 class="text-base font-semibold text-slate-800">
                        {{ patient.full_name }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ patient.phone }}
                    </p>
                </div>

                <!-- Bouton fermer desktop -->
                <button
                    @click="emit('close')"
                    class="hidden lg:flex w-8 h-8 items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                >
                    <svg
                        class="w-4 h-4"
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

            <!-- ── Solde résumé ─────────────────────────────────────── -->
            <div class="px-5 py-4 border-b border-slate-100">
                <!-- Barre de progression -->
                <div class="mb-3">
                    <div
                        class="flex justify-between text-xs text-slate-500 mb-1.5"
                    >
                        <span>Encaissé</span>
                        <span class="font-semibold">
                            {{ fmt(patient.total_paid) }} /
                            {{ fmt(patient.total_consultations) }} MAD
                        </span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="progressColor"
                            :style="`width: ${progressPct}%`"
                        ></div>
                    </div>
                </div>

                <!-- 3 montants -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="text-center p-2 bg-slate-50 rounded-xl">
                        <p class="text-sm font-bold text-slate-800">
                            {{ fmt(patient.total_consultations) }}
                        </p>
                        <p class="text-[10px] text-slate-400 mt-0.5">
                            Total MAD
                        </p>
                    </div>
                    <div class="text-center p-2 bg-emerald-50 rounded-xl">
                        <p class="text-sm font-bold text-emerald-700">
                            {{ fmt(patient.total_paid) }}
                        </p>
                        <p class="text-[10px] text-emerald-500 mt-0.5">
                            Payé MAD
                        </p>
                    </div>
                    <div
                        class="text-center p-2 rounded-xl"
                        :class="
                            patient.balance < 0
                                ? 'bg-red-50'
                                : patient.balance > 0
                                  ? 'bg-blue-50'
                                  : 'bg-slate-50'
                        "
                    >
                        <p
                            class="text-sm font-bold"
                            :class="
                                patient.balance < 0
                                    ? 'text-red-600'
                                    : patient.balance > 0
                                      ? 'text-blue-600'
                                      : 'text-slate-400'
                            "
                        >
                            {{ patient.balance > 0 ? "+" : ""
                            }}{{ fmt(patient.balance) }}
                        </p>
                        <p
                            class="text-[10px] mt-0.5"
                            :class="
                                patient.balance < 0
                                    ? 'text-red-400'
                                    : patient.balance > 0
                                      ? 'text-blue-400'
                                      : 'text-slate-400'
                            "
                        >
                            Solde MAD
                        </p>
                    </div>
                </div>
            </div>

            <!-- ── Corps scrollable ─────────────────────────────────── -->
            <div class="flex-1 overflow-y-auto pb-20 lg:pb-4">
                <!-- Loading -->
                <div
                    v-if="loadingDetail"
                    class="flex items-center justify-center h-32"
                >
                    <div
                        class="w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"
                    ></div>
                </div>

                <template v-else>
                    <!-- Consultations -->
                    <div class="px-5 py-4 border-b border-slate-100">
                        <p class="text-xs font-medium text-slate-500 mb-3">
                            Consultations
                            <span class="text-slate-300"
                                >({{
                                    patient.consultations?.length ?? 0
                                }})</span
                            >
                        </p>

                        <div
                            v-if="patient.consultations?.length"
                            class="space-y-2"
                        >
                            <div
                                v-for="c in patient.consultations"
                                :key="c.id"
                                class="p-3 bg-slate-50 rounded-xl"
                            >
                                <div
                                    class="flex items-center justify-between mb-1"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-xs font-medium text-slate-600"
                                        >
                                            #{{ c.id }}
                                        </span>
                                        <span
                                            :class="
                                                consultationStatusClass(
                                                    c.status,
                                                )
                                            "
                                            class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                                        >
                                            {{
                                                consultationStatusLabel(
                                                    c.status,
                                                )
                                            }}
                                        </span>
                                    </div>
                                    <span
                                        class="text-sm font-bold text-slate-700"
                                    >
                                        {{ fmt(c.total_price) }} MAD
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-400">
                                    {{ c.created_at }}
                                </p>
                                <div
                                    v-if="c.acts?.length"
                                    class="mt-1.5 flex flex-wrap gap-1"
                                >
                                    <span
                                        v-for="(act, i) in c.acts.slice(0, 3)"
                                        :key="i"
                                        class="text-[10px] px-1.5 py-0.5 bg-white border border-slate-200 rounded text-slate-500"
                                    >
                                        {{ act.name }}
                                        <span v-if="act.teeth?.length">
                                            ({{ act.teeth.join(", ") }})
                                        </span>
                                    </span>
                                    <span
                                        v-if="c.acts.length > 3"
                                        class="text-[10px] text-slate-400"
                                    >
                                        +{{ c.acts.length - 3 }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p
                            v-else
                            class="text-xs text-slate-400 italic text-center py-2"
                        >
                            Aucune consultation
                        </p>
                    </div>

                    <!-- Versements reçus -->
                    <div class="px-5 py-4 border-b border-slate-100">
                        <p class="text-xs font-medium text-slate-500 mb-3">
                            Versements reçus
                            <span class="text-slate-300">
                                ({{ patient.transactions?.length ?? 0 }})
                            </span>
                        </p>

                        <div v-if="localTransactions.length" class="space-y-2">
                            <div
                                v-for="t in localTransactions"
                                :key="t.id"
                                class="flex items-center justify-between p-3 bg-emerald-50 rounded-xl group"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-7 h-7 bg-emerald-100 rounded-full flex items-center justify-center shrink-0"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 text-emerald-600"
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
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-700"
                                        >
                                            {{ fmt(t.amount) }} MAD
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{ t.date }}
                                        </p>
                                        <p
                                            v-if="t.notes"
                                            class="text-xs text-slate-400 italic"
                                        >
                                            {{ t.notes }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    v-if="isDentist"
                                    @click="handleDeleteTransaction(t.id)"
                                    class="lg:opacity-0 lg:group-hover:opacity-100 transition-opacity w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50"
                                >
                                    <svg
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862
                                             a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6
                                             m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p
                            v-else
                            class="text-xs text-slate-400 italic text-center py-2"
                        >
                            Aucun versement enregistré
                        </p>
                    </div>

                    <!-- Formulaire nouveau versement -->
                    <!-- Formulaire nouveau versement — toujours visible (avance possible) -->
                    <div class="px-5 py-4">
                        <p class="text-xs font-medium text-slate-500 mb-3">
                            {{
                                patient.balance_status === "PAYÉ"
                                    ? "Ajouter une avance"
                                    : "Ajouter un versement"
                            }}
                        </p>

                        <div
                            class="space-y-3 p-4 bg-blue-50 border border-blue-100 rounded-xl"
                        >
                            <div class="grid grid-cols-2 gap-3">
                                <!-- Montant -->
                                <div>
                                    <label
                                        class="text-[10px] font-medium text-blue-600 mb-1 block"
                                    >
                                        Montant *
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model.number="newTx.amount"
                                            type="number"
                                            min="0.01"
                                            step="10"
                                            placeholder="0"
                                            class="w-full border border-blue-200 rounded-lg px-3 py-2 pr-12 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300"
                                        />
                                        <span
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400"
                                            >MAD</span
                                        >
                                    </div>
                                    <!-- Raccourci solde restant -->
                                    <button
                                        v-if="patient.balance < 0"
                                        @click="
                                            newTx.amount = Math.abs(
                                                patient.balance,
                                            )
                                        "
                                        class="text-[10px] text-blue-500 hover:text-blue-700 mt-1 transition-colors"
                                    >
                                        Tout régler :
                                        {{ fmt(Math.abs(patient.balance)) }} MAD
                                    </button>
                                </div>
                                <!-- Date -->
                                <div>
                                    <label
                                        class="text-[10px] font-medium text-blue-600 mb-1 block"
                                    >
                                        Date *
                                    </label>
                                    <input
                                        v-model="newTx.date"
                                        type="date"
                                        :max="today"
                                        class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300"
                                    />
                                </div>
                            </div>

                            <!-- Notes -->
                            <input
                                v-model="newTx.notes"
                                type="text"
                                placeholder="Note (optionnel)"
                                class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300 placeholder:text-slate-300"
                            />

                            <p v-if="txError" class="text-xs text-red-500">
                                {{ txError }}
                            </p>

                            <button
                                @click="handleAddTransaction"
                                :disabled="
                                    !newTx.amount || !newTx.date || adding
                                "
                                class="w-full py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 disabled:bg-blue-200 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                            >
                                <svg
                                    v-if="adding"
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
                                + Enregistrer le versement
                            </button>
                        </div>
                    </div>

                    <!-- Message compte soldé (mais formulaire reste visible) -->
                    <div
                        v-if="patient.balance_status === 'PAYÉ'"
                        class="mx-5 mt-4 p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-center"
                    >
                        <p class="text-xs font-medium text-emerald-700">
                            ✓ Compte soldé — vous pouvez ajouter une avance
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import PaymentStatusBadge from "./PaymentStatusBadge.vue";
import { usePayments } from "../../composables/usePayments";

const props = defineProps({
    patient: { type: Object, default: null },
    isDentist: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'edit', 'updated', 'patch-patient']);

const {
    addTransaction,
    deleteTransaction,
    fetchPatient,
    patient: detail,
} = usePayments();

const loadingDetail = ref(false);
const today = new Date().toISOString().split("T")[0];
const newTx = ref({ amount: null, date: today, notes: "" });

// ─── Copie locale des transactions pour réactivité immédiate ──────
const localTransactions = ref([]);
const adding = ref(false);
const txError = ref(null);

// Charge les détails quand on change de patient
watch(() => props.patient?.id, async (id) => {
    if (!id) return
    loadingDetail.value  = true
    localTransactions.value = []
    await fetchPatient(id)
    if (detail.value) {
        // Mise à jour des transactions locales depuis l'API
        localTransactions.value = [...(detail.value.transactions ?? [])]
        // Merge les totaux dans le patient parent (sans écraser les transactions)
        emit('patch-patient', {
            total_consultations: detail.value.total_consultations,
            total_paid:          detail.value.total_paid,
            balance:             detail.value.balance,
            balance_status:      detail.value.balance_status,
            consultations:       detail.value.consultations,
        })
    }
    loadingDetail.value = false
}, { immediate: true });

// ─── Progression ──────────────────────────────────────────────────
const progressPct = computed(() => {
    if (!props.patient?.total_consultations) return 0;
    return Math.min(
        100,
        (props.patient.total_paid / props.patient.total_consultations) * 100,
    );
});

const progressColor = computed(
    () =>
        ({
            PAYÉ: "bg-emerald-500",
            PARTIEL: "bg-amber-400",
            AVANCE: "bg-blue-400",
        })[props.patient?.balance_status] ?? "bg-slate-300",
);

// ─── Actions ──────────────────────────────────────────────────────
async function handleAddTransaction() {
    txError.value = null
    adding.value  = true
    try {
        const res = await addTransaction(props.patient.id, {
            amount: newTx.value.amount,
            date:   newTx.value.date,
            notes:  newTx.value.notes || null,
        })
        // Ajout immédiat local
        if (res.transaction) {
            localTransactions.value = [res.transaction, ...localTransactions.value]
        }
        newTx.value = { amount: null, date: today, notes: '' }
        emit('updated')
    } catch (e) {
        txError.value = e.message
    } finally {
        adding.value = false
    }
}

async function handleDeleteTransaction(txId) {
    if (!confirm('Supprimer ce versement ?')) return
    // Suppression optimiste immédiate
    const backup = [...localTransactions.value]
    localTransactions.value = localTransactions.value.filter(t => t.id !== txId)
    try {
        await deleteTransaction(txId, props.patient.id)
        emit('updated')
    } catch (e) {
        txError.value = e.message
        localTransactions.value = backup // restaure si erreur
    }
}
// ─── Helpers ──────────────────────────────────────────────────────
function fmt(val) {
    return Number(val ?? 0).toFixed(0);
}

function consultationStatusLabel(s) {
    return (
        { EN_COURS: "En cours", TERMINE: "Terminée", ANNULE: "Annulée" }[s] ?? s
    );
}

function consultationStatusClass(s) {
    return (
        {
            EN_COURS: "bg-amber-100 text-amber-700",
            TERMINE: "bg-emerald-100 text-emerald-700",
            ANNULE: "bg-red-100 text-red-700",
        }[s] ?? "bg-slate-100 text-slate-600"
    );
}
</script>

<style scoped>
.panel-enter-active,
.panel-leave-active {
    transition: all 0.2s ease;
}
.panel-enter-from {
    transform: translateX(100%);
    opacity: 0;
}
.panel-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
</style>
