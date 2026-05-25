<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    // GET /api/payments
    // Liste des patients avec leur solde — calcul en SQL via withSum
    // ═══════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $perPage = 30;
        $page    = max(1, (int) $request->get('page', 1));

        $query = Patient::select('id', 'first_name', 'last_name', 'phone', 'couverture')
            ->where('is_archived', false)
            ->whereHas('consultations', fn($q) => $q->whereNotIn('status', ['BROUILLON']))
            ->withSum(
                ['consultations as total_consultations' => fn($q) => $q->whereNotIn('status', ['BROUILLON'])],
                'total_price'
            )
            ->withSum('paymentTransactions as total_paid', 'amount');

        // Recherche en SQL
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name',  'like', "%{$s}%")
                ->orWhere('phone',      'like', "%{$s}%")
            );
        }

        // Calcul balance sur toute la sélection (pour les stats globales)
        $all = $query->get()->map(fn($p) => $this->formatBalance($p));

        // Stats toujours calculées sur TOUS les résultats (avant filtre statut)
        $stats = [
            'total_du'       => $all->sum('total_consultations'),
            'total_encaisse' => $all->sum('total_paid'),
            'total_restant'  => $all->sum(fn($p) => max(0, -$p['balance'])),
            'count_partiel'  => $all->where('balance_status', 'PARTIEL')->count(),
            'count_paye'     => $all->where('balance_status', 'PAYÉ')->count(),
            'count_avance'   => $all->where('balance_status', 'AVANCE')->count(),
        ];

        // Filtre statut en PHP (valeur calculée, non exprimable simplement en SQL)
        $filtered = $request->filled('status')
            ? $all->filter(fn($p) => $p['balance_status'] === $request->status)->values()
            : $all->values();

        // Tri : plus endetté en premier
        $sorted = $filtered->sortBy('balance')->values();

        // Pagination sur la collection
        $paginated = new LengthAwarePaginator(
            $sorted->slice(($page - 1) * $perPage, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
        );

        return response()->json([
            'data'  => $paginated->items(),
            'stats' => $stats,
            'meta'  => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /api/payments/{patientId}
    // Fiche complète : consultations + versements
    // ═══════════════════════════════════════════════════════════════
    public function show(int $patientId)
    {
        $patient = Patient::with([
            'consultations' => fn($q) =>
                $q->whereNotIn('status', ['BROUILLON'])
                  ->with('acts.catalogAct:id,name,code')
                  ->orderBy('created_at', 'desc'),
            'paymentTransactions' => fn($q) =>
                $q->orderBy('date', 'desc')->with('creator:id,name'),
        ])->findOrFail($patientId);

        $formatted = $this->formatBalance($patient);

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

        $formatted['transactions'] = $patient->paymentTransactions->map(
            fn($t) => $this->formatTransaction($t)
        );

        return response()->json(['patient' => $formatted]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /api/payments/{patientId}/transactions
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

        $patient->load([
            'consultations'       => fn($q) => $q->whereNotIn('status', ['BROUILLON'])->select('id', 'patient_id', 'total_price', 'status', 'created_at'),
            'paymentTransactions' => fn($q) => $q->orderBy('date', 'desc'),
        ]);

        return response()->json([
            'message'     => 'Versement enregistré.',
            'transaction' => $this->formatTransaction($transaction),
            'patient'     => $this->formatBalance($patient),
        ], 201);
    }

    // ═══════════════════════════════════════════════════════════════
    // DELETE /api/payments/transactions/{id}
    // ═══════════════════════════════════════════════════════════════
    public function deleteTransaction(int $transactionId)
    {
        $transaction = PaymentTransaction::findOrFail($transactionId);
        $patientId   = $transaction->patient_id;

        $transaction->delete();

        $patient = Patient::with([
            'consultations'       => fn($q) => $q->whereNotIn('status', ['BROUILLON'])->select('id', 'patient_id', 'total_price', 'status', 'created_at'),
            'paymentTransactions' => fn($q) => $q->orderBy('date', 'desc'),
        ])->findOrFail($patientId);

        return response()->json([
            'message' => 'Versement supprimé.',
            'patient' => $this->formatBalance($patient),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    // Accepte un patient avec relations chargées OU avec withSum attributes
    private function formatBalance(Patient $p): array
    {
        // withSum retourne des attributs scalaires ; sinon on utilise la collection
        $totalConsultations = isset($p->total_consultations)
            ? (float) $p->total_consultations
            : (float) ($p->relationLoaded('consultations') ? $p->consultations->sum('total_price') : 0);

        $totalPaid = isset($p->total_paid)
            ? (float) $p->total_paid
            : (float) ($p->relationLoaded('paymentTransactions') ? $p->paymentTransactions->sum('amount') : 0);

        $balance = $totalPaid - $totalConsultations;

        $status = match (true) {
            $balance >  0.01 => 'AVANCE',
            $balance < -0.01 => 'PARTIEL',
            default          => 'PAYÉ',
        };

        return [
            'id'                  => $p->id,
            'full_name'           => $p->first_name . ' ' . $p->last_name,
            'phone'               => $p->phone,
            'couverture'          => $p->couverture,
            'total_consultations' => $totalConsultations,
            'total_paid'          => $totalPaid,
            'balance'             => round($balance, 2),
            'balance_status'      => $status,
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
