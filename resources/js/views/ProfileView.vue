<template>
    <div class="flex-1 min-h-0 overflow-y-auto bg-slate-50 px-3 sm:px-5 py-4 sm:py-6 pb-20 lg:pb-6">
    <div class="max-w-2xl mx-auto space-y-5">
        <!-- ══════════════════════════════════════════════════════════
         CARTE AVATAR
         ══════════════════════════════════════════════════════════ -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-800 mb-5">
                Photo de profil
            </h2>

            <div class="flex items-center gap-6">
                <!-- Avatar actuel -->
                <div class="relative group">
                    <div
                        class="w-20 h-20 rounded-full overflow-hidden bg-blue-600 flex items-center justify-center shrink-0"
                    >
                        <img
                            v-if="profile.avatar"
                            :src="profile.avatar"
                            class="w-full h-full object-cover"
                            alt="Avatar"
                        />
                        <span v-else class="text-2xl font-bold text-white">
                            {{ initials }}
                        </span>
                    </div>

                    <!-- Overlay upload au hover -->
                    <label
                        class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity"
                    >
                        <Camera class="w-5 h-5 text-white" />
                        <input
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handleAvatarUpload"
                        />
                    </label>
                </div>

                <!-- Infos + bouton -->
                <div>
                    <p class="text-sm font-medium text-slate-800">
                        {{ profile.name }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5 capitalize">
                        {{
                            profile.role === "DENTIST"
                                ? "Dentiste"
                                : "Assistant(e)"
                        }}
                    </p>
                    <label
                        class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 border border-slate-300 rounded-lg text-xs text-slate-600 hover:bg-slate-50 cursor-pointer transition-colors"
                    >
                        <Upload class="w-3.5 h-3.5" />
                        Changer la photo
                        <input
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handleAvatarUpload"
                        />
                    </label>
                    <p class="text-[10px] text-slate-400 mt-1.5">
                        JPG, PNG ou WebP · Max 2 Mo
                    </p>
                </div>
            </div>
           

            <!-- Barre de progression upload -->
            <div v-if="uploading" class="mt-4">
                <div class="h-1 bg-slate-100 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-blue-500 rounded-full animate-pulse w-3/4"
                    ></div>
                </div>
                <p class="text-xs text-slate-400 mt-1">Upload en cours...</p>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
         CARTE INFORMATIONS PERSONNELLES
         ══════════════════════════════════════════════════════════ -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-800 mb-5">
                Informations personnelles
            </h2>

            <!-- Message succès -->
            <div
                v-if="infoSuccess"
                class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2"
            >
                <CheckCircle class="w-4 h-4 shrink-0" />
                {{ infoSuccess }}
            </div>

            <!-- Message erreur -->
            <div
                v-if="infoError"
                class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm"
            >
                {{ infoError }}
            </div>

            <form @submit.prevent="updateProfile" class="space-y-4">
                <!-- Nom complet -->
                <div>
                    <label
                        class="block text-xs font-medium text-slate-600 mb-1.5"
                        >Nom complet</label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <!-- Email -->
                <div>
                    <label
                        class="block text-xs font-medium text-slate-600 mb-1.5"
                        >Email</label
                    >
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <!-- Téléphone -->
                <div>
                    <label
                        class="block text-xs font-medium text-slate-600 mb-1.5"
                        >Téléphone</label
                    >
                    <input
                        v-model="form.phone"
                        type="tel"
                        placeholder="+212 6XX XXX XXX"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <!-- Rôle (lecture seule) -->
                <div>
                    <label
                        class="block text-xs font-medium text-slate-600 mb-1.5"
                        >Rôle</label
                    >
                    <div
                        class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-500"
                    >
                        {{
                            profile.role === "DENTIST"
                                ? "Dentiste"
                                : "Assistant(e)"
                        }}
                    </div>
                </div>

                <div class="pt-1">
                    <button
                        type="submit"
                        :disabled="infoLoading"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2"
                    >
                        <svg
                            v-if="infoLoading"
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
                        {{ infoLoading ? "Enregistrement..." : "Sauvegarder" }}
                    </button>
                </div>
            </form>
        </div>

        <!-- ══════════════════════════════════════════════════════════
         CARTE MOT DE PASSE
         ══════════════════════════════════════════════════════════ -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-800 mb-5">
                Changer le mot de passe
            </h2>

            <!-- Message succès -->
            <div
                v-if="pwdSuccess"
                class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2"
            >
                <CheckCircle class="w-4 h-4 shrink-0" />
                {{ pwdSuccess }}
            </div>

            <!-- Message erreur -->
            <div
                v-if="pwdError"
                class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm"
            >
                {{ pwdError }}
            </div>

            <form @submit.prevent="updatePassword" class="space-y-4">
                <div>
                    <label
                        class="block text-xs font-medium text-slate-600 mb-1.5"
                        >Mot de passe actuel</label
                    >
                    <input
                        v-model="pwd.current"
                        type="password"
                        required
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <div>
                    <label
                        class="block text-xs font-medium text-slate-600 mb-1.5"
                        >Nouveau mot de passe</label
                    >
                    <input
                        v-model="pwd.new"
                        type="password"
                        required
                        minlength="8"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <div>
                    <label
                        class="block text-xs font-medium text-slate-600 mb-1.5"
                        >Confirmer le nouveau mot de passe</label
                    >
                    <input
                        v-model="pwd.confirm"
                        type="password"
                        required
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <div class="pt-1">
                    <button
                        type="submit"
                        :disabled="pwdLoading"
                        class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 disabled:bg-slate-400 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2"
                    >
                        <svg
                            v-if="pwdLoading"
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
                        {{
                            pwdLoading
                                ? "Modification..."
                                : "Modifier le mot de passe"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { Camera, Upload, CheckCircle } from "lucide-vue-next";
import { authStore } from "../stores/auth";
import ConsultationStatusBadge from '../components/consultations/ConsultationStatusBadge.vue'
import { useConsultations } from '../composables/useConsultations'
import { useRouter } from 'vue-router'

// ─── État du profil chargé depuis l'API ──────────────────────────────
const profile = reactive({
    name: "",
    email: "",
    phone: "",
    role: "",
    avatar: null,
});

// ─── Formulaire infos personnelles ───────────────────────────────────
const form = reactive({ name: "", email: "", phone: "" });

// ─── Formulaire mot de passe ──────────────────────────────────────────
const pwd = reactive({ current: "", new: "", confirm: "" });

// ─── États de chargement et messages ─────────────────────────────────
const uploading = ref(false);
const infoLoading = ref(false);
const pwdLoading = ref(false);
const infoSuccess = ref(null);
const infoError = ref(null);
const pwdSuccess = ref(null);
const pwdError = ref(null);

// ─── Initiales pour l'avatar par défaut ──────────────────────────────
const initials = computed(() =>
    profile.name
        .split(" ")
        .map((w) => w[0])
        .join("")
        .toUpperCase()
        .slice(0, 2),
);

// ─── Helper : headers avec token Sanctum ─────────────────────────────
function authHeaders() {
    return {
        Authorization: `Bearer ${authStore.token}`,
        Accept: "application/json",
    };
}

// ─── Charger le profil au montage du composant ───────────────────────
onMounted(async () => {
    try {
        const res = await fetch("/api/profile", { headers: authHeaders() });
        if (!res.ok) throw new Error("Erreur lors du chargement du profil.");
        const data = await res.json();
        Object.assign(profile, data);
        form.name  = data.name;
        form.email = data.email;
        form.phone = data.phone || "";
    } catch (e) {
        infoError.value = e.message;
    }
});

// ─── Upload avatar ────────────────────────────────────────────────────
async function handleAvatarUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    uploading.value = true;

    const formData = new FormData();
    formData.append("avatar", file);

    try {
        const res = await fetch("/api/profile/avatar", {
            method: "POST",
            headers: authHeaders(), // pas de Content-Type → FormData le gère seul
            body: formData,
        });
        const data = await res.json();

        if (res.ok) {
            const avatarUrl = data.avatar + "?t=" + Date.now();
            profile.avatar = avatarUrl;

            // Met à jour le store global → navbar se rafraîchit automatiquement
            authStore.user = { ...authStore.user, avatar: avatarUrl };
            localStorage.setItem("user", JSON.stringify(authStore.user));
        }
    } finally {
        uploading.value = false;
    }
}

// ─── Mettre à jour les infos personnelles ────────────────────────────
async function updateProfile() {
    infoLoading.value = true;
    infoSuccess.value = null;
    infoError.value = null;

    try {
        const res = await fetch("/api/profile", {
            method: "PUT",
            headers: { ...authHeaders(), "Content-Type": "application/json" },
            body: JSON.stringify(form),
        });
        const data = await res.json();

        if (!res.ok) throw new Error(data.message);

        // Met à jour le store global pour que la navbar reflète le changement
        authStore.user = data.user;
        localStorage.setItem("user", JSON.stringify(data.user));

        Object.assign(profile, data.user);
        infoSuccess.value = "Profil mis à jour avec succès.";

        // Efface le message après 3 secondes
        setTimeout(() => (infoSuccess.value = null), 3000);
    } catch (e) {
        infoError.value = e.message;
    } finally {
        infoLoading.value = false;
    }
}

// ─── Changer le mot de passe ──────────────────────────────────────────
async function updatePassword() {
    pwdError.value = null;
    pwdSuccess.value = null;

    // Vérification côté client avant d'appeler l'API
    if (pwd.new !== pwd.confirm) {
        pwdError.value = "Les mots de passe ne correspondent pas.";
        return;
    }

    pwdLoading.value = true;

    try {
        const res = await fetch("/api/profile/password", {
            method: "POST",
            headers: { ...authHeaders(), "Content-Type": "application/json" },
            body: JSON.stringify({
                current_password: pwd.current,
                new_password: pwd.new,
                new_password_confirmation: pwd.confirm,
            }),
        });
        const data = await res.json();

        if (!res.ok) throw new Error(data.message);

        pwdSuccess.value = "Mot de passe modifié avec succès.";
        pwd.current = pwd.new = pwd.confirm = ""; // Reset le formulaire

        setTimeout(() => (pwdSuccess.value = null), 3000);
    } catch (e) {
        pwdError.value = e.message;
    } finally {
        pwdLoading.value = false;
    }
}
const router = useRouter()
        const { consultations: enCoursList, fetchEnCours } = useConsultations()

        onMounted(() => {
            fetchEnCours()
        })
</script>
