<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ArchivePatients extends Command
{
    protected $signature   = 'patients:auto-archive {--dry-run : Simuler sans archiver}';
    protected $description = 'Archive les patients inactifs depuis X mois (paramètre archive_after_months)';

    public function handle(): int
    {
        $months = (int) Setting::get('archive_after_months', '18');

        if ($months <= 0) {
            $this->info('Archivage automatique désactivé (archive_after_months = 0).');
            return 0;
        }

        $cutoff = Carbon::now()->subMonths($months)->toDateString();

        // Patients actifs non archivés dont le dernier RDV TERMINE est avant la date limite
        // OU sans aucun RDV TERMINE et créés avant la date limite
        $patients = Patient::where('is_archived', false)
            ->where('status', '!=', 'DECEDE')
            ->where(function ($q) use ($cutoff) {
                // Cas 1 : a eu des RDV TERMINE, mais le plus récent est trop ancien
                $q->where(function ($q1) use ($cutoff) {
                    $q1->whereExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('appointments')
                                ->whereColumn('appointments.patient_id', 'patients.id')
                                ->where('appointments.status', 'TERMINE');
                        })
                        ->where(function ($q2) use ($cutoff) {
                            $q2->whereRaw('(SELECT MAX(scheduled_date) FROM appointments WHERE patient_id = patients.id AND status = ?) <= ?', ['TERMINE', $cutoff]);
                        });
                })
                // Cas 2 : aucun RDV TERMINE et créé avant la date limite
                ->orWhere(function ($q3) use ($cutoff) {
                    $q3->whereNotExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('appointments')
                                ->whereColumn('appointments.patient_id', 'patients.id')
                                ->where('appointments.status', 'TERMINE');
                        })
                        ->where('patients.created_at', '<=', $cutoff);
                });
            })
            ->get();

        $count = $patients->count();

        if ($this->option('dry-run')) {
            $this->info("Simulation — {$count} patient(s) seraient archivés (inactifs depuis {$months} mois) :");
            foreach ($patients as $p) {
                $lastAppt = $p->appointments()->where('status', 'TERMINE')->max('scheduled_date');
                $since    = $lastAppt ? "dernier RDV : {$lastAppt}" : "aucun RDV terminé";
                $this->line("  [{$p->numero_dossier}] {$p->full_name} — {$since}");
            }
            return 0;
        }

        if ($count === 0) {
            $this->info('Aucun patient à archiver.');
            return 0;
        }

        $now = now();
        foreach ($patients as $p) {
            $p->update(['is_archived' => true, 'archived_at' => $now]);
        }

        $this->info("{$count} patient(s) archivés automatiquement (inactifs depuis {$months} mois).");
        return 0;
    }
}
