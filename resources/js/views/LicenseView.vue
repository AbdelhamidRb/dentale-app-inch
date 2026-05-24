<template>
    <div class="min-h-screen bg-slate-50 flex items-center justify-center px-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md p-8 text-center">

            <!-- Icône -->
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <ShieldX class="w-8 h-8 text-red-600" />
            </div>

            <h1 class="text-xl font-bold text-slate-800 mb-2">Licence requise</h1>
            <p class="text-sm text-slate-500 mb-6">{{ reason || 'Fichier dental-app.lic manquant ou invalide.' }}</p>

            <!-- MAC Address -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 mb-6">
                <p class="text-xs text-slate-400 mb-1">Adresse MAC de ce PC</p>
                <div class="flex items-center justify-center gap-2">
                    <code class="text-base font-bold text-slate-800 tracking-widest">{{ mac || 'Chargement...' }}</code>
                    <button @click="copyMac"
                            class="text-slate-400 hover:text-blue-600 transition-colors"
                            :title="copied ? 'Copié !' : 'Copier'">
                        <Check v-if="copied" class="w-4 h-4 text-green-500" />
                        <Copy v-else class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Instructions -->
            <div class="text-left space-y-3 mb-6">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Comment activer :</p>
                <div class="space-y-2">
                    <div class="flex items-start gap-2.5 text-sm text-slate-600">
                        <span class="w-5 h-5 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">1</span>
                        Copiez l'adresse MAC ci-dessus
                    </div>
                    <div class="flex items-start gap-2.5 text-sm text-slate-600">
                        <span class="w-5 h-5 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">2</span>
                        Envoyez-la avec votre email à votre fournisseur
                    </div>
                    <div class="flex items-start gap-2.5 text-sm text-slate-600">
                        <span class="w-5 h-5 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">3</span>
                        Placez le fichier <code class="bg-slate-100 px-1 rounded">dental-app.lic</code> reçu dans le dossier de l'application
                    </div>
                    <div class="flex items-start gap-2.5 text-sm text-slate-600">
                        <span class="w-5 h-5 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">4</span>
                        Rechargez la page
                    </div>
                </div>
            </div>

            <button @click="reload"
                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-colors">
                Vérifier la licence
            </button>

            <button @click="logout"
                    class="mt-2 w-full py-2 text-sm text-slate-400 hover:text-slate-600 transition-colors">
                Se déconnecter
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { ShieldX, Copy, Check } from 'lucide-vue-next';
import { authStore } from '../stores/auth';

const copied = ref(false);

const reason = authStore.licenseReason;
const mac    = authStore.licenseMac;

function copyMac() {
    if (!mac) return;
    navigator.clipboard.writeText(mac);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}

function reload() { window.location.reload(); }

async function logout() { await authStore.logout(); }
</script>
