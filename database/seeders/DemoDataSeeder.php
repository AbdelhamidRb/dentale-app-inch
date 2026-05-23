<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\CatalogAct;
use App\Models\Consultation;
use App\Models\ConsultationAct;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Dentiste ─────────────────────────────────────────────────
        $dentist = User::firstOrCreate(
            ['email' => 'dentiste@demo.com'],
            [
                'name'     => 'Dr. Youssef Benali',
                'password' => bcrypt('password'),
                'role'     => 'DENTIST',
            ]
        );

        // ─── Actes catalogue ──────────────────────────────────────────
        if (CatalogAct::count() === 0) {
            $this->call(CatalogActSeeder::class);
        }
        $acts = CatalogAct::all();

        // ─── 20 patients marocains ────────────────────────────────────
        $patients = [
            ['first_name' => 'Fatima',    'last_name' => 'Ait Benhaddou', 'phone' => '0661234501', 'gender' => 'F', 'birth_date' => '1985-03-12'],
            ['first_name' => 'Mohamed',   'last_name' => 'Charani',        'phone' => '0661234502', 'gender' => 'M', 'birth_date' => '1978-07-25'],
            ['first_name' => 'Khadija',   'last_name' => 'Ouazzani',       'phone' => '0661234503', 'gender' => 'F', 'birth_date' => '1992-11-04'],
            ['first_name' => 'Yassine',   'last_name' => 'Tahiri',         'phone' => '0661234504', 'gender' => 'M', 'birth_date' => '1990-05-18'],
            ['first_name' => 'Nadia',     'last_name' => 'Berrada',        'phone' => '0661234505', 'gender' => 'F', 'birth_date' => '1995-08-30'],
            ['first_name' => 'Omar',      'last_name' => 'Ziani',          'phone' => '0661234506', 'gender' => 'M', 'birth_date' => '1970-01-15'],
            ['first_name' => 'Sanaa',     'last_name' => 'Bouchti',        'phone' => '0661234507', 'gender' => 'F', 'birth_date' => '1988-04-22'],
            ['first_name' => 'Rachid',    'last_name' => 'Alami',          'phone' => '0661234508', 'gender' => 'M', 'birth_date' => '1982-09-10'],
            ['first_name' => 'Houda',     'last_name' => 'Naciri',         'phone' => '0661234509', 'gender' => 'F', 'birth_date' => '1998-02-28'],
            ['first_name' => 'Hamza',     'last_name' => 'El Idrissi',     'phone' => '0661234510', 'gender' => 'M', 'birth_date' => '2000-06-14'],
            ['first_name' => 'Zineb',     'last_name' => 'Moussaoui',      'phone' => '0661234511', 'gender' => 'F', 'birth_date' => '1975-12-03'],
            ['first_name' => 'Karim',     'last_name' => 'Sebti',          'phone' => '0661234512', 'gender' => 'M', 'birth_date' => '1993-07-07'],
            ['first_name' => 'Laila',     'last_name' => 'Boujemaa',       'phone' => '0661234513', 'gender' => 'F', 'birth_date' => '1987-10-19'],
            ['first_name' => 'Adil',      'last_name' => 'Kettani',        'phone' => '0661234514', 'gender' => 'M', 'birth_date' => '1968-03-27'],
            ['first_name' => 'Meryem',    'last_name' => 'Qabbaj',         'phone' => '0661234515', 'gender' => 'F', 'birth_date' => '2001-05-05'],
            ['first_name' => 'Amine',     'last_name' => 'Tazi',           'phone' => '0661234516', 'gender' => 'M', 'birth_date' => '1983-08-16'],
            ['first_name' => 'Samira',    'last_name' => 'Chorfi',         'phone' => '0661234517', 'gender' => 'F', 'birth_date' => '1979-11-11'],
            ['first_name' => 'Idriss',    'last_name' => 'Hamdaoui',       'phone' => '0661234518', 'gender' => 'M', 'birth_date' => '1996-04-01'],
            ['first_name' => 'Nour',      'last_name' => 'Filali',         'phone' => '0661234519', 'gender' => 'F', 'birth_date' => '1991-09-23'],
            ['first_name' => 'Khalid',    'last_name' => 'Amrani',         'phone' => '0661234520', 'gender' => 'M', 'birth_date' => '1965-02-08'],
        ];

        $createdPatients = [];
        foreach ($patients as $data) {
            $last  = Patient::max('id') ?? 0;
            $p = Patient::create(array_merge($data, [
                'numero_dossier' => 'PAT-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT),
                'status'         => 'ACTIF',
                'is_archived'    => false,
            ]));
            $createdPatients[] = $p;
        }

        // ─── RDV + consultations ──────────────────────────────────────
        $today   = Carbon::today();
        $configs = [
            // [patient_idx, date_offset_days, start, end, rdv_status, consult_status, act_indices]
            [0,  -14, '09:00', '09:30', 'TERMINE',  'TERMINE',  [0, 1]],
            [1,  -10, '10:00', '10:30', 'TERMINE',  'TERMINE',  [2]],
            [2,   -7, '14:00', '14:30', 'TERMINE',  'EN_COURS', [4, 5]],
            [3,   -5, '11:00', '11:30', 'TERMINE',  'TERMINE',  [6]],
            [4,   -3, '09:30', '10:00', 'NO_SHOW',  null,       [0]],
            [5,   -2, '15:00', '15:30', 'TERMINE',  'TERMINE',  [1, 2]],
            [6,   -1, '08:30', '09:00', 'TERMINE',  'BROUILLON',[0]],
            [7,    0, '09:00', '09:30', 'PLANIFIE',  null,       [3]],
            [8,    0, '10:00', '10:45', 'PLANIFIE',  null,       [4]],
            [9,    0, '11:00', '11:30', 'PLANIFIE',  null,       [0, 1]],
            [10,   0, '14:00', '14:30', 'PLANIFIE',  null,       [2]],
            [11,   0, '15:00', '15:30', 'PLANIFIE',  null,       [5]],
            [12,   1, '09:00', '09:30', 'PLANIFIE',  null,       [0]],
            [13,   1, '10:30', '11:00', 'PLANIFIE',  null,       [6]],
            [14,   2, '09:30', '10:00', 'PLANIFIE',  null,       [1]],
            [15,   2, '11:00', '12:00', 'PLANIFIE',  null,       [5, 6]],
            [16,   3, '14:00', '14:30', 'PLANIFIE',  null,       [0]],
            [17,   3, '16:00', '16:30', 'PLANIFIE',  null,       [3]],
            [2,    4, '09:00', '09:30', 'PLANIFIE',  null,       [5]], // Khadija 2e RDV
            [0,    7, '10:00', '10:30', 'PLANIFIE',  null,       [0]], // Fatima suivi
        ];

        foreach ($configs as $cfg) {
            [$pIdx, $dayOffset, $start, $end, $rdvStatus, $consultStatus, $actIndices] = $cfg;

            $patient = $createdPatients[$pIdx];
            $date    = $today->copy()->addDays($dayOffset)->toDateString();

            // Crée le RDV
            $appt = Appointment::create([
                'patient_id'     => $patient->id,
                'created_by'     => $dentist->id,
                'scheduled_date' => $date,
                'start_time'     => $start . ':00',
                'end_time'       => $end . ':00',
                'status'         => $rdvStatus,
                'notes'          => null,
            ]);

            // Crée la consultation si demandé
            if ($consultStatus !== null) {
                $sessionDates = [$date];
                if ($consultStatus === 'EN_COURS') {
                    $sessionDates[] = $today->copy()->addDays($dayOffset + 3)->toDateString();
                }

                $consult = Consultation::create([
                    'patient_id'     => $patient->id,
                    'appointment_id' => $appt->id,
                    'created_by'     => $dentist->id,
                    'status'         => $consultStatus,
                    'notes'          => null,
                    'session_dates'  => $sessionDates,
                    'total_price'    => 0,
                ]);

                foreach ($actIndices as $i) {
                    $act = $acts[$i];
                    ConsultationAct::create([
                        'consultation_id' => $consult->id,
                        'catalog_act_id'  => $act->id,
                        'teeth'           => [],
                        'price'           => $act->base_price,
                        'notes'           => null,
                    ]);
                }

                $consult->recalculateTotal();
            }
        }

        $this->command->info('✓ 20 patients, ' . count($configs) . ' RDV et consultations créés.');
        $this->command->info('  Dentiste : dentiste@demo.com / password');
    }
}
