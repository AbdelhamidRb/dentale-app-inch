<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-[200] flex flex-col gap-3 pointer-events-none">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto relative flex items-start gap-3 bg-white rounded-2xl shadow-2xl px-4 py-3.5 min-w-[300px] max-w-sm overflow-hidden border border-slate-100"
                    :class="{
                        'border-l-[3px] border-l-emerald-500': toast.type === 'success',
                        'border-l-[3px] border-l-red-500':     toast.type === 'error',
                        'border-l-[3px] border-l-blue-500':    toast.type === 'info',
                    }"
                >
                    <!-- Icône avec fond coloré -->
                    <div
                        class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center mt-0.5"
                        :class="{
                            'bg-emerald-50': toast.type === 'success',
                            'bg-red-50':     toast.type === 'error',
                            'bg-blue-50':    toast.type === 'info',
                        }"
                    >
                        <CheckCircle  v-if="toast.type === 'success'" class="w-4.5 h-4.5 text-emerald-500" />
                        <AlertCircle  v-else-if="toast.type === 'error'" class="w-4.5 h-4.5 text-red-500" />
                        <Info         v-else class="w-4.5 h-4.5 text-blue-500" />
                    </div>

                    <!-- Texte -->
                    <div class="flex-1 pt-1">
                        <p class="text-sm font-semibold text-slate-800 leading-snug">{{ toast.message }}</p>
                    </div>

                    <!-- Bouton fermer -->
                    <button
                        @click="dismissToast(toast.id)"
                        class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center text-slate-300 hover:text-slate-500 hover:bg-slate-100 transition-colors mt-0.5"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>

                    <!-- Barre de progression -->
                    <div class="absolute bottom-0 left-0 right-0 h-[2px] bg-slate-50">
                        <div
                            class="h-full toast-progress"
                            :class="{
                                'bg-emerald-400': toast.type === 'success',
                                'bg-red-400':     toast.type === 'error',
                                'bg-blue-400':    toast.type === 'info',
                            }"
                            :style="{ animationDuration: `${toast.duration}ms` }"
                        />
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { CheckCircle, AlertCircle, Info, X } from 'lucide-vue-next'
import { toasts, dismissToast } from '../../stores/toast.js'
</script>

<style scoped>
.toast-enter-active { transition: all 0.38s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-leave-active { transition: all 0.22s cubic-bezier(0.4, 0, 1, 1); }
.toast-enter-from   { opacity: 0; transform: translateX(1.5rem) scale(0.92); }
.toast-leave-to     { opacity: 0; transform: translateX(1.5rem) scale(0.96); }

.toast-progress {
    animation: shrink linear forwards;
    transform-origin: left;
    width: 100%;
}

@keyframes shrink {
    from { width: 100%; }
    to   { width: 0%;   }
}
</style>
