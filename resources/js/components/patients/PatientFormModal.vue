<template>
  <!-- Overlay sombre derrière la modal -->
  <div
    class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

      <!-- ── En-tête modal ───────────────────────────────────────── -->
      <div class="flex items-center justify-between p-5 border-b border-slate-100">
        <h2 class="font-semibold text-slate-800">
          {{ isEdit ? 'Modifier le patient' : 'Nouveau patient' }}
        </h2>
        <button @click="$emit('close')"
          class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors text-slate-400">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- ── Alerte doublon ──────────────────────────────────────── -->
      <div v-if="doublon"
        class="mx-5 mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
        <p class="text-sm font-medium text-amber-800">Patient similaire détecté</p>
        <p class="text-xs text-amber-600 mt-0.5">
          {{ doublon.name }} ({{ doublon.numero_dossier }}) existe déjà.
        </p>
        <button @click="doublon = null"
          class="text-xs text-amber-700 underline mt-1">
          Continuer quand même
        </button>
      </div>

      <!-- ── Formulaire ──────────────────────────────────────────── -->
      <form @submit.prevent="handleSubmit" class="p-5 space-y-4">

        <!-- Nom + Prénom sur la même ligne -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1.5">
              Prénom <span class="text-red-400">*</span>
            </label>
            <input v-model="form.first_name" required
              class="input" placeholder="Mohammed" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1.5">
              Nom <span class="text-red-400">*</span>
            </label>
            <input v-model="form.last_name" required
              class="input" placeholder="Alami" />
          </div>
        </div>

        <!-- Téléphone -->
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1.5">
            Téléphone <span class="text-red-400">*</span>
          </label>
          <input v-model="form.phone" required type="tel"
            class="input" placeholder="06 XX XX XX XX" />
        </div>

        <!-- Date de naissance + Sexe -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1.5">
              Date de naissance
            </label>
            <input v-model="form.birth_date" type="date"
              class="input" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1.5">Sexe</label>
            <select v-model="form.gender" class="input">
              <option value="">Non précisé</option>
              <option value="M">Masculin</option>
              <option value="F">Féminin</option>
            </select>
          </div>
        </div>

        <!-- Couverture médicale -->
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1.5">
            Couverture médicale
          </label>
          <select v-model="form.couverture" class="input">
            <option value="AUCUNE">Aucune</option>
            <option value="CNSS">CNSS</option>
            <option value="CNOPS">CNOPS</option>
            <option value="RAMED">RAMED</option>
            <option value="ASSURANCE">Assurance privée</option>
          </select>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1.5">
            Notes générales
          </label>
          <textarea v-model="form.notes" rows="3"
            class="input resize-none"
            placeholder="Remarques sur le patient (patient anxieux, préfère les matins...)">
          </textarea>
        </div>

        <!-- Message erreur -->
        <div v-if="error"
          class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
          {{ error }}
        </div>

        <!-- Boutons -->
        <div class="flex gap-2 pt-1">
          <button type="button" @click="$emit('close')"
            class="flex-1 py-2.5 border border-slate-300 rounded-lg text-sm
                   text-slate-600 hover:bg-slate-50 transition-colors">
            Annuler
          </button>
          <button type="submit" :disabled="loading"
            class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400
                   text-white text-sm font-medium rounded-lg transition-colors
                   flex items-center justify-center gap-2">
            <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ loading ? 'Enregistrement...' : (isEdit ? 'Modifier' : 'Créer le patient') }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
  patient: { type: Object, default: null } // null = création
})
const emit = defineEmits(['close', 'saved'])

const isEdit  = computed(() => !!props.patient)
const loading = ref(false)
const error   = ref(null)
const doublon = ref(null)

// Formulaire pré-rempli si modification
const form = reactive({
  first_name: '',
  last_name:  '',
  phone:      '',
  birth_date: '',
  gender:     '',
  couverture: 'AUCUNE',
  notes:      '',
})

// Pré-remplit le formulaire si on édite
watch(() => props.patient, (p) => {
  if (p) Object.assign(form, {
    first_name: p.first_name,
    last_name:  p.last_name,
    phone:      p.phone,
    birth_date: p.birth_date || '',
    gender:     p.gender || '',
    couverture: p.couverture || 'AUCUNE',
    notes:      p.notes || '',
  })
}, { immediate: true })

async function handleSubmit() {
  loading.value = true
  error.value   = null
  doublon.value = null
  try {
    const data = isEdit.value
      ? { ...form, id: props.patient.id }
      : { ...form }
    emit('saved', data, isEdit.value)
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.input {
  @apply w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm
         focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
</style>