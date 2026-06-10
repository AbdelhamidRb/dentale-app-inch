<template>
    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- ── Logo + titre ───────────────────────────────────────────── -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-4 shadow-lg"
                >
                    <Activity class="w-8 h-8 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-slate-800">DentalApp</h1>
                <p class="text-slate-500 text-sm mt-1">
                    Connectez-vous à votre espace
                </p>
            </div>

            <!-- ── Carte formulaire ───────────────────────────────────────── -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8"
            >
                <div
                    v-if="retrying"
                    class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm"
                >
                    <div class="flex items-center gap-2 text-blue-700 font-medium">
                        <svg class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Démarrage du service en cours...
                    </div>
                    <p class="text-blue-500 text-xs mt-1 ml-6">
                        Connexion automatique dès que le service est prêt
                        <span v-if="retryCount > 1">(tentative {{ retryCount }})</span>
                    </p>
                </div>
                <div
                    v-else-if="error"
                    class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm"
                >
                    {{ error }}
                </div>

                <form @submit.prevent="handleLogin" class="space-y-5">
                    <!-- ── Champ Email ──────────────────────────────────────── -->
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1.5"
                        >
                            Email
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="exemple@cabinet.ma"
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-150"
                        />
                    </div>

                    <!-- ── Champ Mot de passe ───────────────────────────────── -->
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1.5"
                        >
                            Mot de passe
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-150 pr-10"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                            >
                                <EyeOff v-if="showPassword" class="w-5 h-5" />
                                <Eye v-else class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- ── Bouton de connexion ──────────────────────────────── -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-slate-100 disabled:text-slate-400 text-white font-medium py-2.5 rounded-lg text-sm transition-colors duration-150 flex items-center justify-center gap-2"
                    >
                        <svg
                            v-if="loading"
                            class="animate-spin w-4 h-4"
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
                        {{ loading ? "Connexion..." : "Se connecter" }}
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-slate-400 mt-6">
                Cabinet Dentaire · Système de gestion
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from "vue";
import { authStore } from "../stores/auth";
import { Activity, Eye, EyeOff } from "lucide-vue-next";

const form = reactive({ email: "", password: "" });
const loading     = ref(false);
const retrying    = ref(false);
const retryCount  = ref(0);
const error       = ref(null);
const showPassword = ref(false);

async function handleLogin() {
    loading.value    = true;
    retrying.value   = false;
    retryCount.value = 0;
    error.value      = null;

    try {
        authStore._onRetry = (attempt) => {
            retrying.value   = true;
            retryCount.value = attempt;
        };
        await authStore.login(form.email, form.password);
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value    = false;
        retrying.value   = false;
        authStore._onRetry = null;
    }
}
</script>
