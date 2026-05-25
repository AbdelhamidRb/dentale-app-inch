<template>
    <div class="relative" ref="containerRef">

        <!-- ── Bouton cloche ─────────────────────────────────────── -->
        <button
            @click="toggle"
            class="relative p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
        >
            <Bell class="w-5 h-5" />
            <!-- Badge : RDV non encore notifiés -->
            <span
                v-if="pendingCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[17px] h-[17px] bg-red-500 text-white
                       text-[10px] font-bold rounded-full flex items-center justify-center px-0.5"
            >
                {{ pendingCount }}
            </span>
        </button>

        <!-- ── Dropdown ───────────────────────────────────────────── -->
        <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="open"
                :style="dropdownStyle"
                class="fixed z-[500] bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden
                       w-[calc(100vw-2rem)] max-w-sm sm:w-80"
            >
                <!-- Header -->
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Rappels WhatsApp</p>
                        <p class="text-xs text-slate-400 mt-0.5">RDV de demain — {{ tomorrowLabel }}</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span v-if="pendingCount > 0"
                              class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">
                            {{ pendingCount }} à notifier
                        </span>
                        <span v-else-if="appointments.length > 0"
                              class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">
                            Tous notifiés ✓
                        </span>
                    </div>
                </div>

                <!-- Contenu -->
                <div class="max-h-80 overflow-y-auto">
                    <!-- Loading -->
                    <div v-if="loading" class="py-6 flex items-center justify-center gap-2 text-slate-400">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span class="text-xs">Chargement…</span>
                    </div>

                    <!-- Vide -->
                    <div v-else-if="!appointments.length"
                         class="py-8 flex flex-col items-center gap-2 text-slate-400">
                        <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-xs">Aucun RDV demain</p>
                    </div>

                    <!-- Liste RDV -->
                    <div v-else class="divide-y divide-slate-50">
                        <div
                            v-for="appt in appointments"
                            :key="appt.id"
                            :class="['flex items-center gap-3 px-4 py-3 transition-colors',
                                     appt.whatsapp_notified_at ? 'bg-green-50/50' : 'hover:bg-slate-50']"
                        >
                            <!-- Heure -->
                            <div class="w-11 shrink-0 text-center">
                                <span class="text-sm font-bold text-slate-700">
                                    {{ appt.start_time?.slice(0, 5) }}
                                </span>
                            </div>

                            <!-- Infos patient -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">
                                    {{ appt.patient?.full_name }}
                                </p>
                                <div class="flex items-center gap-1.5">
                                    <a :href="`tel:${appt.patient?.phone}`"
                                       class="text-xs text-blue-500 hover:text-blue-700 hover:underline font-medium truncate"
                                       @click.stop>
                                        {{ appt.patient?.phone }}
                                    </a>
                                    <span v-if="appt.whatsapp_notified_at"
                                          class="text-[10px] text-green-600 font-medium shrink-0">
                                        · ✓ Notifié
                                    </span>
                                </div>
                            </div>

                            <!-- Bouton WhatsApp ou check -->
                            <a
                                v-if="!appt.whatsapp_notified_at"
                                :href="whatsappLink(appt)"
                                target="_blank"
                                rel="noopener"
                                @click="markNotified(appt)"
                                class="shrink-0 flex items-center gap-1.5 px-2.5 py-1.5 bg-green-500 hover:bg-green-600
                                       text-white text-xs font-medium rounded-lg transition-colors"
                            >
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                Envoyer
                            </a>

                            <!-- Déjà notifié -->
                            <div v-else class="shrink-0 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="appointments.length > 0"
                     class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/60">
                    <p class="text-[11px] text-slate-400 text-center">
                        Cliquez <strong>Envoyer</strong> → WhatsApp s'ouvre avec le message pré-rempli
                    </p>
                </div>
            </div>
        </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Bell } from 'lucide-vue-next';

const open          = ref(false);
const loading       = ref(false);
const appointments  = ref([]);
const template      = ref('Bonjour {nom}, nous vous rappelons votre rendez-vous demain {date} à {heure} au cabinet dentaire. Merci de confirmer votre présence. 🦷');
const containerRef  = ref(null);
const btnRect       = ref(null);

const dropdownStyle = computed(() => {
    if (!btnRect.value) return {};
    const margin = 8;
    const panelW = Math.min(window.innerWidth - 32, 320);
    let left = btnRect.value.right - panelW;
    if (left < 16) left = 16;
    return {
        top:  `${btnRect.value.bottom + margin}px`,
        left: `${left}px`,
    };
});

const tomorrowDate = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return d.toISOString().split('T')[0];
});

const tomorrowLabel = computed(() =>
    new Date(tomorrowDate.value + 'T00:00:00').toLocaleDateString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long',
    })
);

const pendingCount = computed(() =>
    appointments.value.filter(a => !a.whatsapp_notified_at).length
);

// ── Ouvrir / fermer ───────────────────────────────────────────────
async function toggle() {
    if (!open.value) {
        btnRect.value = containerRef.value?.getBoundingClientRect() ?? null;
    }
    open.value = !open.value;
    if (open.value && !appointments.value.length) {
        await loadData();
    }
}

// ── Fermer au clic extérieur ──────────────────────────────────────
function onClickOutside(e) {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        open.value = false;
    }
}
onMounted(() => {
    document.addEventListener('mousedown', onClickOutside)
    loadData() // charge les données au montage pour afficher le badge sans cliquer
});
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside));

// ── Chargement ────────────────────────────────────────────────────
async function loadData() {
    loading.value = true;
    const h = headers();
    try {
        const [apptRes, tmplRes] = await Promise.all([
            fetch(`/api/appointments?date=${tomorrowDate.value}`, { headers: h }).then(r => r.json()),
            fetch('/api/settings/whatsapp_message', { headers: h }).then(r => r.json()),
        ]);

        appointments.value = (apptRes.appointments ?? [])
            .filter(a => !['ANNULE', 'NO_SHOW'].includes(a.status))
            .sort((a, b) => (a.start_time ?? '').localeCompare(b.start_time ?? ''));

        if (tmplRes.value) template.value = tmplRes.value;
    } catch {
        // silencieux
    } finally {
        loading.value = false;
    }
}

// ── Marquer comme notifié ─────────────────────────────────────────
async function markNotified(appt) {
    // Optimiste : mise à jour immédiate côté UI
    appt.whatsapp_notified_at = new Date().toISOString();

    try {
        await fetch(`/api/appointments/${appt.id}/whatsapp-notified`, {
            method: 'PATCH',
            headers: headers(),
        });
    } catch {
        // Si erreur, on laisse quand même l'état visuel (non critique)
    }
}

// ── Helpers ───────────────────────────────────────────────────────
function headers() {
    return {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        Accept: 'application/json',
    };
}

function formatPhone(phone) {
    let p = (phone ?? '').replace(/[\s\-().]/g, '');
    if (p.startsWith('+'))   p = p.slice(1);
    else if (p.startsWith('00')) p = p.slice(2);
    else if (p.startsWith('0'))  p = '212' + p.slice(1);
    return p;
}

function whatsappLink(appt) {
    const phone   = formatPhone(appt.patient?.phone ?? '');
    const message = template.value
        .replace('{nom}',   appt.patient?.full_name ?? '')
        .replace('{heure}', appt.start_time?.slice(0, 5) ?? '')
        .replace('{date}',  tomorrowLabel.value);
    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}
</script>
