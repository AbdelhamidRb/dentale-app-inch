import { ref, readonly } from 'vue';
import axios from 'axios';

const updateAvailable = ref(false);
const localVersion    = ref('v1.0.0');
const latestVersion   = ref('v1.0.0');
const checking        = ref(false);
const updating        = ref(false);
const updateError     = ref(null);
const updateSuccess   = ref(null);

// Vérifie au démarrage si une MAJ interrompue existe
async function checkLock() {
    try {
        const { data } = await axios.get('/api/update/check-lock');
        if (data.interrupted) {
            console.warn('[Update] MAJ interrompue détectée — rollback effectué automatiquement.');
        }
    } catch {}
}

// Vérifie la version GitHub (résultat mis en cache 24h côté serveur)
async function checkVersion() {
    if (checking.value) return;
    checking.value = true;
    try {
        const { data } = await axios.get('/api/update/check');
        localVersion.value  = data.local;
        latestVersion.value = data.latest;
        updateAvailable.value = data.available;
    } catch {
        // Pas d'internet ou GitHub indisponible — on ignore silencieusement
    } finally {
        checking.value = false;
    }
}

// Lance la mise à jour
async function runUpdate() {
    if (updating.value) return;
    updating.value  = true;
    updateError.value   = null;
    updateSuccess.value = null;

    try {
        const { data } = await axios.post('/api/update/run');
        updateSuccess.value  = `Mise à jour réussie : ${data.version_before} → ${data.version_after}`;
        updateAvailable.value = false;

        // Recharge la page après 2s pour appliquer le nouveau code
        setTimeout(() => window.location.reload(), 2000);
    } catch (err) {
        updateError.value = err.response?.data?.error
            ?? 'Erreur inattendue. Votre application a été restaurée automatiquement.';
    } finally {
        updating.value = false;
    }
}

export function useUpdate() {
    return {
        updateAvailable: readonly(updateAvailable),
        localVersion:    readonly(localVersion),
        latestVersion:   readonly(latestVersion),
        checking:        readonly(checking),
        updating:        readonly(updating),
        updateError,
        updateSuccess,
        checkLock,
        checkVersion,
        runUpdate,
    };
}
