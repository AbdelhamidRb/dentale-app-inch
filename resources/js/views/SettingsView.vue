<template>
    <div class="flex-1 min-h-0 overflow-y-auto bg-slate-50 px-3 sm:px-6 py-4 sm:py-6 pb-20 lg:pb-6">

        <!-- Titre -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-800">Paramètres</h1>
            <p class="text-sm text-slate-400 mt-0.5">Configuration du cabinet</p>
        </div>

        <!-- Onglets -->
        <div class="flex gap-1 mb-6 bg-white border border-slate-200 rounded-xl p-1 w-fit">
            <button
                v-for="tab in tabs" :key="tab.id"
                @click="activeTab = tab.id"
                :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
                         activeTab === tab.id
                             ? 'bg-blue-600 text-white'
                             : 'text-slate-500 hover:text-slate-700']"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- ══ Onglet Sauvegardes ══════════════════════════════════ -->
        <div v-if="activeTab === 'backup'" class="space-y-4 max-w-2xl">

            <!-- Dernier backup -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="font-semibold text-slate-800">Sauvegarde manuelle</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            BDD + images patients → <code class="bg-slate-100 px-1 rounded">C:\backups\dental-app\</code>
                        </p>
                    </div>
                    <button
                        @click="runBackup"
                        :disabled="backing"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700
                               disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors"
                    >
                        <svg v-if="backing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <DatabaseBackup v-else class="w-4 h-4" />
                        {{ backing ? 'Sauvegarde...' : 'Sauvegarder maintenant' }}
                    </button>
                </div>

                <!-- Message de résultat -->
                <div v-if="backupMsg"
                     :class="['flex items-center gap-2 text-sm px-3 py-2 rounded-lg',
                              backupOk ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700']">
                    <CheckCircle v-if="backupOk" class="w-4 h-4 shrink-0" />
                    <XCircle v-else class="w-4 h-4 shrink-0" />
                    {{ backupMsg }}
                </div>

                <!-- Info planification -->
                <div class="mt-3 text-xs text-slate-400 flex items-center gap-1.5">
                    <Clock class="w-3.5 h-3.5" />
                    Automatique : Lun–Ven 18h00 · Samedi 12h30
                </div>
            </div>

            <!-- Liste des backups -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <p class="font-semibold text-slate-800">Historique des sauvegardes</p>
                    <span class="text-xs text-slate-400">{{ backups.length }} backup(s)</span>
                </div>

                <div v-if="loadingList" class="py-8 flex justify-center text-slate-400 text-sm">
                    Chargement…
                </div>

                <div v-else-if="!backups.length" class="py-8 text-center text-sm text-slate-400">
                    Aucune sauvegarde trouvée
                </div>

                <div v-else class="divide-y divide-slate-50">
                    <div v-for="(b, i) in backups" :key="b.name"
                         class="flex items-center gap-3 px-5 py-3">

                        <!-- Date + badges -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-slate-700">{{ formatName(b.name) }}</p>
                                <span v-if="i === 0"
                                      class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-full">
                                    Le plus récent
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                BDD {{ formatSize(b.db_size) }}
                                <span v-if="b.has_images"> · Images {{ formatSize(b.img_size) }}</span>
                            </p>
                        </div>

                        <!-- Bouton restaurer -->
                        <button
                            @click="confirmRestore(b)"
                            :disabled="restoring === b.name"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium
                                   text-slate-600 hover:text-red-600 hover:bg-red-50
                                   border border-slate-200 hover:border-red-200
                                   rounded-lg transition-colors disabled:opacity-40"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            Restaurer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ Onglet Message WhatsApp ═════════════════════════════ -->
        <div v-if="activeTab === 'whatsapp'" class="max-w-2xl">
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <p class="font-semibold text-slate-800 mb-1">Message de rappel WhatsApp</p>
                <p class="text-xs text-slate-400 mb-4">
                    Variables disponibles :
                    <code class="bg-slate-100 px-1 rounded">{nom}</code>
                    <code class="bg-slate-100 px-1 rounded ml-1">{date}</code>
                    <code class="bg-slate-100 px-1 rounded ml-1">{heure}</code>
                </p>

                <textarea
                    v-model="wtspTemplate"
                    rows="4"
                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                           text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Bonjour {nom}…"
                />

                <!-- Aperçu -->
                <div class="mt-3 bg-green-50 border border-green-100 rounded-xl px-3 py-2.5">
                    <p class="text-[11px] text-green-600 font-medium mb-1">Aperçu :</p>
                    <p class="text-xs text-green-800">{{ wtspPreview }}</p>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button
                        @click="saveTemplate"
                        :disabled="savingTemplate"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50
                               text-white text-sm font-medium rounded-xl transition-colors"
                    >
                        {{ savingTemplate ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                    <span v-if="templateSaved" class="text-sm text-green-600 flex items-center gap-1">
                        <CheckCircle class="w-4 h-4" /> Enregistré
                    </span>
                </div>
            </div>
        </div>

        <!-- Modal confirmation restauration -->
        <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0"
                    leave-active-class="transition duration-100" leave-to-class="opacity-0">
            <div v-if="restoreTarget"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
                <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                            <TriangleAlert class="w-5 h-5 text-red-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">Confirmer la restauration</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ formatName(restoreTarget.name) }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 mb-5">
                        Toutes les données actuelles seront <strong>remplacées</strong> par ce backup.
                        Cette action est irréversible.
                    </p>
                    <div class="flex gap-2">
                        <button @click="restoreTarget = null"
                                class="flex-1 py-2 text-sm font-medium text-slate-600
                                       border border-slate-200 rounded-xl hover:bg-slate-50">
                            Annuler
                        </button>
                        <button @click="doRestore"
                                :disabled="restoring"
                                class="flex-1 py-2 text-sm font-medium text-white
                                       bg-red-600 hover:bg-red-700 disabled:opacity-50 rounded-xl">
                            {{ restoring ? 'Restauration…' : 'Confirmer' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { DatabaseBackup, CheckCircle, XCircle, Clock, RotateCcw, TriangleAlert } from 'lucide-vue-next';

const activeTab = ref('backup');
const tabs = [
    { id: 'backup',   label: 'Sauvegardes' },
    { id: 'whatsapp', label: 'Message WhatsApp' },
];

// ── Backup ──────────────────────────────────────────────────────
const backups     = ref([]);
const loadingList = ref(false);
const backing     = ref(false);
const backupMsg   = ref('');
const backupOk    = ref(false);

async function loadBackups() {
    loadingList.value = true;
    try {
        const res = await api('/api/backup/list');
        backups.value = res.backups ?? [];
    } finally {
        loadingList.value = false;
    }
}

async function runBackup() {
    backing.value  = true;
    backupMsg.value = '';
    try {
        const res = await api('/api/backup/run', 'POST');
        backupOk.value  = true;
        backupMsg.value = `Backup créé : ${res.name}  (BDD ${formatSize(res.db_size_kb * 1024)}, Images ${formatSize(res.img_size_kb * 1024)})`;
        await loadBackups();
    } catch (e) {
        backupOk.value  = false;
        backupMsg.value = e.message ?? 'Erreur lors du backup.';
    } finally {
        backing.value = false;
    }
}

// ── Restauration ────────────────────────────────────────────────
const restoreTarget = ref(null);
const restoring     = ref(false);

function confirmRestore(b) { restoreTarget.value = b; }

async function doRestore() {
    restoring.value = true;
    try {
        await api('/api/backup/restore', 'POST', { name: restoreTarget.value.name });
        restoreTarget.value = null;
        backupOk.value  = true;
        backupMsg.value = 'Restauration terminée avec succès. Rechargez la page pour appliquer les changements.';
        activeTab.value = 'backup';
    } catch (e) {
        restoreTarget.value = null;
        backupOk.value  = false;
        backupMsg.value = e.message ?? 'La restauration a échoué. Veuillez réessayer.';
    } finally {
        restoring.value = false;
    }
}

// ── WhatsApp template ───────────────────────────────────────────
const wtspTemplate    = ref('');
const savingTemplate  = ref(false);
const templateSaved   = ref(false);

const wtspPreview = computed(() =>
    wtspTemplate.value
        .replace('{nom}',   'Mohammed Alami')
        .replace('{date}',  'lundi 25 mai')
        .replace('{heure}', '10:30')
);

async function loadTemplate() {
    try {
        const res = await api('/api/settings/whatsapp_message');
        wtspTemplate.value = res.value ?? '';
    } catch { /* silencieux */ }
}

async function saveTemplate() {
    savingTemplate.value = true;
    templateSaved.value  = false;
    try {
        await api('/api/settings/whatsapp_message', 'PUT', { value: wtspTemplate.value });
        templateSaved.value = true;
        setTimeout(() => { templateSaved.value = false; }, 3000);
    } finally {
        savingTemplate.value = false;
    }
}

// ── Helpers ─────────────────────────────────────────────────────
function headers() {
    return { Authorization: `Bearer ${localStorage.getItem('token')}`, 'Content-Type': 'application/json' };
}

async function api(url, method = 'GET', body = null) {
    const opts = { method, headers: headers() };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        if (res.status === 401) throw new Error('Session expirée. Veuillez vous reconnecter.');
        if (res.status === 403) throw new Error('Accès refusé. Cette action est réservée au dentiste.');
        if (res.status === 500) throw new Error(data.error ?? data.message ?? 'Erreur serveur. Vérifiez que Laragon est bien démarré.');
        throw new Error(data.error ?? data.message ?? `Erreur ${res.status}.`);
    }
    return data;
}

function formatName(name) {
    // "2026-05-24_13-26" → "24/05/2026 à 13h26"
    const m = name.match(/^(\d{4})-(\d{2})-(\d{2})_(\d{2})-(\d{2})$/);
    if (!m) return name;
    return `${m[3]}/${m[2]}/${m[1]} à ${m[4]}h${m[5]}`;
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' Ko';
    return (bytes / 1024 / 1024).toFixed(1) + ' Mo';
}

onMounted(() => {
    loadBackups();
    loadTemplate();
});
</script>
