<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\ConsultationAct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    // GET /api/consultations
    // Liste filtrée : statut, patient, date
    // ═══════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        // En liste : on charge seulement les 2 premiers actes pour le résumé + le count total
        // La fiche complète (show) charge tous les actes en détail
        $query = Consultation::with([
            'patient:id,first_name,last_name,phone',
            'dentist:id,name',
            'acts.catalogAct:id,code,name',
        ])->withCount('acts')
          ->whereHas('patient', fn($q) => $q->where('is_archived', false))
          ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('patient_id')) {
            $query->forPatient($request->patient_id);
        }

        if ($request->boolean('en_cours')) {
            $query->enCours();
        }

        // ── Filtre par date ───────────────────────────────────────────
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $consultations = $query->paginate(15);

        return response()->json([
            'data' => $consultations->map(fn($c) => $this->format($c)),
            'meta' => [
                'current_page' => $consultations->currentPage(),
                'last_page'    => $consultations->lastPage(),
                'total'        => $consultations->total(),
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/consultations
    // Créer une consultation avec ses actes
    // ═══════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $request->validate([
            'patient_id'        => 'required|exists:patients,id',
            'appointment_id'    => [
                'nullable',
                'exists:appointments,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && Appointment::where('id', $value)
                            ->where('patient_id', $request->patient_id)
                            ->doesntExist()) {
                        $fail('Ce rendez-vous n\'appartient pas à ce patient.');
                    }
                },
            ],
            'status'            => 'nullable|in:EN_COURS,TERMINE',
            'notes'             => 'nullable|string',

            // Tableau d'actes
            'acts'              => 'nullable|array',
            'acts.*.catalog_act_id' => 'required|exists:catalog_acts,id',
            'acts.*.teeth'      => 'nullable|array',
            'acts.*.teeth.*'    => 'integer|min:11|max:85',
            'acts.*.price'      => 'required|numeric|min:0|max:999999',
            'acts.*.notes'      => 'nullable|string',
        ]);

        $consultation = DB::transaction(function () use ($request) {
            // ─── Création de la consultation ──────────────────────
            $consultation = Consultation::create([
                'patient_id'     => $request->patient_id,
                'appointment_id' => $request->appointment_id,
                'created_by'     => auth()->id(),
                'status'         => $request->status ?? 'EN_COURS',
                'notes'          => $request->notes,
                'session_dates'  => [now()->toDateString()],
            ]);

            // ─── Ajout des actes (batch insert) ──────────────────
            if ($request->filled('acts')) {
                $now  = now();
                $rows = array_map(fn($act) => [
                    'consultation_id' => $consultation->id,
                    'catalog_act_id'  => $act['catalog_act_id'],
                    'teeth'           => json_encode($act['teeth'] ?? []),
                    'price'           => round((float) $act['price']),
                    'notes'           => $act['notes'] ?? null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ], $request->acts);
                ConsultationAct::insert($rows);
            }

            $consultation->recalculateTotal();
            $this->markAppointmentTermine($request->appointment_id);

            return $consultation;
        });

        $consultation->load([
            'patient:id,first_name,last_name,phone',
            'dentist:id,name',
            'acts.catalogAct:id,code,name',
        ]);

        cache()->forget('dashboard_stats_v4_' . now()->format('Y-m'));

        return response()->json([
            'message'      => 'Consultation créée avec succès.',
            'consultation' => $this->format($consultation),
        ], 201);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /api/consultations/{id}
    // Fiche complète
    // ═══════════════════════════════════════════════════════════════
    public function show(Consultation $consultation)
    {
        $consultation->load([
            'patient:id,first_name,last_name,phone,birth_date,gender',
            'dentist:id,name,avatar',
            'appointment:id,scheduled_date,start_time,end_time',
            'acts.catalogAct:id,code,name,base_price',
        ]);

        return response()->json([
            'consultation' => $this->format($consultation, detailed: true),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // PUT /api/consultations/{id}
    // Modifier notes, statut et actes
    // ═══════════════════════════════════════════════════════════════
    public function update(Request $request, Consultation $consultation)
    {
        $request->validate([
            'status'            => 'nullable|in:EN_COURS,TERMINE',
            'notes'             => 'nullable|string',
            'acts'              => 'nullable|array',
            'acts.*.id'         => 'nullable|exists:consultation_acts,id',
            'acts.*.catalog_act_id' => 'required|exists:catalog_acts,id',
            'acts.*.teeth'      => 'nullable|array',
            'acts.*.teeth.*'    => 'integer|min:11|max:85',
            'acts.*.price'      => 'required|numeric|min:0|max:999999',
            'acts.*.notes'      => 'nullable|string',
        ]);

        $consultation->update([
            'status' => $request->status ?? $consultation->status,
            'notes'  => $request->notes,
        ]);

        // ─── Sync des actes (remplace tout — batch insert) ───────
        if ($request->has('acts')) {
            DB::transaction(function () use ($request, $consultation) {
                $consultation->acts()->delete();

                if (!empty($request->acts)) {
                    $now  = now();
                    $rows = array_map(fn($act) => [
                        'consultation_id' => $consultation->id,
                        'catalog_act_id'  => $act['catalog_act_id'],
                        'teeth'           => json_encode($act['teeth'] ?? []),
                        'price'           => round((float) $act['price']),
                        'notes'           => $act['notes'] ?? null,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ], $request->acts);
                    ConsultationAct::insert($rows);
                }
            });
        }

        $consultation->recalculateTotal();
        $consultation->load([
            'patient:id,first_name,last_name,phone',
            'dentist:id,name',
            'acts.catalogAct:id,code,name',
        ]);

        return response()->json([
            'message'      => 'Consultation mise à jour.',
            'consultation' => $this->format($consultation),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/consultations/{id}/session
    // Ajouter une nouvelle date de séance
    // ═══════════════════════════════════════════════════════════════
    public function addSession(Request $request, Consultation $consultation)
    {
        $request->validate([
            'date'           => 'required|date',
            'appointment_id' => 'nullable|exists:appointments,id',
        ]);

        if ($consultation->status !== 'EN_COURS') {
            return response()->json([
                'message' => 'Seules les consultations EN_COURS peuvent recevoir une nouvelle séance.',
            ], 422);
        }

        $consultation->addSessionDate($request->date);

        // ─── Cas 3 : passe le RDV source à TERMINE ───────────────
        $this->markAppointmentTermine($request->appointment_id);

        return response()->json([
            'message'       => 'Séance ajoutée.',
            'session_dates' => $consultation->fresh()->session_dates,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // PATCH /api/consultations/{id}/close
    // Clôturer une consultation (→ TERMINE)
    // ═══════════════════════════════════════════════════════════════
    public function close(Consultation $consultation)
    {
        $consultation->update(['status' => 'TERMINE']);

        // ─── Cas 2 : passe le RDV lié à TERMINE ──────────────────
        $this->markAppointmentTermine($consultation->appointment_id);

        $consultation->load([
            'patient:id,first_name,last_name,phone',
            'dentist:id,name',
            'acts.catalogAct:id,code,name',
        ]);

        cache()->forget('dashboard_stats_v4_' . now()->format('Y-m'));

        return response()->json([
            'message'      => 'Consultation clôturée.',
            'consultation' => $this->format($consultation),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // DELETE /api/consultations/{id}
    // Suppression (dentiste uniquement — protégé par middleware)
    // ═══════════════════════════════════════════════════════════════
    public function destroy(Consultation $consultation)
    {
        $consultation->acts()->delete();
        $consultation->delete();

        cache()->forget('dashboard_stats_v4_' . now()->format('Y-m'));

        return response()->json(['message' => 'Consultation supprimée.']);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER — Passe un RDV à TERMINE seulement si ce n'est pas déjà
    // TERMINE ou NO_SHOW (préserve l'état NO_SHOW)
    // ═══════════════════════════════════════════════════════════════
    private function markAppointmentTermine(?int $appointmentId): void
    {
        if (!$appointmentId) return;

        Appointment::where('id', $appointmentId)
            ->whereNotIn('status', ['TERMINE', 'NO_SHOW'])
            ->update(['status' => 'TERMINE']);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER — Format uniforme retourné au frontend
    // ═══════════════════════════════════════════════════════════════
    private function format(Consultation $c, bool $detailed = false): array
    {
        $base = [
            'id'            => $c->id,
            'status'        => $c->status,
            'notes'         => $c->notes,
            'total_price'   => (float) $c->total_price,
            'session_dates' => $c->session_dates ?? [],
            'sessions_count' => count($c->session_dates ?? []),

            'patient' => [
                'id'        => $c->patient->id,
                'full_name' => $c->patient->first_name . ' ' . $c->patient->last_name,
                'phone'     => $c->patient->phone,
            ],

            'dentist' => [
                'id'   => $c->dentist->id,
                'name' => $c->dentist->name,
            ],

            // Actes avec dents + prix
            'acts' => $c->acts->map(fn($a) => [
                'id'           => $a->id,
                'catalog_act'  => [
                    'id'   => $a->catalogAct->id,
                    'code' => $a->catalogAct->code,
                    'name' => $a->catalogAct->name,
                ],
                'teeth'        => $a->teeth ?? [],
                'price'        => (float) $a->price,
                'notes'        => $a->notes,
            ]),

            'appointment_id' => $c->appointment_id,
            'created_at'     => $c->created_at->format('d/m/Y'),
        ];

        // Détails supplémentaires pour la fiche complète
        if ($detailed && $c->relationLoaded('appointment') && $c->appointment) {
            $base['appointment'] = [
                'id'             => $c->appointment->id,
                'scheduled_date' => $c->appointment->scheduled_date->format('d/m/Y'),
                'start_time'     => substr($c->appointment->start_time, 0, 5),
                'end_time'       => substr($c->appointment->end_time, 0, 5),
            ];
        }

        if ($detailed && $c->relationLoaded('patient')) {
            $base['patient']['birth_date'] = $c->patient->birth_date?->format('Y-m-d');
            $base['patient']['gender']     = $c->patient->gender;
        }

        return $base;
    }
}
