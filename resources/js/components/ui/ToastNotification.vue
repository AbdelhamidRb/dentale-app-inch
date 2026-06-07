<template>
    <Teleport to="body">
        <div class="fixed bottom-24 right-4 lg:bottom-6 z-[200] flex flex-col gap-2 pointer-events-none">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium min-w-[220px] max-w-xs"
                    :class="{
                        'bg-emerald-600 text-white': toast.type === 'success',
                        'bg-red-500 text-white':     toast.type === 'error',
                        'bg-blue-600 text-white':    toast.type === 'info',
                    }"
                >
                    <CheckCircle  v-if="toast.type === 'success'" class="w-4 h-4 shrink-0" />
                    <AlertCircle  v-else-if="toast.type === 'error'" class="w-4 h-4 shrink-0" />
                    <Info         v-else class="w-4 h-4 shrink-0" />
                    <span>{{ toast.message }}</span>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { CheckCircle, AlertCircle, Info } from 'lucide-vue-next'
import { toasts } from '../../stores/toast.js'
</script>

<style scoped>
.toast-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(1rem) scale(0.95); }
.toast-leave-to     { opacity: 0; transform: translateX(1rem); }
</style>
