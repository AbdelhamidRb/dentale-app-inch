<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0"
                    leave-active-class="transition duration-100" leave-to-class="opacity-0">
            <div v-if="modelValue"
                 class="fixed inset-0 z-[500] flex items-center justify-center bg-black/40 px-4">
                <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                            <TriangleAlert class="w-5 h-5 text-red-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">{{ title }}</p>
                            <p v-if="subtitle" class="text-xs text-slate-400 mt-0.5">{{ subtitle }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 mb-5" v-html="message" />
                    <div class="flex gap-2">
                        <button @click="emit('update:modelValue', false)"
                                class="flex-1 py-2 text-sm font-medium text-slate-600
                                       border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                            Annuler
                        </button>
                        <button @click="handleConfirm"
                                class="flex-1 py-2 text-sm font-medium text-white
                                       bg-red-600 hover:bg-red-700 disabled:opacity-50 rounded-xl transition-colors">
                            {{ confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { TriangleAlert } from 'lucide-vue-next';

const props = defineProps({
    modelValue:   { type: Boolean, default: false },
    title:        { type: String,  default: 'Confirmer' },
    subtitle:     { type: String,  default: '' },
    message:      { type: String,  default: 'Cette action est irréversible.' },
    confirmLabel: { type: String,  default: 'Confirmer' },
});

const emit = defineEmits(['update:modelValue', 'confirm']);

function handleConfirm() {
    emit('confirm');
    emit('update:modelValue', false);
}
</script>
