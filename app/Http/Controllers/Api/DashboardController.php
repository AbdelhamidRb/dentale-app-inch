<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        // ── 1. RDV du jour (1 requête Eloquent) ──────────────────────
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

        $nextAppt = $todayAppts
            ->whereIn('status', ['PLANIFIE', 'CONFIRME'])
            ->where('start_time', '>=', $now->format('H:i:s'))
            ->first();

        $prochainRdv = $nextAppt ? [
            'start_time' => substr($nextAppt->start_time, 0, 5),
            'patient'    => $nextAppt->patient->first_name . ' ' . $nextAppt->patient->last_name,
        ] : null;

        // ── 2. Patients : total + nouveaux (1 requête) ───────────────
        $patientStats = DB::selectOne("
            SELECT
                COUNT(*)                          AS total,
                SUM(DATE(created_at) = ?)         AS new_today,
                SUM(created_at >= ?)              AS new_month
            FROM patients
            WHERE is_archived = 0
        ", [$today, $monthStart]);

        // ── 3. Consultations EN_COURS (1 requête simple) ─────────────
        $consultEnCours = (int) DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM consultations WHERE status = 'EN_COURS'"
        )->cnt;

        // ── 4. Absentéisme du mois (1 requête appointments) ──────────
        $absStats = DB::selectOne("
            SELECT
                SUM(status IN ('NO_SHOW','ANNULE')) AS absences,
                COUNT(*)                            AS total
            FROM appointments
            WHERE scheduled_date >= ?
        ", [$monthStart]);

        $absenceRate = ($absStats->total > 0)
            ? round($absStats->absences / $absStats->total * 100, 1)
            : 0;

        // ── 5. Revenus : jour/semaine/mois/mois précédent (1 requête) ─
        $revenueStats = DB::selectOne("
            SELECT
                SUM(CASE WHEN DATE(date) = ?           THEN amount ELSE 0 END) AS day,
                SUM(CASE WHEN date BETWEEN ? AND ?     THEN amount ELSE 0 END) AS week,
                SUM(CASE WHEN date BETWEEN ? AND ?     THEN amount ELSE 0 END) AS month,
                SUM(CASE WHEN date BETWEEN ? AND ?     THEN amount ELSE 0 END) AS prev_month,
                SUM(amount)                                                     AS total
            FROM payment_transactions
        ", [$today, $weekStart, $today, $monthStart, $today, $prevStart, $prevEnd]);

        // CA consultations terminées ce mois
        $caConsultMonth = (float) DB::selectOne("
            SELECT COALESCE(SUM(total_price), 0) AS ca
            FROM consultations
            WHERE status = 'TERMINE' AND created_at >= ?
        ", [$monthStart . ' 00:00:00'])->ca;

        // Impayés globaux
        $totalDette = (float) DB::selectOne(
            "SELECT COALESCE(SUM(total_price), 0) AS s FROM consultations WHERE status = 'TERMINE'"
        )->s;
        $unpaid = max(0, $totalDette - (float) ($revenueStats->total ?? 0));

        $revenueVariation = (($revenueStats->prev_month ?? 0) > 0)
            ? round(($revenueStats->month - $revenueStats->prev_month) / $revenueStats->prev_month * 100, 1)
            : null;

        // ── 6. Top 5 actes (1 requête GROUP BY) ──────────────────────
        $topActs = DB::select("
            SELECT ca.name, ca.code,
                   COUNT(*)     AS cnt,
                   SUM(a.price) AS revenue
            FROM consultation_acts a
            JOIN catalog_acts ca ON ca.id = a.catalog_act_id
            GROUP BY ca.id, ca.name, ca.code
            ORDER BY cnt DESC
            LIMIT 5
        ");

        // ── 7. Graphique 6 mois (2 requêtes GROUP BY) ────────────────
        $sixMonthsAgo = $now->copy()->subMonths(5)->startOfMonth()->toDateString();

        $revenueByMonth = DB::select("
            SELECT DATE_FORMAT(date, '%Y-%m') AS m, SUM(amount) AS revenue
            FROM payment_transactions
            WHERE date >= ?
            GROUP BY m
        ", [$sixMonthsAgo]);
        $revenueMap = collect($revenueByMonth)->pluck('revenue', 'm');

        $caByMonth = DB::select("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, SUM(total_price) AS ca
            FROM consultations
            WHERE status = 'TERMINE' AND created_at >= ?
            GROUP BY m
        ", [$sixMonthsAgo . ' 00:00:00']);
        $caMap = collect($caByMonth)->pluck('ca', 'm');

        $monthlyChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $d   = $now->copy()->subMonths($i);
            $key = $d->format('Y-m');
            $monthlyChart[] = [
                'month'   => $d->locale('fr')->isoFormat('MMM YY'),
                'revenue' => (float) ($revenueMap[$key] ?? 0),
                'ca'      => (float) ($caMap[$key] ?? 0),
            ];
        }

        // ── 8. Démographie (1 requête SQL agrégée) ───────────────────
        $demo = DB::selectOne("
            SELECT
                COUNT(*)                                                            AS total,
                SUM(gender = 'M')                                                   AS male,
                SUM(gender = 'F')                                                   AS female,
                SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) <= 18)               AS age_0_18,
                SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 19 AND 30)   AS age_18_30,
                SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 31 AND 50)   AS age_30_50,
                SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) > 50)                AS age_50_plus
            FROM patients
            WHERE is_archived = 0
        ");

        return response()->json([
            'today' => [
                'rdv'              => $rdvStats,
                'prochain_rdv'     => $prochainRdv,
                'new_patients'     => (int) ($patientStats->new_today ?? 0),
                'consult_en_cours' => $consultEnCours,
            ],
            'patients' => [
                'total'          => (int) ($patientStats->total ?? 0),
                'new_this_month' => (int) ($patientStats->new_month ?? 0),
            ],
            'revenue' => [
                'day'              => (float) ($revenueStats->day ?? 0),
                'week'             => (float) ($revenueStats->week ?? 0),
                'month'            => (float) ($revenueStats->month ?? 0),
                'prev_month'       => (float) ($revenueStats->prev_month ?? 0),
                'variation_pct'    => $revenueVariation,
                'ca_consult_month' => $caConsultMonth,
                'unpaid'           => $unpaid,
            ],
            'absence_rate' => [
                'rate'     => $absenceRate,
                'absences' => (int) ($absStats->absences ?? 0),
                'total'    => (int) ($absStats->total ?? 0),
            ],
            'top_acts' => array_map(fn($a) => [
                'name'    => $a->name,
                'code'    => $a->code,
                'count'   => (int) $a->cnt,
                'revenue' => (float) $a->revenue,
            ], $topActs),
            'monthly_chart' => $monthlyChart,
            'demographics'  => [
                'total'      => (int) ($demo->total ?? 0),
                'gender'     => ['M' => (int) ($demo->male ?? 0), 'F' => (int) ($demo->female ?? 0)],
                'age_groups' => [
                    '0-18'  => (int) ($demo->age_0_18   ?? 0),
                    '18-30' => (int) ($demo->age_18_30  ?? 0),
                    '30-50' => (int) ($demo->age_30_50  ?? 0),
                    '50+'   => (int) ($demo->age_50_plus ?? 0),
                ],
            ],
        ]);
    }
}
