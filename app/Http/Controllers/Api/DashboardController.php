<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\ConsultationAct;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today      = now()->toDateString();
        $weekStart  = now()->startOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        // ── Aujourd'hui ───────────────────────────────────────────
        $todayAppts = Appointment::where('scheduled_date', $today)->get();

        $apptPatientIds    = $todayAppts->pluck('patient_id');
        $consultPatientIds = Consultation::whereDate('created_at', $today)->pluck('patient_id');
        $todayPatients     = $apptPatientIds->merge($consultPatientIds)->unique()->count();
        $newPatients       = Patient::whereDate('created_at', $today)->count();

        // ── CA consultations du jour (total_price des consultations terminées aujourd'hui) ─
        $caConsultToday = (float) Consultation::whereDate('created_at', $today)
            ->sum('total_price');

        // ── Revenus ───────────────────────────────────────────────
        $revenueDay   = (float) PaymentTransaction::whereDate('date', $today)->sum('amount');
        $revenueWeek  = (float) PaymentTransaction::whereBetween('date', [$weekStart, $today])->sum('amount');
        $revenueMonth = (float) PaymentTransaction::whereBetween('date', [$monthStart, $today])->sum('amount');

        // ── Impayés : calcul par patient (évite l'erreur des avances) ─
        $unpaid = Patient::with([
            'paymentTransactions',
        ])->get()->sum(function ($patient) {
            $dette = $patient->consultations->sum('total_price');
            $paye  = $patient->paymentTransactions->sum('amount');
            return max(0, $dette - $paye); // ne compte pas les avances
        });
        $unpaid = (float) $unpaid;

        // ── Top 5 actes ───────────────────────────────────────────
        $topActs = ConsultationAct::with('catalogAct:id,name,code')
            ->selectRaw('catalog_act_id, COUNT(*) as count, SUM(price) as revenue')
            ->groupBy('catalog_act_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'name'    => $a->catalogAct->name ?? 'Inconnu',
                'code'    => $a->catalogAct->code ?? '',
                'count'   => (int) $a->count,
                'revenue' => (float) $a->revenue,
            ]);

        // ── Taux d'absentéisme (mois en cours) ───────────────────
        $totalMonth  = Appointment::where('scheduled_date', '>=', $monthStart)->count();
        $absences    = Appointment::where('scheduled_date', '>=', $monthStart)
            ->whereIn('status', ['NO_SHOW', 'ANNULE'])->count();
        $absenceRate = $totalMonth > 0 ? round($absences / $totalMonth * 100, 1) : 0;

        // ── Stats RDV aujourd'hui ─────────────────────────────────
        $rdvStats = [
            'total'    => $todayAppts->count(),
            'termine'  => $todayAppts->where('status', 'TERMINE')->count(),
            'en_cours' => $todayAppts->where('status', 'EN_COURS')->count(),
            'planifie' => $todayAppts->whereIn('status', ['PLANIFIE', 'CONFIRME'])->count(),
            'absent'   => $todayAppts->whereIn('status', ['NO_SHOW', 'ANNULE'])->count(),
        ];

        // ── Démographie ───────────────────────────────────────────
        $patients = Patient::active()->get();
        $total    = $patients->count();

        $gender = [
            'M' => $patients->where('gender', 'M')->count(),
            'F' => $patients->where('gender', 'F')->count(),
        ];

        $ageGroups = [
            '0-18'  => $patients->filter(fn($p) => $p->age !== null && $p->age <= 18)->count(),
            '18-30' => $patients->filter(fn($p) => $p->age !== null && $p->age > 18 && $p->age <= 30)->count(),
            '30-50' => $patients->filter(fn($p) => $p->age !== null && $p->age > 30 && $p->age <= 50)->count(),
            '50+'   => $patients->filter(fn($p) => $p->age !== null && $p->age > 50)->count(),
        ];

        // ── CA des 6 derniers mois (graphique) ────────────────────
        $monthlyChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $start = $date->copy()->startOfMonth()->toDateString();
            $end   = $date->copy()->endOfMonth()->toDateString();
            $monthlyChart[] = [
                'month'   => $date->locale('fr')->isoFormat('MMM'),
                'revenue' => (float) PaymentTransaction::whereBetween('date', [$start, $end])->sum('amount'),
            ];
        }

        return response()->json([
            'today' => [
                'patients'          => $todayPatients,
                'new_patients'      => $newPatients,
                'rdv'               => $rdvStats,
                'ca_consult_today'  => $caConsultToday,
            ],
            'revenue' => [
                'day'    => $revenueDay,
                'week'   => $revenueWeek,
                'month'  => $revenueMonth,
                'unpaid' => $unpaid,
            ],
            'top_acts'     => $topActs,
            'absence_rate' => [
                'rate'     => $absenceRate,
                'absences' => $absences,
                'total'    => $totalMonth,
            ],
            'demographics' => [
                'total'      => $total,
                'gender'     => $gender,
                'age_groups' => $ageGroups,
            ],
            'monthly_chart' => $monthlyChart,
        ]);
    }
}
