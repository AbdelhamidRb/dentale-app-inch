<template>
    <div>
        <p class="text-[10px] text-slate-400 uppercase tracking-wide">
            {{ label }}
        </p>
        <p
            class="text-sm mt-0.5 font-medium"
            :class="value ? 'text-slate-700' : 'text-slate-400 italic'"
        >
            {{ value || "Aucune information" }}
        </p>
    </div>
</template>

<script setup>
defineProps({ label: String, value: [String, Number] });
</script>
