<template>
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">

        <!-- ── En-tête de la ligne ─────────────────────────────────── -->
        <div class="flex items-center gap-3 px-4 py-3 bg-slate-50 border-b border-slate-100">

            <!-- Nom de l'acte -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate">
                    {{ act.catalog_act?.name ?? act.catalogActName }}
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                    Code : {{ act.catalog_act?.code ?? act.catalogActCode ?? '—' }}
                </p>
            </div>

            <!-- Prix éditable -->
            <div class="flex items-center gap-1.5 shrink-0">
                <input
                    v-if="editable"
                    type="number"
                    min="0"
                    step="10"
                    :value="act.price"
                    @input="emit('update:price', Number($event.target.value))"
                    class="w-24 text-right text-sm font-semibold text-blue-700 bg-blue-50
                           border border-blue-200 rounded-lg px-2 py-1
                           focus:outline-none focus:ring-2 focus:ring-blue-300"
                />
                <span v-else class="text-sm font-semibold text-blue-700">
                    {{ fmt(act.price) }}
                </span>
                <span class="text-xs text-slate-400">MAD</span>
            </div>

            <!-- Bouton supprimer -->
            <button
                v-if="editable"
                @click="emit('remove')"
                class="shrink-0 w-7 h-7 flex items-center justify-center
                       rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50
                       transition-colors"
                title="Supprimer cet acte"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>

        <!-- ── Corps : dents sélectionnées ────────────────────────── -->
        <div class="px-4 py-3 space-y-3">

            <!-- Dents -->
            <div v-if="act.teeth?.length" class="flex flex-wrap gap-1.5">
                <span
                    v-for="tooth in act.teeth"
                    :key="tooth"
                    class="inline-flex items-center px-2 py-0.5 rounded-md
                           bg-blue-100 text-blue-700 text-xs font-semibold"
                >
                    🦷 {{ tooth }}
                </span>
            </div>
            <p v-else class="text-xs text-slate-400 italic">
                Acte global — pas de dent spécifique
            </p>

            <!-- Note spécifique -->
            <textarea
                v-if="editable"
                :value="act.notes"
                @input="emit('update:notes', $event.target.value)"
                placeholder="Note pour cet acte (optionnel)…"
                rows="2"
                class="w-full text-xs text-slate-600 bg-slate-50 border border-slate-200
                       rounded-lg px-3 py-2 resize-none
                       focus:outline-none focus:ring-2 focus:ring-blue-200
                       placeholder:text-slate-300"
            ></textarea>
            <p v-else-if="act.notes" class="text-xs text-slate-500 italic">
                {{ act.notes }}
            </p>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    act:      { type: Object,  required: true },
    editable: { type: Boolean, default: true  },
})

const emit = defineEmits(['remove', 'update:price', 'update:notes'])

function fmt(val) {
    return Math.round(Number(val ?? 0)).toLocaleString('fr-FR');
}
</script>