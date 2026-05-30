import { ref, readonly } from 'vue';

const updateAvailable = ref(false);
const localVersion    = ref('');
const latestVersion   = ref('');
const checking        = ref(false);
const updating        = ref(false);
const updateError     = ref(null);
const updateSuccess   = ref(null);

function authHeaders() {
    return {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
    };
}

async function checkLock() {
    try {
        const res  = await fetch('/api/update/check-lock', { headers: authHeaders() });
        const data = await res.json();
        if (data.interrupted) {
            console.warn('[Update] MAJ interrompue détectée — rollback effectué automatiquement.');
        }
    } catch {}
}

async function checkVersion() {
    if (checking.value) return;
    checking.value    = true;
    updateError.value = null;
    try {
        const res  = await fetch('/api/update/check', { headers: authHeaders() });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message ?? `Erreur ${res.status}`);
        localVersion.value    = data.local;
        latestVersion.value   = data.latest;
        updateAvailable.value = data.available;
    } catch (err) {
        updateError.value = err.message ?? 'Impossible de vérifier la version.';
    } finally {
        checking.value = false;
    }
}

async function runUpdate() {
    if (updating.value) return;
    updating.value      = true;
    updateError.value   = null;
    updateSuccess.value = null;

    try {
        const res  = await fetch('/api/update/run', { method: 'POST', headers: authHeaders() });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error ?? `Erreur ${res.status}`);
        updateSuccess.value   = `Mise à jour réussie : ${data.version_before} → ${data.version_after}`;
        updateAvailable.value = false;
        setTimeout(() => window.location.reload(), 2000);
    } catch (err) {
        updateError.value = err.message ?? 'Erreur inattendue. Votre application a été restaurée automatiquement.';
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
