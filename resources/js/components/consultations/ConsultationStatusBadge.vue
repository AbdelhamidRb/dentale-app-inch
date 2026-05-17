<template>
    <span :class="badge.class" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium">
        <span class="w-1.5 h-1.5 rounded-full" :class="badge.dot"></span>
        {{ badge.label }}
    </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    status: { type: String, required: true }
})

const badge = computed(() => ({
    BROUILLON: { label: 'Brouillon',  dot: 'bg-slate-400',  class: 'bg-slate-100 text-slate-600' },
    EN_COURS:  { label: 'En cours',   dot: 'bg-amber-400',  class: 'bg-amber-50  text-amber-700' },
    TERMINE:   { label: 'Terminée',   dot: 'bg-emerald-400',class: 'bg-emerald-50 text-emerald-700' },
}[props.status] ?? { label: props.status, dot: 'bg-slate-300', class: 'bg-slate-100 text-slate-500' }))
</script>