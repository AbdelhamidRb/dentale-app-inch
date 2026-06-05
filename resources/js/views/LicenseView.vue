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
                        Envoyez-la à votre fournisseur pour recevoir le fichier de licence
                    </div>
                    <div class="flex items-start gap-2.5 text-sm text-slate-600">
                        <span class="w-5 h-5 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">3</span>
                        Uploadez le fichier <code class="bg-slate-100 px-1 rounded">dental-app.lic</code> reçu ci-dessous
                    </div>
                </div>
            </div>

            <!-- Succès upload -->
            <div v-if="uploadSuccess"
                 class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
                <CheckCircle class="w-5 h-5 text-green-600 shrink-0" />
                <div class="text-left">
                    <p class="text-sm font-medium text-green-700">Licence activée avec succès !</p>
                    <p class="text-xs text-green-600">Redirection vers la connexion...</p>
                </div>
            </div>

            <!-- Erreur upload -->
            <div v-if="uploadError"
                 class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm">
                {{ uploadError }}
            </div>

            <!-- Upload licence -->
            <input ref="fileInput" type="file" accept=".lic" class="hidden" @change="handleFile" />

            <button @click="fileInput.click()"
                    :disabled="uploading || uploadSuccess"
                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors flex items-center justify-center gap-2">
                <svg v-if="uploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                <Upload v-else class="w-4 h-4" />
                {{ uploading ? 'Activation en cours...' : 'Uploader la licence' }}
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
import { useRouter } from 'vue-router';
import { ShieldX, Copy, Check, Upload, CheckCircle } from 'lucide-vue-next';
import { authStore } from '../stores/auth';

const router      = useRouter();
const copied      = ref(false);
const uploading   = ref(false);
const uploadError = ref(null);
const uploadSuccess = ref(false);
const fileInput   = ref(null);

const reason = authStore.licenseReason;
const mac    = authStore.licenseMac;

function copyMac() {
    if (!mac) return;
    navigator.clipboard.writeText(mac);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}

async function handleFile(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    uploading.value   = true;
    uploadError.value = null;

    try {
        const form = new FormData();
        form.append('license', file);

        const res  = await fetch('/api/license/upload', {
            method:  'POST',
            headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
            body:    form,
        });

        const data = await res.json();

        if (!res.ok) {
            const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            uploadError.value = first || data.message || 'Erreur lors de l\'upload.';
            return;
        }

        uploadSuccess.value = true;
        setTimeout(() => router.push('/login'), 2500);

    } catch {
        uploadError.value = 'Impossible de contacter le serveur. Vérifiez que Laragon est démarré.';
    } finally {
        uploading.value = false;
        if (fileInput.value) fileInput.value.value = '';
    }
}

async function logout() { await authStore.logout(); }
</script>
