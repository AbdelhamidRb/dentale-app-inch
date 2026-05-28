<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\CatalogAct;
use App\Models\Consultation;
use App\Models\ConsultationAct;
use App\Models\MedicalAlert;
use App\Models\Patient;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    private array $firstNames = [
        'Mohamed', 'Fatima', 'Ahmed', 'Khadija', 'Youssef', 'Amina', 'Hassan', 'Zineb',
        'Omar', 'Nadia', 'Khalid', 'Sanaa', 'Rachid', 'Houda', 'Karim', 'Layla',
        'Hamid', 'Sara', 'Abdelhamid', 'Meriem', 'Mustapha', 'Soukaina', 'Samir', 'Widad',
        'Driss', 'Imane', 'Tarik', 'Loubna', 'Aziz', 'Najat',
    ];

    private array $lastNames = [
        'Benali', 'El Fassi', 'Idrissi', 'Alaoui', 'Berrada', 'Tazi', 'Bensouda', 'Chraibi',
        'Bennani', 'Lahlou', 'Benkirane', 'Haddad', 'Amrani', 'Bouazza', 'Filali', 'Kettani',
        'Moussaoui', 'Sebti', 'Slaoui', 'Rhajaoui', 'Belhaj', 'Ouazzani', 'Naciri', 'Tahiri',
    ];

    private array $phones = [
        '0612', '0613', '0614', '0615', '0616', '0617', '0618', '0619',
        '0620', '0621', '0622', '0623', '0624', '0625',
    ];

    public function run(): void
    {
        $dentist = User::where('role', 'DENTIST')->first();
        $acts    = CatalogAct::all();

        if (!$dentist || $acts->isEmpty()) {
            $this->command->error('Lance d\'abord ProductionSeeder.');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->command->info('Génération de 120 patients...');
        $patients = [];

        for ($i = 0; $i < 120; $i++) {
            $firstName = $this->firstNames[array_rand($this->firstNames)];
            $lastName  = $this->lastNames[array_rand($this->lastNames)];
            $gender    = ['M', 'F'][array_rand(['M', 'F'])];
            $phone     = $this->phones[array_rand($this->phones)] . rand(100000, 999999);
            $createdAt = Carbon::now()->subDays(rand(0, 730));

            $patient = Patient::create([
                'numero_dossier' => Patient::generateNumeroDossier(),
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'phone'          => $phone,
                'gender'         => $gender,
                'birth_date'     => Carbon::now()->subYears(rand(10, 75))->subDays(rand(0, 365)),
                'couverture'     => ['CNSS', 'CNOPS', 'RAMED', 'ASSURANCE', 'AUCUNE'][array_rand(['CNSS', 'CNOPS', 'RAMED', 'ASSURANCE', 'AUCUNE'])],
                'status'         => rand(0, 10) > 1 ? 'ACTIF' : 'INACTIF',
                'is_archived'    => false,
                'created_at'     => $createdAt,
                'updated_at'     => $createdAt,
            ]);

            // Alertes médicales (30% des patients)
            if (rand(0, 10) < 3) {
                MedicalAlert::create([
                    'patient_id'  => $patient->id,
                    'created_by'  => $dentist->id,
                    'description' => ['Allergie à la pénicilline', 'Diabète type 2', 'Hypertension', 'Anticoagulants', 'Asthme'][array_rand(['Allergie à la pénicilline', 'Diabète type 2', 'Hypertension', 'Anticoagulants', 'Asthme'])],
                    'severity'    => ['ROUGE', 'ORANGE', 'JAUNE'][array_rand(['ROUGE', 'ORANGE', 'JAUNE'])],
                ]);
            }

            $patients[] = $patient;
        }

        $this->command->info('Génération des RDV (90 jours)...');
        $statuses = ['PLANIFIE', 'CONFIRME', 'TERMINE', 'ANNULE', 'NO_SHOW', 'EN_COURS'];
        $weights  = [5, 10, 60, 10, 10, 5];

        for ($day = -60; $day <= 30; $day++) {
            $date      = Carbon::now()->addDays($day)->format('Y-m-d');
            $numAppts  = rand(4, 10);
            $usedSlots = [];

            for ($a = 0; $a < $numAppts; $a++) {
                $hour   = rand(9, 17);
                $minute = [0, 15, 30, 45][array_rand([0, 15, 30, 45])];
                $slot   = $hour * 60 + $minute;

                if (in_array($slot, $usedSlots)) continue;
                $usedSlots[] = $slot;

                $startTime = sprintf('%02d:%02d', $hour, $minute);
                $endHour   = $minute + 30 >= 60 ? $hour + 1 : $hour;
                $endMin    = ($minute + 30) % 60;
                $endTime   = sprintf('%02d:%02d', $endHour, $endMin);

                if ($endHour >= 18) continue;

                $status  = $this->weightedRandom($statuses, $weights);
                $patient = $patients[array_rand($patients)];

                $appointment = Appointment::create([
                    'patient_id'     => $patient->id,
                    'created_by'     => $dentist->id,
                    'scheduled_date' => $date,
                    'start_time'     => $startTime,
                    'end_time'       => $endTime,
                    'status'         => $status,
                    'created_at'     => Carbon::parse($date)->subDays(rand(1, 14)),
                ]);

                // Consultation pour les RDV terminés
                if ($status === 'TERMINE' && rand(0, 10) > 2) {
                    $selectedActs = $acts->random(rand(1, 3));
                    $totalPrice   = 0;

                    $consultation = Consultation::create([
                        'patient_id'    => $patient->id,
                        'appointment_id'=> $appointment->id,
                        'created_by'    => $dentist->id,
                        'status'        => 'TERMINE',
                        'notes'         => rand(0, 1) ? 'Traitement effectué sans complications.' : null,
                        'total_price'   => 0,
                        'session_dates' => [$date],
                        'created_at'    => Carbon::parse($date),
                    ]);

                    foreach ($selectedActs as $act) {
                        $price = $act->base_price * (rand(90, 110) / 100);
                        ConsultationAct::create([
                            'consultation_id' => $consultation->id,
                            'catalog_act_id'  => $act->id,
                            'price'           => round($price),
                            'teeth'           => [rand(11, 48)],
                            'notes'           => null,
                        ]);
                        $totalPrice += round($price);
                    }

                    $consultation->update(['total_price' => $totalPrice]);

                    // Paiement (80% payent tout ou partie)
                    if (rand(0, 10) < 8) {
                        $paid = rand(0, 10) > 3
                            ? $totalPrice
                            : round($totalPrice * rand(50, 90) / 100);

                        PaymentTransaction::create([
                            'patient_id' => $patient->id,
                            'amount'     => $paid,
                            'date'       => Carbon::parse($date)->addDays(rand(0, 3))->format('Y-m-d'),
                            'notes'      => 'Paiement consultation',
                            'created_by' => $dentist->id,
                        ]);
                    }
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('✓ 120 patients, RDV, consultations et paiements générés.');
    }

    private function weightedRandom(array $items, array $weights): string
    {
        $total = array_sum($weights);
        $rand  = rand(1, $total);
        $cumul = 0;
        foreach ($items as $i => $item) {
            $cumul += $weights[$i];
            if ($rand <= $cumul) return $item;
        }
        return $items[0];
    }
}
