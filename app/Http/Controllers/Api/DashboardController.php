<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now        = Carbon::now();
        $today      = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $prevStart  = $now->copy()->subMonth()->startOfMonth()->toDateString();
        $prevEnd    = $now->copy()->subMonth()->endOfMonth()->toDateString();

        // ── 1. Patients ce mois ──────────────────────────────────────
        $patientStats = DB::selectOne("
            SELECT
                COUNT(*)          AS total,
                SUM(created_at >= ?) AS new_month
            FROM patients
            WHERE is_archived = 0
        ", [$monthStart]);

        // ── 2. Paiements aujourd'hui ─────────────────────────────────
        $paymentsToday = DB::selectOne("
            SELECT COUNT(*) AS cnt, COALESCE(SUM(pt.amount), 0) AS total
            FROM payment_transactions pt
            JOIN patients p ON p.id = pt.patient_id
            WHERE DATE(pt.date) = ? AND p.is_archived = 0
        ", [$today]);

        // ── 3. Encaissé ce mois + mois précédent ─────────────────────
        $revenueStats = DB::selectOne("
            SELECT
                SUM(CASE WHEN pt.date BETWEEN ? AND ? THEN pt.amount ELSE 0 END) AS month,
                SUM(CASE WHEN pt.date BETWEEN ? AND ? THEN pt.amount ELSE 0 END) AS prev_month,
                SUM(pt.amount)                                                     AS total_ever
            FROM payment_transactions pt
            JOIN patients p ON p.id = pt.patient_id
            WHERE p.is_archived = 0
        ", [$monthStart, $today, $prevStart, $prevEnd]);

        $variationPct = (($revenueStats->prev_month ?? 0) > 0)
            ? round(($revenueStats->month - $revenueStats->prev_month) / $revenueStats->prev_month * 100, 1)
            : null;

        // ── 4. Impayés (ce que les patients doivent encore) ──────────
        $totalDette = (float) DB::selectOne("
            SELECT COALESCE(SUM(c.total_price), 0) AS s
            FROM consultations c
            JOIN patients p ON p.id = c.patient_id
            WHERE p.is_archived = 0
        ")->s;
        $unpaid = max(0, $totalDette - (float) ($revenueStats->total_ever ?? 0));

        // ── 5. Absentéisme du mois ───────────────────────────────────
        $absStats = DB::selectOne("
            SELECT
                SUM(status IN ('NO_SHOW','ANNULE')) AS absences,
                COUNT(*)                             AS total
            FROM appointments
            WHERE scheduled_date >= ?
        ", [$monthStart]);

        $absenceRate = ($absStats->total > 0)
            ? round($absStats->absences / $absStats->total * 100, 1)
            : 0;

        // ── 6. Graphique revenus 6 derniers mois ─────────────────────
        $sixMonthsAgo = $now->copy()->subMonths(5)->startOfMonth()->toDateString();

        $revenueByMonth = DB::select("
            SELECT DATE_FORMAT(pt.date, '%Y-%m') AS m, SUM(pt.amount) AS revenue
            FROM payment_transactions pt
            JOIN patients p ON p.id = pt.patient_id
            WHERE pt.date >= ? AND p.is_archived = 0
            GROUP BY m
        ", [$sixMonthsAgo]);
        $revenueMap = collect($revenueByMonth)->pluck('revenue', 'm');

        $monthlyChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $d   = $now->copy()->subMonths($i);
            $key = $d->format('Y-m');
            $monthlyChart[] = [
                'month'   => $d->locale('fr')->isoFormat('MMM YY'),
                'revenue' => (float) ($revenueMap[$key] ?? 0),
            ];
        }

        // ── 7. Répartition patients ───────────────────────────────────
        $demo = DB::selectOne("
            SELECT
                COUNT(*)                                                          AS total,
                SUM(gender = 'M')                                                 AS male,
                SUM(gender = 'F')                                                 AS female,
                SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) <= 18)             AS age_0_18,
                SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 19 AND 30) AS age_18_30,
                SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 31 AND 50) AS age_30_50,
                SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) > 50)              AS age_50_plus
            FROM patients
            WHERE is_archived = 0
        ");

        return response()->json([
            'patients' => [
                'total'          => (int) ($patientStats->total ?? 0),
                'new_this_month' => (int) ($patientStats->new_month ?? 0),
            ],
            'payments_today' => [
                'count'  => (int) ($paymentsToday->cnt ?? 0),
                'amount' => (float) ($paymentsToday->total ?? 0),
            ],
            'revenue' => [
                'month'         => (float) ($revenueStats->month ?? 0),
                'prev_month'    => (float) ($revenueStats->prev_month ?? 0),
                'variation_pct' => $variationPct,
                'unpaid'        => $unpaid,
            ],
            'absence_rate' => [
                'rate'     => $absenceRate,
                'absences' => (int) ($absStats->absences ?? 0),
                'total'    => (int) ($absStats->total ?? 0),
            ],
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
