<!-- resources/js/components/payments/PaymentModal.vue -->
<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="close"
            >
                <div
                    class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                ></div>

                <div
                    class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-slate-100"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">
                                {{
                                    isEdit
                                        ? "Modifier la facture"
                                        : "Nouvelle facture"
                                }}
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{
                                    isEdit
                                        ? `Facture #${form.id}`
                                        : "Remplissez les informations"
                                }}
                            </p>
                        </div>
                        <button
                            @click="close"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                        >
                            <svg
                                class="w-5 h-5"
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

                    <!-- Body -->
                    <div class="px-6 py-5 space-y-4">
                        <!-- Patient -->
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-500 mb-1.5"
                                >Patient *</label
                            >
                            <select
                                v-model="form.patient_id"
                                :disabled="!!preselectedPatient"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300 disabled:bg-slate-50 disabled:text-slate-400"
                            >
                                <option value="">Choisir un patient</option>
                                <option
                                    v-for="p in patients"
                                    :key="p.id"
                                    :value="p.id"
                                >
                                    {{
                                        p.full_name ??
                                        `${p.first_name} ${p.last_name}`
                                    }}
                                </option>
                            </select>
                        </div>

                        <!-- Label -->
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-500 mb-1.5"
                            >
                                Description *
                            </label>
                            <input
                                v-model="form.label"
                                type="text"
                                placeholder="ex: Détartrage + Radio panoramique"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-300 placeholder:text-slate-300"
                            />
                        </div>

                        <!-- Montant total -->
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-500 mb-1.5"
                            >
                                Montant total *
                            </label>
                            <div class="relative">
                                <input
                                    v-model.number="form.total_amount"
                                    type="number"
                                    min="0"
                                    step="10"
                                    placeholder="0"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 pr-16 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-300"
                                />
                                <span
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-400"
                                    >MAD</span
                                >
                            </div>
                        </div>

                        <!-- Lier à une consultation (optionnel) -->
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-500 mb-1.5"
                            >
                                Lier à une consultation
                                <span class="text-slate-300 font-normal"
                                    >(optionnel)</span
                                >
                            </label>
                            <select
                                v-model="form.consultation_id"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300"
                            >
                                <option :value="null">Sans consultation</option>
                                <option
                                    v-for="c in consultations"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    #{{ c.id }} — {{ c.patient?.full_name }} ({{
                                        c.created_at
                                    }})
                                </option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-500 mb-1.5"
                                >Notes</label
                            >
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Remarques optionnelles…"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-blue-300 placeholder:text-slate-300"
                            />
                        </div>

                        <!-- Erreur -->
                        <div
                            v-if="error"
                            class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600"
                        >
                            {{ error }}
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100"
                    >
                        <button
                            @click="close"
                            class="px-4 py-2 text-sm text-slate-500 hover:text-slate-700 rounded-xl hover:bg-slate-100 transition-colors"
                        >
                            Annuler
                        </button>
                        <button
                            @click="submit"
                            :disabled="
                                !form.patient_id ||
                                !form.label ||
                                !form.total_amount ||
                                loading
                            "
                            class="px-5 py-2 text-sm font-medium rounded-xl transition-colors bg-blue-600 text-white hover:bg-blue-700 disabled:bg-blue-200 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg
                                v-if="loading"
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
                            {{ isEdit ? "Enregistrer" : "Créer la facture" }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { usePayments } from "../../composables/usePayments";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    paymentToEdit: { type: Object, default: null },
    preselectedPatient: { type: Object, default: null },
    patients: { type: Array, default: () => [] },
    consultations: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:modelValue", "saved"]);

const { createPayment, updatePayment } = usePayments();

const loading = ref(false);
const error = ref(null);

const defaultForm = () => ({
    id: null,
    patient_id: props.preselectedPatient?.id ?? "",
    consultation_id: null,
    label: "",
    total_amount: 0,
    notes: "",
});

const form = ref(defaultForm());

const isEdit = computed(() => !!props.paymentToEdit);

watch(
    () => props.paymentToEdit,
    (p) => {
        if (!p) {
            form.value = defaultForm();
            return;
        }
        form.value = {
            id: p.id,
            patient_id: p.patient?.id ?? "",
            consultation_id: p.consultation_id,
            label: p.label,
            total_amount: p.total_amount,
            notes: p.notes ?? "",
        };
    },
    { immediate: true },
);

watch(
    () => props.modelValue,
    (v) => {
        if (!v) error.value = null;
    },
);

async function submit() {
    loading.value = true;
    error.value = null;
    try {
        const payload = {
            patient_id: form.value.patient_id,
            consultation_id: form.value.consultation_id,
            label: form.value.label,
            total_amount: form.value.total_amount,
            notes: form.value.notes || null,
        };
        const saved = isEdit.value
            ? await updatePayment(form.value.id, payload)
            : await createPayment(payload);
        emit("saved", saved);
        close();
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

function close() {
    form.value = defaultForm();
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
