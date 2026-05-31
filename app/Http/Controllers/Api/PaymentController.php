<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    // GET /api/payments
    // Liste des patients avec leur solde — calcul en SQL via withSum
    // ═══════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:PARTIEL,PAYÉ,AVANCE',
            'page'   => 'nullable|integer|min:1',
        ]);

        $perPage = 30;

        $consultSum = "COALESCE((SELECT SUM(total_price) FROM consultations WHERE patient_id = patients.id AND status NOT IN ('BROUILLON')), 0)";
        $paidSum    = "COALESCE((SELECT SUM(amount) FROM payment_transactions WHERE patient_id = patients.id), 0)";
        $balanceExp = "({$paidSum}) - ({$consultSum})";

        // Stats globales en une seule requête SQL (avant filtre statut)
        $statsBase = Patient::where('is_archived', false)
            ->whereExists(fn($q) => $q->select(DB::raw(1))
                ->from('consultations')
                ->whereColumn('patient_id', 'patients.id')
                ->whereNotIn('status', ['BROUILLON']));

        if ($request->filled('search')) {
            $s = $request->search;
            $statsBase->where(fn($q) => $q
                ->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name',  'like', "%{$s}%")
                ->orWhere('phone',      'like', "%{$s}%")
            );
        }

        $statsRow = $statsBase->selectRaw("
            COALESCE(SUM({$consultSum}), 0)                                    AS total_du,
            COALESCE(SUM({$paidSum}), 0)                                       AS total_encaisse,
            COALESCE(SUM(GREATEST(-({$balanceExp}), 0)), 0)                    AS total_restant,
            SUM(({$balanceExp}) < -0.01)                                       AS count_partiel,
            SUM(ABS({$balanceExp}) <= 0.01)                                    AS count_paye,
            SUM(({$balanceExp}) > 0.01)                                        AS count_avance
        ")->first();

        $stats = [
            'total_du'       => (float) ($statsRow->total_du ?? 0),
            'total_encaisse' => (float) ($statsRow->total_encaisse ?? 0),
            'total_restant'  => (float) ($statsRow->total_restant ?? 0),
            'count_partiel'  => (int)   ($statsRow->count_partiel ?? 0),
            'count_paye'     => (int)   ($statsRow->count_paye ?? 0),
            'count_avance'   => (int)   ($statsRow->count_avance ?? 0),
        ];

        // Requête principale paginée en SQL
        $query = Patient::select(
            'id', 'first_name', 'last_name', 'phone', 'couverture',
            DB::raw("{$consultSum} AS total_consultations"),
            DB::raw("{$paidSum}    AS total_paid"),
            DB::raw("{$balanceExp} AS balance_raw"),
        )
        ->where('is_archived', false)
        ->whereExists(fn($q) => $q->select(DB::raw(1))
            ->from('consultations')
            ->whereColumn('patient_id', 'patients.id')
            ->whereNotIn('status', ['BROUILLON']));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name',  'like', "%{$s}%")
                ->orWhere('phone',      'like', "%{$s}%")
            );
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'PARTIEL' => $query->havingRaw("{$balanceExp} < -0.01"),
                'PAYÉ'    => $query->havingRaw("ABS({$balanceExp}) <= 0.01"),
                'AVANCE'  => $query->havingRaw("{$balanceExp} > 0.01"),
            };
        }

        $query->orderByRaw("{$balanceExp} ASC");

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data'  => $paginated->map(fn($p) => $this->formatBalance($p)),
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
            'amount' => 'required|numeric|min:0.01|max:999999',
            'date'   => 'required|date',
            'notes'  => 'nullable|string|max:500',
        ]);

        $transaction = PaymentTransaction::create([
            'patient_id' => $patientId,
            'created_by' => auth()->id(),
            'amount'     => $request->amount,
            'date'       => $request->date,
            'notes'      => $request->notes,
        ]);

        cache()->forget('dashboard_stats_v2');

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

        cache()->forget('dashboard_stats_v2');

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
        $totalConsultations = isset($p->total_consultations)
            ? (float) $p->total_consultations
            : (float) ($p->relationLoaded('consultations') ? $p->consultations->sum('total_price') : 0);

        $totalPaid = isset($p->total_paid)
            ? (float) $p->total_paid
            : (float) ($p->relationLoaded('paymentTransactions') ? $p->paymentTransactions->sum('amount') : 0);

        $balance = isset($p->balance_raw)
            ? (float) $p->balance_raw
            : $totalPaid - $totalConsultations;

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
