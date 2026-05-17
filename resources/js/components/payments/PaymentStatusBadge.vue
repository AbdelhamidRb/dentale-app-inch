<!-- resources/js/components/payments/PaymentStatusBadge.vue -->
<template>
    <span
        :class="badge.class"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
    >
        <span class="w-1.5 h-1.5 rounded-full" :class="badge.dot"></span>
        {{ badge.label }}
    </span>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    status: { type: String, required: true },
});

const badge = computed(
    () =>
        ({
            PARTIEL: {
                label: "Partiel",
                dot: "bg-red-400",
                class: "bg-red-50    text-red-700",
            },
            PAYÉ: {
                label: "Soldé",
                dot: "bg-emerald-400",
                class: "bg-emerald-50 text-emerald-700",
            },
            AVANCE: {
                label: "Avance",
                dot: "bg-blue-400",
                class: "bg-blue-50   text-blue-700",
            },
        })[props.status] ?? {
            label: props.status,
            dot: "bg-slate-300",
            class: "bg-slate-100 text-slate-500",
        },
);
</script>
