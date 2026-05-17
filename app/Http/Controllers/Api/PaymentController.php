<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PaymentTransaction;
use App\Models\Consultation;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    // GET /api/payments
    // Liste des patients avec leur solde
    // Filtre : status (AVANCE / PAYÉ / PARTIEL), search
    // ═══════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $patients = Patient::whereHas('consultations')
            ->with([
                'consultations'       => fn($q) => $q->select('id', 'patient_id', 'total_price', 'status', 'created_at'),
                'paymentTransactions' => fn($q) => $q->orderBy('date', 'desc')->select('id', 'patient_id', 'amount', 'date'),
            ])
            ->get()
            ->map(fn($p) => $this->formatPatientBalance($p));

        // ─── Filtres ──────────────────────────────────────────────
        if ($request->filled('status')) {
            $patients = $patients->filter(
                fn($p) => $p['balance_status'] === $request->status
            );
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $patients = $patients->filter(
                fn($p) => str_contains(strtolower($p['full_name']), $search)
            );
        }

        // ─── Stats globales ───────────────────────────────────────
        $all = $patients->values();

        $stats = [
            'total_du'      => $all->sum('total_consultations'),
            'total_encaisse' => $all->sum('total_paid'),
            'total_restant' => $all->sum(fn($p) => max(0, -$p['balance'])),
            'count_partiel' => $all->where('balance_status', 'PARTIEL')->count(),
            'count_paye'    => $all->where('balance_status', 'PAYÉ')->count(),
            'count_avance'  => $all->where('balance_status', 'AVANCE')->count(),
        ];

        return response()->json([
            'data'  => $all->values(),
            'stats' => $stats,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /api/payments/{patientId}
    // Fiche complète d'un patient : consultations + versements
    // ═══════════════════════════════════════════════════════════════
    public function show(int $patientId)
    {
        // findOrFail fonctionne même sans consultation
        $patient = Patient::with([
            'consultations' => fn($q) =>
            $q->whereNotIn('status', ['BROUILLON'])
                ->with('acts.catalogAct:id,name,code')
                ->orderBy('created_at', 'desc'),
            'paymentTransactions' => fn($q) =>
            $q->orderBy('date', 'desc')
                ->with('creator:id,name'),
        ])->findOrFail($patientId);
        
        $formatted = $this->formatPatientBalance($patient);

        // Détail consultations
        $formatted['consultations'] = $patient->consultations->map(fn($c) => [
            'id'          => $c->id,
            'status'      => $c->status,
            'total_price' => (float) $c->total_price,
            'created_at'  => $c->created_at->format('d/m/Y'),
            'acts_count'  => $c->acts->count(),
            'acts'        => $c->acts->map(fn($a) => [
                'name'  => $a->catalogAct->name ?? '—',
                'teeth' => $a->teeth ?? [],
                'price' => (float) $a->price,
            ]),
        ]);

        // Détail versements
        $formatted['transactions'] = $patient->paymentTransactions->map(
            fn($t) => $this->formatTransaction($t)
        );

        return response()->json(['patient' => $formatted]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/payments/{patientId}/transactions
    // Ajouter un versement pour un patient
    // ═══════════════════════════════════════════════════════════════
    public function addTransaction(Request $request, int $patientId)
    {
        $patient = Patient::findOrFail($patientId);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date'   => 'required|date',
            'notes'  => 'nullable|string',
        ]);

        $transaction = PaymentTransaction::create([
            'patient_id' => $patientId,
            'created_by' => auth()->id(),
            'amount'     => $request->amount,
            'date'       => $request->date,
            'notes'      => $request->notes,
        ]);

        // Recharge le patient avec ses données à jour
        $patient->load([
            'consultations' => fn($q) =>
            $q->whereNotIn('status', ['BROUILLON'])
                ->select('id', 'patient_id', 'total_price', 'status', 'created_at'),
            'paymentTransactions' => fn($q) =>
            $q->orderBy('date', 'desc'),
        ]);

        return response()->json([
            'message'     => 'Versement enregistré.',
            'transaction' => $this->formatTransaction($transaction),
            'patient'     => $this->formatPatientBalance($patient),
        ], 201);
    }

    // ═══════════════════════════════════════════════════════════════
    // DELETE /api/payments/transactions/{id}
    // Supprimer un versement (dentiste uniquement)
    // ═══════════════════════════════════════════════════════════════
    public function deleteTransaction(int $transactionId)
    {
        $transaction = PaymentTransaction::findOrFail($transactionId);
        $patientId   = $transaction->patient_id;

        $transaction->delete();

        $patient = Patient::with([
            'consultations' => fn($q) =>
            $q->whereNotIn('status', ['BROUILLON'])
                ->select('id', 'patient_id', 'total_price', 'status', 'created_at'),
            'paymentTransactions' => fn($q) =>
            $q->orderBy('date', 'desc'),
        ])->findOrFail($patientId);

        return response()->json([
            'message' => 'Versement supprimé.',
            'patient' => $this->formatPatientBalance($patient),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    // Calcule le solde d'un patient et retourne un tableau formaté
    private function formatPatientBalance(Patient $patient): array
    {
        $totalConsultations = $patient->consultations->sum('total_price');
        $totalPaid          = $patient->paymentTransactions->sum('amount');
        $balance            = $totalPaid - $totalConsultations;

        // Statut du solde
        $status = match (true) {
            $balance >  0.01 => 'AVANCE',   // a trop payé
            $balance < -0.01 => 'PARTIEL',  // doit encore
            default          => 'PAYÉ',     // tout réglé
        };

        return [
            'id'                 => $patient->id,
            'full_name'          => $patient->first_name . ' ' . $patient->last_name,
            'phone'              => $patient->phone,
            'couverture'         => $patient->couverture,
            'total_consultations' => (float) $totalConsultations,
            'total_paid'         => (float) $totalPaid,
            'balance'            => round((float) $balance, 2),
            'balance_status'     => $status,
            'consultations_count' => $patient->consultations->count(),
            'transactions_count' => $patient->paymentTransactions->count(),
        ];
    }

    private function formatTransaction(PaymentTransaction $t): array
    {
        return [
            'id'     => $t->id,
            'amount' => (float) $t->amount,
            'date'   => $t->date instanceof \Carbon\Carbon
                ? $t->date->format('d/m/Y')
                : $t->date,
            'notes'  => $t->notes,
        ];
    }
}
