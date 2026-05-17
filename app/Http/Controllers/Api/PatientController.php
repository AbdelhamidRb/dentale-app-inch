<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\MedicalAlert;
use App\Models\PatientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    // PATIENTS CRUD
    // ═══════════════════════════════════════════════════════════════

    // ─── GET /api/patients ────────────────────────────────────────
    // Liste paginée avec recherche et filtres
    public function index(Request $request)
    {
        $query = Patient::active()
            ->with(['medicalAlerts', 'criticalAlerts'])
            ->latest();

        // Recherche par nom, téléphone, numéro dossier
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre patients archivés
        if ($request->boolean('archived')) {
            $query = Patient::where('is_archived', true)->latest();
        }

        $patients = $query->paginate(15);

        // Formate chaque patient pour le frontend
        return response()->json([
            'data' => $patients->map(function ($p) {
                return $this->formatPatient($p);
            }),
            'meta' => [
                'current_page' => $patients->currentPage(),
                'last_page'    => $patients->lastPage(),
                'total'        => $patients->total(),
            ]
        ]);
    }

    // ─── POST /api/patients ───────────────────────────────────────
    // Créer un nouveau patient
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => 'required|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'gender'     => 'nullable|in:M,F',
            'couverture' => 'nullable|in:CNSS,CNOPS,RAMED,ASSURANCE,AUCUNE',
            'notes'      => 'nullable|string',
        ]);

        // Vérifie doublon potentiel
        $doublon = null;
        if ($request->filled('birth_date')) {
            $doublon = Patient::where('first_name', $request->first_name)
                ->where('last_name', $request->last_name)
                ->where('birth_date', $request->birth_date)
                ->first();
        }

        // Crée le patient avec numéro de dossier auto
        $patient = Patient::create([
            'numero_dossier' => Patient::generateNumeroDossier(),
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'phone'          => $request->phone,
            'birth_date'     => $request->birth_date,
            'gender'         => $request->gender,
            'couverture'     => $request->couverture ?? 'AUCUNE',
            'notes'          => $request->notes,
        ]);

        return response()->json([
            'message' => 'Patient créé avec succès.',
            'patient' => $this->formatPatient($patient),
            // Avertissement si doublon détecté (pas bloquant)
            'doublon' => $doublon ? [
                'id'   => $doublon->id,
                'name' => $doublon->full_name,
                'numero_dossier' => $doublon->numero_dossier,
            ] : null,
        ], 201);
    }

    // ─── GET /api/patients/{id} ───────────────────────────────────
    // Fiche complète d'un patient
    public function show(Patient $patient)
    {
        $patient->load(['medicalAlerts', 'documents']);

        return response()->json([
            'patient'        => $this->formatPatient($patient),
            'medical_alerts' => $patient->medicalAlerts->map(fn($a) => $this->formatAlert($a)),
            'documents'      => $patient->documents->map(fn($d) => $this->formatDocument($d)),
        ]);
    }

    // ─── PUT /api/patients/{id} ───────────────────────────────────
    // Modifier un patient
    public function update(Request $request, Patient $patient)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => 'required|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'gender'     => 'nullable|in:M,F',
            'couverture' => 'nullable|in:CNSS,CNOPS,RAMED,ASSURANCE,AUCUNE',
            'notes'      => 'nullable|string',
            'status'     => 'nullable|in:ACTIF,INACTIF,DECEDE',
        ]);

        $patient->update($request->only([
            'first_name',
            'last_name',
            'phone',
            'birth_date',
            'gender',
            'couverture',
            'notes',
            'status',
        ]));

        return response()->json([
            'message' => 'Patient modifié avec succès.',
            'patient' => $this->formatPatient($patient),
        ]);
    }

    // ─── DELETE /api/patients/{id} ────────────────────────────────
    // Archiver un patient (pas de suppression physique)
    public function destroy(Patient $patient)
    {
        $patient->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return response()->json([
            'message' => 'Patient archivé avec succès.'
        ]);
    }

    // ─── POST /api/patients/{id}/restore ─────────────────────────
    // Réactiver un patient archivé
    public function restore(Patient $patient)
    {
        $patient->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return response()->json([
            'message' => 'Patient réactivé avec succès.'
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // ALERTES MÉDICALES
    // ═══════════════════════════════════════════════════════════════

    // ─── POST /api/patients/{id}/alerts ──────────────────────────
    public function storeAlert(Request $request, Patient $patient)
    {
        $request->validate([
            'type'        => 'required|in:ALLERGIE,MALADIE,MEDICATION',
            'description' => 'required|string',
            'severity'    => 'required|in:ROUGE,ORANGE,JAUNE',
        ]);

        $alert = $patient->medicalAlerts()->create([
            'type'        => $request->type,
            'description' => $request->description,
            'severity'    => $request->severity,
            'created_by'  => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Alerte ajoutée.',
            'alert'   => $this->formatAlert($alert),
        ], 201);
    }

    // ─── DELETE /api/patients/{id}/alerts/{alertId} ───────────────
    public function destroyAlert(Patient $patient, MedicalAlert $alert)
    {
        // Vérifie que l'alerte appartient bien à ce patient
        if ($alert->patient_id !== $patient->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $alert->delete();

        return response()->json(['message' => 'Alerte supprimée.']);
    }

    // ═══════════════════════════════════════════════════════════════
    // DOCUMENTS & RADIOS
    // ═══════════════════════════════════════════════════════════════

    // ─── POST /api/patients/{id}/documents ───────────────────────
    public function uploadDocument(Request $request, Patient $patient)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:10240',
            'type' => 'required|in:RADIO,PHOTO,PDF,CONSENTEMENT,AUTRE',
        ]);

        $file          = $request->file('file');
        $originalName  = $file->getClientOriginalName();
        $storedName    = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Stocke dans storage/app/public/patients/{id}/documents/
        $path = $file->storeAs(
            "patients/{$patient->id}/documents",
            $storedName,
            'public'
        );

        $document = $patient->documents()->create([
            'type'          => $request->type,
            'original_name' => $originalName,
            'stored_name'   => $storedName,
            'path'          => $path,
            'size'          => $file->getSize(),
            'uploaded_by'   => auth()->id(),
        ]);

        return response()->json([
            'message'  => 'Document uploadé.',
            'document' => $this->formatDocument($document),
        ], 201);
    }

    // ─── DELETE /api/patients/{id}/documents/{docId} ──────────────
    public function destroyDocument(Patient $patient, PatientDocument $document)
    {
        if ($document->patient_id !== $patient->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Supprime le fichier physique du storage
        Storage::disk('public')->delete($document->path);

        $document->delete();

        return response()->json(['message' => 'Document supprimé.']);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS — Format uniforme pour le frontend
    // ═══════════════════════════════════════════════════════════════

    private function formatPatient(Patient $p): array
    {
        return [
            'id'              => $p->id,
            'numero_dossier'  => $p->numero_dossier,
            'first_name'      => $p->first_name,
            'last_name'       => $p->last_name,
            'full_name'       => $p->full_name,
            'phone'           => $p->phone,
            'birth_date'      => $p->birth_date?->format('Y-m-d'),
            'age'             => $p->age,
            'gender'          => $p->gender,
            'couverture'      => $p->couverture,
            'notes'           => $p->notes,
            'status'          => $p->status,
            'is_archived'     => $p->is_archived,
            'no_show_count'   => $p->no_show_count,
            'has_critical_alerts' => $p->criticalAlerts->count() > 0,
            'alerts_count'    => $p->medicalAlerts->count(),
            'created_at'      => $p->created_at->format('d/m/Y'),
        ];
    }

    private function formatAlert(MedicalAlert $a): array
    {
        return [
            'id'          => $a->id,
            'type'        => $a->type,
            'description' => $a->description,
            'severity'    => $a->severity,
            'created_at'  => $a->created_at->format('d/m/Y'),
        ];
    }

    private function formatDocument(PatientDocument $d): array
    {
        return [
            'id'            => $d->id,
            'type'          => $d->type,
            'original_name' => $d->original_name,
            'url'           => $d->url,
            'size'          => $d->readable_size,
            'created_at'    => $d->created_at->format('d/m/Y'),
        ];
    }
}
