<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\ConsultationAct;
use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now        = Carbon::now();
        $today      = $now->toDateString();
        $weekStart  = $now->copy()->startOfWeek()->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $prevStart  = $now->copy()->subMonth()->startOfMonth()->toDateString();
        $prevEnd    = $now->copy()->subMonth()->endOfMonth()->toDateString();

        // ── RDV aujourd'hui ───────────────────────────────────────
        $todayAppts = Appointment::with('patient:id,first_name,last_name')
            ->where('scheduled_date', $today)
            ->orderBy('start_time')
            ->get();

        $rdvStats = [
            'total'    => $todayAppts->count(),
            'termine'  => $todayAppts->where('status', 'TERMINE')->count(),
            'planifie' => $todayAppts->whereIn('status', ['PLANIFIE', 'CONFIRME'])->count(),
            'absent'   => $todayAppts->whereIn('status', ['NO_SHOW', 'ANNULE'])->count(),
        ];

        // ── Prochain RDV (le prochain non-terminé après maintenant) ──
        $nextAppt = $todayAppts
            ->whereIn('status', ['PLANIFIE', 'CONFIRME'])
            ->where('start_time', '>=', $now->format('H:i:s'))
            ->first();

        $prochainRdv = $nextAppt ? [
            'start_time'  => substr($nextAppt->start_time, 0, 5),
            'patient'     => $nextAppt->patient->first_name . ' ' . $nextAppt->patient->last_name,
        ] : null;

        // ── Consultations EN_COURS ────────────────────────────────
        $consultEnCours = Consultation::where('status', 'EN_COURS')->count();

        // ── Patients ──────────────────────────────────────────────
        $newPatientsToday = Patient::whereDate('created_at', $today)->count();
        $newPatientsMonth = Patient::where('created_at', '>=', $monthStart)->count();
        $totalPatients    = Patient::active()->count();

        // ── Revenus encaissés ─────────────────────────────────────
        $revenueDay       = (float) PaymentTransaction::whereDate('date', $today)->sum('amount');
        $revenueWeek      = (float) PaymentTransaction::whereBetween('date', [$weekStart, $today])->sum('amount');
        $revenueMonth     = (float) PaymentTransaction::whereBetween('date', [$monthStart, $today])->sum('amount');
        $revenuePrevMonth = (float) PaymentTransaction::whereBetween('date', [$prevStart, $prevEnd])->sum('amount');

        $revenueVariation = $revenuePrevMonth > 0
            ? round(($revenueMonth - $revenuePrevMonth) / $revenuePrevMonth * 100, 1)
            : null;

        // ── CA consultations terminées ce mois ────────────────────
        $caConsultMonth = (float) Consultation::where('status', 'TERMINE')
            ->where('created_at', '>=', $monthStart)
            ->sum('total_price');

        // ── Impayés ───────────────────────────────────────────────
        $totalDette = (float) Consultation::where('status', 'TERMINE')->sum('total_price');
        $totalPaye  = (float) PaymentTransaction::sum('amount');
        $unpaid     = max(0, $totalDette - $totalPaye);

        // ── Taux absentéisme ce mois ──────────────────────────────
        $totalMonth  = Appointment::where('scheduled_date', '>=', $monthStart)->count();
        $absences    = Appointment::where('scheduled_date', '>=', $monthStart)
            ->whereIn('status', ['NO_SHOW', 'ANNULE'])->count();
        $absenceRate = $totalMonth > 0 ? round($absences / $totalMonth * 100, 1) : 0;

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

        // ── CA des 6 derniers mois ────────────────────────────────
        $monthlyChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $d     = $now->copy()->subMonths($i);
            $start = $d->copy()->startOfMonth()->toDateString();
            $end   = $d->copy()->endOfMonth()->toDateString();
            $monthlyChart[] = [
                'month'   => $d->locale('fr')->isoFormat('MMM YY'),
                'revenue' => (float) PaymentTransaction::whereBetween('date', [$start, $end])->sum('amount'),
                'ca'      => (float) Consultation::where('status', 'TERMINE')
                                ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                                ->sum('total_price'),
            ];
        }

        // ── Démographie ───────────────────────────────────────────
        $patients  = Patient::active()->get(['id', 'gender', 'birth_date']);
        $total     = $patients->count();
        $gender    = ['M' => $patients->where('gender', 'M')->count(), 'F' => $patients->where('gender', 'F')->count()];
        $ageGroups = [
            '0-18'  => $patients->filter(fn($p) => $p->age !== null && $p->age <= 18)->count(),
            '18-30' => $patients->filter(fn($p) => $p->age !== null && $p->age > 18 && $p->age <= 30)->count(),
            '30-50' => $patients->filter(fn($p) => $p->age !== null && $p->age > 30 && $p->age <= 50)->count(),
            '50+'   => $patients->filter(fn($p) => $p->age !== null && $p->age > 50)->count(),
        ];

        return response()->json([
            'today' => [
                'rdv'               => $rdvStats,
                'prochain_rdv'      => $prochainRdv,
                'new_patients'      => $newPatientsToday,
                'consult_en_cours'  => $consultEnCours,
            ],
            'patients' => [
                'total'             => $totalPatients,
                'new_this_month'    => $newPatientsMonth,
            ],
            'revenue' => [
                'day'              => $revenueDay,
                'week'             => $revenueWeek,
                'month'            => $revenueMonth,
                'prev_month'       => $revenuePrevMonth,
                'variation_pct'    => $revenueVariation,
                'ca_consult_month' => $caConsultMonth,
                'unpaid'           => $unpaid,
            ],
            'absence_rate' => [
                'rate'     => $absenceRate,
                'absences' => $absences,
                'total'    => $totalMonth,
            ],
            'top_acts'      => $topActs,
            'monthly_chart' => $monthlyChart,
            'demographics'  => ['total' => $total, 'gender' => $gender, 'age_groups' => $ageGroups],
        ]);
    }
}
