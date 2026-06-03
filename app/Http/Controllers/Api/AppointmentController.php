<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CatalogAct;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    // GET /api/appointments?date=2026-05-05
    // Retourne tous les RDV d'un jour + les créneaux libres
    // ═══════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        // ─── Mode semaine : start_date + end_date ─────────────────────
        if ($request->has('start_date') && $request->has('end_date')) {
            $request->validate([
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
            ]);

            $appointments = Appointment::whereBetween('scheduled_date', [$request->start_date, $request->end_date])
                ->with([
                    'patient:id,first_name,last_name,phone',
                    'catalogActs:id,name,code',
                    'creator:id,name,role',
                ])
                ->orderBy('scheduled_date')
                ->orderBy('start_time')
                ->get();

            $grouped = $appointments
                ->groupBy(fn($a) => $a->scheduled_date->format('Y-m-d'))
                ->map(fn($group) => $group->map(fn($a) => $this->formatAppointment($a))->values());

            return response()->json([
                'start_date'   => $request->start_date,
                'end_date'     => $request->end_date,
                'appointments' => $grouped,
            ]);
        }

        // ─── Mode jour : date unique ───────────────────────────────────
        $request->validate([
            'date' => 'required|date'
        ]);

        $date = $request->date;

        // Charge tous les RDV du jour avec leurs relations
        $appointments = Appointment::forDate($date)
            ->with([
                'patient:id,first_name,last_name,phone',
                'catalogActs:id,name,code',
                'creator:id,name,role',
            ])
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'date'         => $date,
            'appointments' => $appointments->map(fn($a) => $this->formatAppointment($a)),

            // Stats rapides du jour
            'stats' => [
                'total'    => $appointments->count(),
                'termine'  => $appointments->where('status', 'TERMINE')->count(),
                'en_cours' => $appointments->where('status', 'EN_COURS')->count(),
                'annule'   => $appointments->whereIn('status', ['ANNULE', 'NO_SHOW'])->count(),
            ]
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/appointments
    // Créer un nouveau RDV avec vérification de disponibilité
    // ═══════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'scheduled_date' => 'required|date',
            'start_time'     => 'required|date_format:H:i|after_or_equal:09:00|before:18:00',
            'end_time'       => ['required', 'date_format:H:i', 'after:start_time', 'before_or_equal:18:00',
                function ($attr, $value, $fail) use ($request) {
                    if ($request->start_time && str_contains($value, ':') && str_contains($request->start_time, ':')) {
                        [$sh, $sm] = explode(':', $request->start_time);
                        [$eh, $em] = explode(':', $value);
                        if (((int)$eh * 60 + (int)$em) - ((int)$sh * 60 + (int)$sm) < 15) {
                            $fail('La durée minimale est de 15 minutes.');
                        }
                    }
                },
            ],
            'notes'          => 'nullable|string',
            'act_ids'        => 'nullable|array',
            'act_ids.*'      => 'exists:catalog_acts,id',
            'status'         => 'nullable|in:PLANIFIE,CONFIRME',
        ]);

        // ─── Vérification chevauchement ───────────────────────────
        if (Appointment::hasConflict(
            $request->scheduled_date,
            $request->start_time,
            $request->end_time
        )) {
            return response()->json([
                'message' => 'Ce créneau est déjà occupé par un autre rendez-vous.'
            ], 409);
        }

        // ─── Création du RDV ──────────────────────────────────────
        $appointment = Appointment::create([
            'patient_id'     => $request->patient_id,
            'created_by'     => auth()->id(),
            'scheduled_date' => $request->scheduled_date,
            'start_time'     => $request->start_time,
            'end_time'       => $request->end_time,
            'status' => $request->status ?? 'PLANIFIE',
            'notes'          => $request->notes,
        ]);

        // ─── Attache les actes prévus ─────────────────────────────
        if ($request->filled('act_ids')) {
            $appointment->catalogActs()->attach($request->act_ids);
        }

        // Recharge avec les relations pour la réponse
        $appointment->load([
            'patient:id,first_name,last_name,phone',
            'catalogActs:id,name,code',
            'creator:id,name,role',
        ]);

        cache()->forget('dashboard_stats_v4_' . now()->format('Y-m'));

        return response()->json([
            'message'     => 'Rendez-vous créé avec succès.',
            'appointment' => $this->formatAppointment($appointment),
        ], 201);
    }

    // ═══════════════════════════════════════════════════════════════
    // PUT /api/appointments/{id}
    // Modifier un RDV existant
    // ═══════════════════════════════════════════════════════════════
    public function update(Request $request, Appointment $appointment)
    {
        $request->validate([
            'patient_id'     => 'sometimes|exists:patients,id',
            'scheduled_date' => 'required|date',
            'start_time'     => 'required|date_format:H:i|after_or_equal:09:00|before:18:00',
            'end_time'       => ['required', 'date_format:H:i', 'after:start_time', 'before_or_equal:18:00',
                function ($attr, $value, $fail) use ($request) {
                    if ($request->start_time && str_contains($value, ':') && str_contains($request->start_time, ':')) {
                        [$sh, $sm] = explode(':', $request->start_time);
                        [$eh, $em] = explode(':', $value);
                        if (((int)$eh * 60 + (int)$em) - ((int)$sh * 60 + (int)$sm) < 15) {
                            $fail('La durée minimale est de 15 minutes.');
                        }
                    }
                },
            ],
            'notes'          => 'nullable|string',
            'act_ids'        => 'nullable|array',
            'act_ids.*'      => 'exists:catalog_acts,id',
        ]);

        // ─── Vérification chevauchement (exclut le RDV actuel) ────
        if (Appointment::hasConflict(
            $request->scheduled_date,
            $request->start_time,
            $request->end_time,
            $appointment->id // exclut ce RDV de la vérification
        )) {
            return response()->json([
                'message' => 'Ce créneau est déjà occupé par un autre rendez-vous.'
            ], 409);
        }

        $appointment->update(array_filter([
            'patient_id'     => $request->patient_id,
            'scheduled_date' => $request->scheduled_date,
            'start_time'     => $request->start_time,
            'end_time'       => $request->end_time,
            'notes'          => $request->notes,
        ], fn($v) => $v !== null));

        // ─── Met à jour les actes (sync remplace tout) ────────────
        if ($request->has('act_ids')) {
            $appointment->catalogActs()->sync($request->act_ids ?? []);
        }

        $appointment->load([
            'patient:id,first_name,last_name,phone',
            'catalogActs:id,name,code',
            'creator:id,name,role',
        ]);

        return response()->json([
            'message'     => 'Rendez-vous modifié avec succès.',
            'appointment' => $this->formatAppointment($appointment),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // PATCH /api/appointments/{id}/status
    // Changer uniquement le statut d'un RDV
    // ═══════════════════════════════════════════════════════════════
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => 'required|in:PLANIFIE,CONFIRME,EN_COURS,TERMINE,ANNULE,NO_SHOW'
        ]);

        $appointment->update(['status' => $request->status]);

        cache()->forget('dashboard_stats_v4_' . now()->format('Y-m'));

        return response()->json([
            'message'     => 'Statut mis à jour.',
            'appointment' => $this->formatAppointment($appointment),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // DELETE /api/appointments/{id}a
    // ═══════════════════════════════════════════════════════════════
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        cache()->forget('dashboard_stats_v4_' . now()->format('Y-m'));

        return response()->json(['message' => 'Rendez-vous supprimé.'], 200);
    }

    // ═══════════════════════════════════════════════════════════════
    // PATCH /api/appointments/{id}/whatsapp-notified
    // ═══════════════════════════════════════════════════════════════
    public function markWhatsappNotified(Appointment $appointment)
    {
        $appointment->update(['whatsapp_notified_at' => now()]);

        return response()->json([
            'whatsapp_notified_at' => $appointment->whatsapp_notified_at->toISOString(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /api/catalog-acts
    // Liste des actes pour le select dans la modal de création
    // ═══════════════════════════════════════════════════════════════
    public function catalogActs()
    {
        $acts = CatalogAct::active()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'base_price', 'duration_minutes']);

        return response()->json($acts);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER — Format uniforme retourné au frontend
    // ═══════════════════════════════════════════════════════════════
    private function formatAppointment(Appointment $a): array
    {
        return [
            'id'             => $a->id,
            'scheduled_date' => $a->scheduled_date->format('Y-m-d'),
            // Supprime les secondes → "11:20:00" devient "11:20"
            'start_time' => substr($a->start_time, 0, 5),
            'end_time'   => substr($a->end_time, 0, 5),
            'status'         => $a->status,

            // Couleur calculée depuis le modèle
            'color'          => $a->color,

            'notes'          => $a->notes,

            // Patient (champs essentiels uniquement)
            'patient' => [
                'id'        => $a->patient->id,
                'full_name' => $a->patient->first_name . ' ' . $a->patient->last_name,
                'phone'     => $a->patient->phone,
            ],

            // Actes prévus
            'acts' => $a->catalogActs->map(fn($act) => [
                'id'   => $act->id,
                'name' => $act->name,
                'code' => $act->code,
            ]),

            // Qui a créé le RDV
            'created_by' => [
                'id'   => $a->creator->id,
                'name' => $a->creator->name,
                'role' => $a->creator->role,
            ],

            'whatsapp_notified_at' => $a->whatsapp_notified_at?->toISOString(),
            'created_at'           => $a->created_at->format('d/m/Y H:i'),
        ];
    }
}
