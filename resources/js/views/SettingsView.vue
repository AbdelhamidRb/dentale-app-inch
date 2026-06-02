<template>
    <div class="flex-1 min-h-0 overflow-y-auto bg-slate-50 px-3 sm:px-6 py-4 sm:py-6 pb-20 lg:pb-6">

        <!-- Titre -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-800">Paramètres</h1>
            <p class="text-sm text-slate-400 mt-0.5">Configuration du cabinet</p>
        </div>

        <!-- Onglets -->
        <div class="flex gap-1 mb-6 bg-white border border-slate-200 rounded-xl p-1 w-fit flex-wrap">
            <button
                v-for="tab in visibleTabs" :key="tab.id"
                @click="activeTab = tab.id"
                :class="['relative px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
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
                            BDD + images patients → <code class="bg-slate-100 px-1 rounded">{{ backupDir || 'X:\\backups\\dental-app\\' }}</code>
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

        <!-- ══ Onglet Actes ════════════════════════════════════════ -->
        <div v-if="activeTab === 'acts'" class="max-w-3xl">

            <!-- Barre d'actions -->
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-slate-500">{{ acts.length }} acte(s) configuré(s)</p>
                <button @click="openActForm(null)"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-medium rounded-xl transition-colors">
                    <Plus class="w-4 h-4" />
                    Nouvel acte
                </button>
            </div>

            <!-- Skeleton -->
            <div v-if="loadingActs" class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-50">
                <div v-for="i in 5" :key="i" class="flex items-center gap-3 px-4 py-3.5 animate-pulse">
                    <div class="h-4 bg-slate-100 rounded w-16 shrink-0"></div>
                    <div class="h-4 bg-slate-100 rounded flex-1"></div>
                    <div class="h-4 bg-slate-100 rounded w-20 shrink-0"></div>
                    <div class="h-4 bg-slate-100 rounded w-12 shrink-0"></div>
                </div>
            </div>

            <!-- Liste desktop (tableau) -->
            <div v-else-if="acts.length" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <!-- En-tête tableau (desktop) -->
                <div class="hidden sm:grid grid-cols-[5rem_1fr_7rem_5rem_5rem] gap-3 px-4 py-2.5
                            border-b border-slate-100 bg-slate-50">
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">Code</span>
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">Nom</span>
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">Prix (MAD)</span>
                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">Statut</span>
                    <span></span>
                </div>

                <div class="divide-y divide-slate-50">
                    <div v-for="act in acts" :key="act.id"
                         :class="['transition-colors', act.is_active ? '' : 'opacity-50']">

                        <!-- Desktop row -->
                        <div class="hidden sm:grid grid-cols-[5rem_1fr_7rem_5rem_5rem] gap-3 px-4 py-3 items-center">
                            <span class="text-xs font-mono text-slate-500">{{ act.code }}</span>
                            <span class="text-sm font-medium text-slate-800 truncate">{{ act.name }}</span>
                            <span class="text-sm text-slate-700 font-semibold">{{ Number(act.base_price).toFixed(0) }} MAD</span>
                            <button @click="toggleActStatus(act)"
                                :class="['text-xs font-medium px-2 py-0.5 rounded-full transition-colors',
                                         act.is_active
                                             ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'
                                             : 'bg-slate-100 text-slate-500 hover:bg-slate-200']">
                                {{ act.is_active ? 'Actif' : 'Inactif' }}
                            </button>
                            <div class="flex items-center gap-1 justify-end">
                                <button @click="openActForm(act)"
                                    class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <Pencil class="w-3.5 h-3.5" />
                                </button>
                                <button @click="confirmDeleteAct(act)"
                                    :disabled="act.in_use"
                                    :title="act.in_use ? 'Utilisé dans des consultations' : 'Supprimer'"
                                    :class="['p-1.5 rounded-lg transition-colors',
                                             act.in_use
                                                 ? 'text-slate-200 cursor-not-allowed'
                                                 : 'text-slate-400 hover:text-red-600 hover:bg-red-50']">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Mobile card -->
                        <div class="sm:hidden px-4 py-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-[10px] font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">{{ act.code }}</span>
                                        <button @click="toggleActStatus(act)"
                                            :class="['text-[10px] font-medium px-2 py-0.5 rounded-full transition-colors',
                                                     act.is_active
                                                         ? 'bg-emerald-100 text-emerald-700'
                                                         : 'bg-slate-100 text-slate-500']">
                                            {{ act.is_active ? 'Actif' : 'Inactif' }}
                                        </button>
                                        <span v-if="act.in_use"
                                            class="text-[10px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full font-medium">
                                            Utilisé
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-slate-800 truncate">{{ act.name }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ Number(act.base_price).toFixed(0) }} MAD</p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button @click="openActForm(act)"
                                        class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDeleteAct(act)"
                                        :disabled="act.in_use"
                                        :class="['p-2 rounded-lg transition-colors',
                                                 act.in_use
                                                     ? 'text-slate-200 cursor-not-allowed'
                                                     : 'text-slate-400 hover:text-red-600 hover:bg-red-50']">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vide -->
            <div v-else class="bg-white rounded-2xl border border-slate-200 py-16 flex flex-col items-center gap-3 text-slate-400">
                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center">
                    <Stethoscope class="w-7 h-7 text-slate-300" />
                </div>
                <p class="text-sm font-medium">Aucun acte configuré</p>
                <button @click="openActForm(null)"
                    class="text-xs text-blue-600 hover:underline font-medium">
                    Ajouter le premier acte
                </button>
            </div>
        </div>

        <!-- ══ Onglet Archivage ══════════════════════════════════════ -->
        <div v-if="activeTab === 'archive'" class="space-y-4 max-w-2xl">

            <!-- Configuration -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <p class="font-semibold text-slate-800 mb-1">Archivage automatique</p>
                <p class="text-xs text-slate-400 mb-4">
                    Les patients sans visite terminée depuis ce nombre de mois sont archivés automatiquement chaque nuit.
                    Mettre <strong>0</strong> pour désactiver.
                </p>

                <div class="flex items-center gap-3">
                    <div class="relative w-32">
                        <input
                            v-model.number="archiveMonths"
                            type="number" min="0" max="120" step="1"
                            class="w-full px-3 py-2 pr-14 border border-slate-200 rounded-xl text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">mois</span>
                    </div>
                    <button
                        @click="saveArchiveMonths"
                        :disabled="savingArchive"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50
                               text-white text-sm font-medium rounded-xl transition-colors"
                    >
                        {{ savingArchive ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                    <span v-if="archiveSaved" class="text-sm text-green-600 flex items-center gap-1">
                        <CheckCircle class="w-4 h-4" /> Enregistré
                    </span>
                </div>

                <p v-if="archiveMonths === 0" class="mt-3 text-xs text-amber-600 bg-amber-50 px-3 py-2 rounded-lg">
                    Archivage automatique désactivé.
                </p>
                <p v-else class="mt-3 text-xs text-slate-400 flex items-center gap-1.5">
                    <Clock class="w-3.5 h-3.5" />
                    Chaque nuit à 01h00 — patients inactifs depuis plus de {{ archiveMonths }} mois
                </p>
            </div>

            <!-- Prévisualisation -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-slate-800">Patients concernés</p>
                        <p class="text-xs text-slate-400 mt-0.5">Seraient archivés avec la configuration actuelle</p>
                    </div>
                    <button
                        @click="loadArchivePreview"
                        :disabled="loadingPreview"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600
                               border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors disabled:opacity-40"
                    >
                        <RotateCcw class="w-3.5 h-3.5" :class="loadingPreview ? 'animate-spin' : ''" />
                        Actualiser
                    </button>
                </div>

                <div v-if="loadingPreview" class="py-8 flex justify-center text-slate-400 text-sm">
                    Analyse en cours…
                </div>

                <div v-else-if="archivePreviewPatients.length === 0" class="py-8 text-center text-sm text-slate-400">
                    <Archive class="w-8 h-8 mx-auto mb-2 text-slate-200" />
                    Aucun patient à archiver pour le moment
                </div>

                <div v-else class="divide-y divide-slate-50">
                    <div v-for="p in archivePreviewPatients" :key="p.id"
                         class="flex items-center gap-3 px-5 py-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-700">{{ p.full_name }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ p.numero_dossier }} · Créé le {{ p.created_at }}</p>
                        </div>
                        <span v-if="p.has_critical_alerts"
                              class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full shrink-0">
                            Alerte médicale
                        </span>
                    </div>
                </div>

                <div v-if="archivePreviewPatients.length > 0"
                     class="px-5 py-3 bg-amber-50 border-t border-amber-100 flex items-center justify-between">
                    <p class="text-xs text-amber-700">
                        <strong>{{ archivePreviewPatients.length }}</strong> patient(s) seront archivés à la prochaine exécution
                    </p>
                </div>
            </div>
        </div>

        <!-- ══ Onglet Réseau ═════════════════════════════════════════ -->
        <div v-if="activeTab === 'reseau'" class="space-y-4 max-w-2xl">
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <p class="font-semibold text-slate-800 mb-1">Adresse de l'application</p>
                <p class="text-xs text-slate-400 mb-5">
                    Communiquez ce lien à votre assistante pour qu'elle accède à l'application depuis son appareil (même réseau Wi-Fi).
                </p>

                <div v-if="networkLoading" class="h-16 flex items-center justify-center">
                    <div class="w-5 h-5 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"/>
                </div>

                <template v-else-if="networkUrl">
                    <!-- Adresse affichée -->
                    <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 mb-3">
                        <span class="flex-1 font-mono text-lg font-semibold text-slate-800 select-all">{{ networkUrl }}</span>
                        <button @click="copyNetwork"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors shrink-0"
                                :class="copied ? 'bg-green-100 text-green-700' : 'bg-blue-600 text-white hover:bg-blue-700'">
                            <CheckCircle v-if="copied" class="w-3.5 h-3.5"/>
                            <span>{{ copied ? 'Copié !' : 'Copier' }}</span>
                        </button>
                    </div>
                    <p class="text-xs text-slate-400 flex items-center gap-1.5">
                        <Clock class="w-3.5 h-3.5 shrink-0"/>
                        L'adresse peut changer si le réseau Wi-Fi change. Rechargez cette page pour obtenir l'adresse actuelle.
                    </p>
                </template>

                <div v-else class="text-sm text-amber-600 bg-amber-50 px-4 py-3 rounded-xl">
                    Aucune adresse réseau détectée. Vérifiez que le PC est bien connecté au Wi-Fi du cabinet.
                </div>
            </div>
        </div>

        <!-- ── Modal formulaire acte ────────────────────────────────── -->
        <Teleport to="body">
        <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0"
                    leave-active-class="transition duration-100" leave-to-class="opacity-0">
            <div v-if="showActForm"
                 class="fixed inset-0 z-[400] flex items-end sm:items-center justify-center bg-black/40 px-0 sm:px-4"
                 @click.self="showActForm = false">
                <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-5">
                        <p class="font-semibold text-slate-800">
                            {{ editingAct ? 'Modifier l\'acte' : 'Nouvel acte' }}
                        </p>
                        <button @click="showActForm = false" class="text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Code -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Code *</label>
                            <input v-model="actForm.code" type="text" placeholder="EX001"
                                class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="actErrors.code ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="actErrors.code" class="text-xs text-red-500 mt-1">{{ actErrors.code }}</p>
                        </div>

                        <!-- Nom -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Nom de l'acte *</label>
                            <input v-model="actForm.name" type="text" placeholder="Détartrage complet"
                                class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="actErrors.name ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="actErrors.name" class="text-xs text-red-500 mt-1">{{ actErrors.name }}</p>
                        </div>

                        <!-- Prix -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Prix de base (MAD) *</label>
                            <div class="relative">
                                <input v-model.number="actForm.base_price" type="number" min="0" step="10" placeholder="500"
                                    class="w-full px-3 py-2 pr-14 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="actErrors.base_price ? 'border-red-400' : 'border-slate-200'" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">MAD</span>
                            </div>
                            <p v-if="actErrors.base_price" class="text-xs text-red-500 mt-1">{{ actErrors.base_price }}</p>
                        </div>

                        <!-- Statut -->
                        <div class="flex items-center justify-between py-1">
                            <div>
                                <p class="text-sm font-medium text-slate-700">Acte actif</p>
                                <p class="text-xs text-slate-400">Visible lors de la création de RDV</p>
                            </div>
                            <button @click="actForm.is_active = !actForm.is_active"
                                :class="['relative inline-flex w-10 h-6 rounded-full transition-colors',
                                         actForm.is_active ? 'bg-blue-600' : 'bg-slate-200']">
                                <span :class="['absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform',
                                               actForm.is_active ? 'translate-x-4' : '']"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Erreur globale -->
                    <p v-if="actSaveError" class="mt-3 text-xs text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ actSaveError }}</p>

                    <!-- Actions -->
                    <div class="flex gap-2 mt-5">
                        <button @click="showActForm = false"
                            class="flex-1 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50">
                            Annuler
                        </button>
                        <button @click="saveAct" :disabled="savingAct"
                            class="flex-1 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-xl transition-colors">
                            {{ savingAct ? 'Enregistrement…' : (editingAct ? 'Modifier' : 'Créer') }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
        </Teleport>

        <!-- ── Confirm suppression acte ────────────────────────────── -->
        <ConfirmModal
            :model-value="!!deleteActTarget"
            @update:model-value="v => { if (!v) deleteActTarget = null }"
            title="Supprimer l'acte"
            :subtitle="deleteActTarget?.name"
            message="Cet acte sera <strong>définitivement supprimé</strong>."
            confirm-label="Supprimer"
            @confirm="doDeleteAct" />

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
import { DatabaseBackup, CheckCircle, XCircle, Clock, RotateCcw, TriangleAlert,
         Plus, Pencil, Trash2, X, Stethoscope, Archive } from 'lucide-vue-next';
import ConfirmModal from '../components/ui/ConfirmModal.vue';

const isDentist = computed(() => {
    try { return JSON.parse(localStorage.getItem('user') || '{}').role === 'DENTIST'; }
    catch { return false; }
});

const tabs = [
    { id: 'backup',   label: 'Sauvegardes' },
    { id: 'whatsapp', label: 'WhatsApp' },
    { id: 'acts',     label: 'Actes' },
    { id: 'archive',  label: 'Archivage' },
    { id: 'reseau',   label: 'Réseau' },
];

const visibleTabs = computed(() =>
    isDentist.value ? tabs : tabs.filter(t => t.id === 'whatsapp')
);

const activeTab = ref(isDentist.value ? 'backup' : 'whatsapp');

// ── Backup ──────────────────────────────────────────────────────
const backups     = ref([]);
const backupDir   = ref('');
const loadingList = ref(false);
const backing     = ref(false);
const backupMsg   = ref('');
const backupOk    = ref(false);

async function loadBackups() {
    loadingList.value = true;
    try {
        const res = await api('/api/backup/list');
        backups.value  = res.backups ?? [];
        backupDir.value = res.backup_dir ?? '';
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
        backupMsg.value = 'Restauration terminée — rechargement dans 3 secondes…';
        activeTab.value = 'backup';
        setTimeout(() => { window.location.reload(); }, 3000);
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
        if (res.status === 422 && data.errors) {
            const err = new Error('Données invalides.');
            err.fields = Object.fromEntries(
                Object.entries(data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
            );
            throw err;
        }
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

// ── Actes ────────────────────────────────────────────────────────
const acts        = ref([]);
const loadingActs = ref(false);
const showActForm = ref(false);
const editingAct  = ref(null);
const savingAct   = ref(false);
const actSaveError = ref('');
const deleteActTarget = ref(null);

const actFormDefault = () => ({ code: '', name: '', base_price: 0, is_active: true });
const actForm  = ref(actFormDefault());
const actErrors = ref({});

async function loadActs() {
    loadingActs.value = true;
    try {
        const res = await api('/api/catalog-acts/all');
        acts.value = res.acts ?? [];
    } finally {
        loadingActs.value = false;
    }
}

function openActForm(act) {
    editingAct.value  = act;
    actErrors.value   = {};
    actSaveError.value = '';
    actForm.value = act
        ? { code: act.code, name: act.name, base_price: Number(act.base_price), is_active: act.is_active }
        : actFormDefault();
    showActForm.value = true;
}

async function saveAct() {
    actErrors.value   = {};
    actSaveError.value = '';
    savingAct.value   = true;
    try {
        if (editingAct.value) {
            const res = await api(`/api/catalog-acts/${editingAct.value.id}`, 'PUT', actForm.value);
            const idx = acts.value.findIndex(a => a.id === editingAct.value.id);
            if (idx !== -1) acts.value[idx] = res.act;
        } else {
            const res = await api('/api/catalog-acts', 'POST', actForm.value);
            acts.value.push(res.act);
            acts.value.sort((a, b) => a.code.localeCompare(b.code));
        }
        showActForm.value = false;
    } catch (e) {
        if (e.fields) { actErrors.value = e.fields; }
        else { actSaveError.value = e.message ?? 'Erreur lors de l\'enregistrement.'; }
    } finally {
        savingAct.value = false;
    }
}

async function toggleActStatus(act) {
    try {
        const res = await api(`/api/catalog-acts/${act.id}`, 'PUT', { is_active: !act.is_active });
        const idx = acts.value.findIndex(a => a.id === act.id);
        if (idx !== -1) acts.value[idx] = res.act;
    } catch { /* silencieux */ }
}

function confirmDeleteAct(act) { deleteActTarget.value = act; }

async function doDeleteAct() {
    if (!deleteActTarget.value) return;
    const actId = deleteActTarget.value.id;
    try {
        await api(`/api/catalog-acts/${actId}`, 'DELETE');
        acts.value = acts.value.filter(a => a.id !== actId);
    } catch (e) {
        actSaveError.value = e.message ?? 'Impossible de supprimer cet acte.';
    } finally {
        deleteActTarget.value = null;
    }
}

// ── Archivage ────────────────────────────────────────────────────
const archiveMonths         = ref(18);
const savingArchive         = ref(false);
const archiveSaved          = ref(false);
const loadingPreview        = ref(false);
const archivePreviewPatients = ref([]);

async function loadArchiveConfig() {
    try {
        const res = await api('/api/settings/archive_after_months');
        archiveMonths.value = parseInt(res.value ?? '18');
    } catch { /* silencieux */ }
}

async function saveArchiveMonths() {
    savingArchive.value = true;
    archiveSaved.value  = false;
    try {
        await api('/api/settings/archive_after_months', 'PUT', { value: String(archiveMonths.value) });
        archiveSaved.value = true;
        setTimeout(() => { archiveSaved.value = false; }, 3000);
        await loadArchivePreview();
    } finally {
        savingArchive.value = false;
    }
}

async function loadArchivePreview() {
    loadingPreview.value = true;
    try {
        const res = await api('/api/patients/archive-preview');
        archivePreviewPatients.value = res.patients ?? [];
    } catch { archivePreviewPatients.value = []; }
    finally { loadingPreview.value = false; }
}

// ── Réseau ──────────────────────────────────────────────────────
const networkUrl     = ref(null);
const networkLoading = ref(false);
const copied         = ref(false);

async function loadNetwork() {
    networkLoading.value = true;
    try {
        const res = await api('/api/network');
        networkUrl.value = res.url;
    } finally {
        networkLoading.value = false;
    }
}

function copyNetwork() {
    if (!networkUrl.value) return;
    const el = document.createElement('textarea');
    el.value = networkUrl.value;
    el.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}

onMounted(() => {
    loadBackups();
    loadTemplate();
    loadActs();
    loadArchiveConfig();
    loadArchivePreview();
    loadNetwork();
});
</script>
