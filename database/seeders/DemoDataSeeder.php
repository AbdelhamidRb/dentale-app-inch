<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentAct;
use App\Models\CatalogAct;
use App\Models\Consultation;
use App\Models\ConsultationAct;
use App\Models\MedicalAlert;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // 1. NETTOYAGE COMPLET
        // ═══════════════════════════════════════════════════════════════
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        PaymentTransaction::truncate();
        ConsultationAct::truncate();
        Consultation::truncate();
        AppointmentAct::truncate();
        Appointment::truncate();
        MedicalAlert::truncate();
        PatientDocument::truncate();
        Patient::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Données supprimées.');

        // ═══════════════════════════════════════════════════════════════
        // 2. DENTISTE
        // ═══════════════════════════════════════════════════════════════
        $dentist = User::firstOrCreate(
            ['email' => 'dentiste@demo.com'],
            [
                'name'     => 'Dr. Youssef Benali',
                'password' => bcrypt('password'),
                'role'     => 'DENTIST',
            ]
        );

        User::firstOrCreate(
            ['email' => 'assistant@demo.com'],
            [
                'name'     => 'Sara Idrissi',
                'password' => bcrypt('password'),
                'role'     => 'ASSISTANT',
            ]
        );

        // ═══════════════════════════════════════════════════════════════
        // 3. CATALOGUE D'ACTES
        // ═══════════════════════════════════════════════════════════════
        if (CatalogAct::count() === 0) {
            $this->call(CatalogActSeeder::class);
        }
        $acts = CatalogAct::all()->keyBy('code');

        // Raccourcis
        $CONS = $acts['CONS001']; // 150 MAD - 30min
        $DET  = $acts['DET001'];  // 400 MAD - 30min
        $EXT1 = $acts['EXT001'];  // 300 MAD - 30min
        $EXT2 = $acts['EXT002'];  // 600 MAD - 60min
        $OBT  = $acts['OBT001'];  // 350 MAD - 30min
        $CAN  = $acts['CAN001'];  // 1200 MAD - 60min
        $COU  = $acts['COU001'];  // 2500 MAD - 60min
        $RAD1 = $acts['RAD001'];  // 100 MAD - 15min
        $RAD2 = $acts['RAD002'];  // 250 MAD - 15min
        $BLA  = $acts['BLA001'];  // 1500 MAD - 60min

        // ═══════════════════════════════════════════════════════════════
        // 4. PATIENTS (33 actifs + 2 archivés)
        // ═══════════════════════════════════════════════════════════════
        $patientsData = [
            // idx 0-9 : patients avec historique complet
            ['first_name' => 'Fatima',    'last_name' => 'Ait Benhaddou', 'phone' => '0661234501', 'gender' => 'F', 'birth_date' => '1985-03-12', 'couverture' => 'CNSS'],
            ['first_name' => 'Mohamed',   'last_name' => 'Charani',       'phone' => '0661234502', 'gender' => 'M', 'birth_date' => '1978-07-25', 'couverture' => 'CNOPS'],
            ['first_name' => 'Khadija',   'last_name' => 'Ouazzani',      'phone' => '0661234503', 'gender' => 'F', 'birth_date' => '1992-11-04', 'couverture' => 'CNSS'],
            ['first_name' => 'Yassine',   'last_name' => 'Tahiri',        'phone' => '0661234504', 'gender' => 'M', 'birth_date' => '1990-05-18', 'couverture' => 'AUCUNE'],
            ['first_name' => 'Nadia',     'last_name' => 'Berrada',       'phone' => '0661234505', 'gender' => 'F', 'birth_date' => '1995-08-30', 'couverture' => 'ASSURANCE'],
            ['first_name' => 'Omar',      'last_name' => 'Ziani',         'phone' => '0661234506', 'gender' => 'M', 'birth_date' => '1970-01-15', 'couverture' => 'RAMED'],
            ['first_name' => 'Sanaa',     'last_name' => 'Bouchti',       'phone' => '0661234507', 'gender' => 'F', 'birth_date' => '1988-04-22', 'couverture' => 'CNSS'],
            ['first_name' => 'Rachid',    'last_name' => 'Alami',         'phone' => '0661234508', 'gender' => 'M', 'birth_date' => '1982-09-10', 'couverture' => 'ASSURANCE'],
            ['first_name' => 'Houda',     'last_name' => 'Naciri',        'phone' => '0661234509', 'gender' => 'F', 'birth_date' => '1998-02-28', 'couverture' => 'AUCUNE'],
            ['first_name' => 'Hamza',     'last_name' => 'El Idrissi',    'phone' => '0661234510', 'gender' => 'M', 'birth_date' => '2000-06-14', 'couverture' => 'CNSS'],
            // idx 10-19
            ['first_name' => 'Zineb',     'last_name' => 'Moussaoui',     'phone' => '0661234511', 'gender' => 'F', 'birth_date' => '1975-12-03', 'couverture' => 'CNOPS'],
            ['first_name' => 'Karim',     'last_name' => 'Sebti',         'phone' => '0661234512', 'gender' => 'M', 'birth_date' => '1993-07-07', 'couverture' => 'AUCUNE'],
            ['first_name' => 'Laila',     'last_name' => 'Boujemaa',      'phone' => '0661234513', 'gender' => 'F', 'birth_date' => '1987-10-19', 'couverture' => 'ASSURANCE'],
            ['first_name' => 'Adil',      'last_name' => 'Kettani',       'phone' => '0661234514', 'gender' => 'M', 'birth_date' => '1968-03-27', 'couverture' => 'RAMED'],
            ['first_name' => 'Meryem',    'last_name' => 'Qabbaj',        'phone' => '0661234515', 'gender' => 'F', 'birth_date' => '2001-05-05', 'couverture' => 'CNSS'],
            ['first_name' => 'Amine',     'last_name' => 'Tazi',          'phone' => '0661234516', 'gender' => 'M', 'birth_date' => '1983-08-16', 'couverture' => 'CNOPS'],
            ['first_name' => 'Samira',    'last_name' => 'Chorfi',        'phone' => '0661234517', 'gender' => 'F', 'birth_date' => '1979-11-11', 'couverture' => 'ASSURANCE'],
            ['first_name' => 'Idriss',    'last_name' => 'Hamdaoui',      'phone' => '0661234518', 'gender' => 'M', 'birth_date' => '1996-04-01', 'couverture' => 'AUCUNE'],
            ['first_name' => 'Nour',      'last_name' => 'Filali',        'phone' => '0661234519', 'gender' => 'F', 'birth_date' => '1991-09-23', 'couverture' => 'CNSS'],
            ['first_name' => 'Khalid',    'last_name' => 'Amrani',        'phone' => '0661234520', 'gender' => 'M', 'birth_date' => '1965-02-08', 'couverture' => 'CNOPS'],
            // idx 20-29 : patients supplémentaires
            ['first_name' => 'Imane',     'last_name' => 'Boutaleb',      'phone' => '0662100020', 'gender' => 'F', 'birth_date' => '1994-06-10', 'couverture' => 'CNSS'],
            ['first_name' => 'Tariq',     'last_name' => 'Mansouri',      'phone' => '0662100021', 'gender' => 'M', 'birth_date' => '1986-12-25', 'couverture' => 'AUCUNE'],
            ['first_name' => 'Souad',     'last_name' => 'Bekkali',       'phone' => '0662100022', 'gender' => 'F', 'birth_date' => '1972-03-18', 'couverture' => 'RAMED'],
            ['first_name' => 'Bilal',     'last_name' => 'Errachidi',     'phone' => '0662100023', 'gender' => 'M', 'birth_date' => '2002-08-05', 'couverture' => 'AUCUNE'],
            ['first_name' => 'Hasnaa',    'last_name' => 'Benkirane',     'phone' => '0662100024', 'gender' => 'F', 'birth_date' => '1989-01-30', 'couverture' => 'ASSURANCE'],
            ['first_name' => 'Ayoub',     'last_name' => 'Chraibi',       'phone' => '0662100025', 'gender' => 'M', 'birth_date' => '1997-04-14', 'couverture' => 'CNSS'],
            ['first_name' => 'Widad',     'last_name' => 'Saadouni',      'phone' => '0662100026', 'gender' => 'F', 'birth_date' => '1980-07-22', 'couverture' => 'CNOPS'],
            ['first_name' => 'Mehdi',     'last_name' => 'Benbrahim',     'phone' => '0662100027', 'gender' => 'M', 'birth_date' => '1974-10-09', 'couverture' => 'RAMED'],
            ['first_name' => 'Ghizlane',  'last_name' => 'Lahlou',        'phone' => '0662100028', 'gender' => 'F', 'birth_date' => '1999-02-17', 'couverture' => 'CNSS'],
            ['first_name' => 'Driss',     'last_name' => 'Benchekroun',   'phone' => '0662100029', 'gender' => 'M', 'birth_date' => '1963-05-31', 'couverture' => 'ASSURANCE'],
            // idx 30-32 : RDV futurs seulement (nouveaux patients)
            ['first_name' => 'Rania',     'last_name' => 'Hassani',       'phone' => '0663200030', 'gender' => 'F', 'birth_date' => '2003-11-12', 'couverture' => 'AUCUNE'],
            ['first_name' => 'Otmane',    'last_name' => 'Cherkaoui',     'phone' => '0663200031', 'gender' => 'M', 'birth_date' => '1977-06-08', 'couverture' => 'CNSS'],
            ['first_name' => 'Loubna',    'last_name' => 'Squalli',       'phone' => '0663200032', 'gender' => 'F', 'birth_date' => '1990-09-03', 'couverture' => 'CNOPS'],
            // idx 33-34 : archivés
            ['first_name' => 'Yousra',    'last_name' => 'Bennis',        'phone' => '0664300033', 'gender' => 'F', 'birth_date' => '1982-04-16', 'couverture' => 'AUCUNE', 'is_archived' => true],
            ['first_name' => 'Hassan',    'last_name' => 'Oulhaj',        'phone' => '0664300034', 'gender' => 'M', 'birth_date' => '1960-08-20', 'couverture' => 'RAMED',  'is_archived' => true],
        ];

        $ps = [];
        foreach ($patientsData as $data) {
            $isArchived = $data['is_archived'] ?? false;
            unset($data['is_archived']);
            $last = Patient::max('id') ?? 0;
            $p = Patient::create(array_merge($data, [
                'numero_dossier' => 'PAT-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT),
                'status'         => 'ACTIF',
                'is_archived'    => $isArchived,
                'archived_at'    => $isArchived ? Carbon::now()->subDays(15) : null,
                'notes'          => null,
                'no_show_count'  => 0,
            ]));
            $ps[] = $p;
        }

        // ═══════════════════════════════════════════════════════════════
        // 5. ALERTES MÉDICALES
        // ═══════════════════════════════════════════════════════════════

        // Fatima (idx 0) - allergie pénicilline critique
        MedicalAlert::create(['patient_id' => $ps[0]->id, 'type' => 'ALLERGIE',   'description' => 'Allergie à la pénicilline — réaction anaphylactique documentée',     'severity' => 'ROUGE', 'created_by' => $dentist->id]);

        // Mohamed (idx 1) - diabète + médicament
        MedicalAlert::create(['patient_id' => $ps[1]->id, 'type' => 'MALADIE',    'description' => 'Diabète de type 2 — surveillance glycémique avant intervention',       'severity' => 'ORANGE', 'created_by' => $dentist->id]);
        MedicalAlert::create(['patient_id' => $ps[1]->id, 'type' => 'MEDICATION', 'description' => 'Metformine 1000mg/j — pas d\'interaction connue en dentaire',          'severity' => 'JAUNE', 'created_by' => $dentist->id]);

        // Rachid (idx 7) - anticoagulant critique
        MedicalAlert::create(['patient_id' => $ps[7]->id, 'type' => 'MEDICATION', 'description' => 'Warfarine (anticoagulant) — contrôle INR obligatoire avant extraction', 'severity' => 'ROUGE', 'created_by' => $dentist->id]);

        // Zineb (idx 10) - double allergie critique
        MedicalAlert::create(['patient_id' => $ps[10]->id, 'type' => 'ALLERGIE',  'description' => 'Allergie au latex — utiliser gants sans latex uniquement',             'severity' => 'ROUGE', 'created_by' => $dentist->id]);
        MedicalAlert::create(['patient_id' => $ps[10]->id, 'type' => 'ALLERGIE',  'description' => 'Allergie à l\'ibuprofène — prescrire paracétamol à la place',          'severity' => 'ROUGE', 'created_by' => $dentist->id]);

        // Adil (idx 13) - insuffisance rénale
        MedicalAlert::create(['patient_id' => $ps[13]->id, 'type' => 'MALADIE',   'description' => 'Insuffisance rénale chronique stade 3 — adapter posologie antibiotiques', 'severity' => 'ROUGE', 'created_by' => $dentist->id]);

        // Khalid (idx 19) - HTA + traitement
        MedicalAlert::create(['patient_id' => $ps[19]->id, 'type' => 'MALADIE',   'description' => 'Hypertension artérielle traitée — éviter vasoconstricteurs',           'severity' => 'ORANGE', 'created_by' => $dentist->id]);
        MedicalAlert::create(['patient_id' => $ps[19]->id, 'type' => 'MEDICATION', 'description' => 'Amlodipine 10mg/j — hyperplasie gingivale possible',                   'severity' => 'JAUNE', 'created_by' => $dentist->id]);

        // Houda (idx 8) - allergie anesthésie
        MedicalAlert::create(['patient_id' => $ps[8]->id,  'type' => 'ALLERGIE',  'description' => 'Intolérance à la lidocaïne — utiliser articaïne sans épinéphrine',     'severity' => 'ORANGE', 'created_by' => $dentist->id]);

        // Driss (idx 29) - cardiopathie
        MedicalAlert::create(['patient_id' => $ps[29]->id, 'type' => 'MALADIE',   'description' => 'Cardiopathie valvulaire — antibioprophylaxie obligatoire',              'severity' => 'ROUGE', 'created_by' => $dentist->id]);

        $this->command->info('Alertes médicales créées.');

        // ═══════════════════════════════════════════════════════════════
        // 6. HELPER INTERNE — crée RDV + consultation + actes
        // ═══════════════════════════════════════════════════════════════
        $today = Carbon::today();

        $makeConsult = function (
            Patient $patient,
            string $date,
            string $start,
            string $end,
            string $rdvStatus,
            ?string $consultStatus,
            array $acts,   // [['act' => $CAN, 'teeth' => [36], 'price' => 1200], ...]
            ?string $rdvNote = null,
            ?string $consultNote = null
        ) use ($dentist) {
            $appt = Appointment::create([
                'patient_id'     => $patient->id,
                'created_by'     => $dentist->id,
                'scheduled_date' => $date,
                'start_time'     => $start . ':00',
                'end_time'       => $end . ':00',
                'status'         => $rdvStatus,
                'notes'          => $rdvNote,
            ]);

            if ($consultStatus === null) return $appt;

            $consult = Consultation::create([
                'patient_id'     => $patient->id,
                'appointment_id' => $appt->id,
                'created_by'     => $dentist->id,
                'status'         => $consultStatus,
                'notes'          => $consultNote,
                'session_dates'  => [$date],
                'total_price'    => 0,
            ]);

            foreach ($acts as $a) {
                ConsultationAct::create([
                    'consultation_id' => $consult->id,
                    'catalog_act_id'  => $a['act']->id,
                    'teeth'           => $a['teeth'] ?? [],
                    'price'           => $a['price'] ?? $a['act']->base_price,
                    'notes'           => $a['note'] ?? null,
                ]);
            }

            $consult->recalculateTotal();
            return $consult;
        };

        $pay = function (Patient $patient, float $amount, string $date, ?string $note = null) use ($dentist) {
            PaymentTransaction::create([
                'patient_id' => $patient->id,
                'created_by' => $dentist->id,
                'amount'     => $amount,
                'date'       => $date,
                'notes'      => $note,
            ]);
        };

        // ═══════════════════════════════════════════════════════════════
        // 7. HISTORIQUE & RDV — patient par patient
        // ═══════════════════════════════════════════════════════════════

        // ── Fatima Ait Benhaddou [0] ─ PAYÉ ────────────────────────
        // Consultation 1 : Détartrage + Radio panoramique (il y a 3 mois)
        $makeConsult($ps[0], $today->copy()->subDays(90)->toDateString(), '09:00', '10:00', 'TERMINE', 'TERMINE', [
            ['act' => $DET,  'teeth' => [],   'price' => 400],
            ['act' => $RAD2, 'teeth' => [],   'price' => 250, 'note' => 'Vue complète avant traitement'],
        ], null, 'Détartrage complet + panoramique de bilan');
        // Consultation 2 : OBT 26 (il y a 2 mois)
        $makeConsult($ps[0], $today->copy()->subDays(60)->toDateString(), '09:00', '09:30', 'TERMINE', 'TERMINE', [
            ['act' => $OBT, 'teeth' => [26], 'price' => 350],
        ], null, 'Obturation composite 26');
        // Paiements : 650 + 350 = 1000 = total → PAYÉ
        $pay($ps[0], 650, $today->copy()->subDays(85)->toDateString(), 'Acompte détartrage + panoramique');
        $pay($ps[0], 350, $today->copy()->subDays(55)->toDateString(), 'Règlement obturation 26');
        // RDV futur : contrôle
        $makeConsult($ps[0], $today->copy()->addDays(7)->toDateString(), '09:00', '09:30', 'PLANIFIE', null, [], 'Contrôle 3 mois');

        // ── Mohamed Charani [1] ─ PARTIEL ───────────────────────────
        // Consultation : Canal 36 + Couronne 36 (en cours)
        $makeConsult($ps[1], $today->copy()->subDays(30)->toDateString(), '10:00', '11:00', 'TERMINE', 'EN_COURS', [
            ['act' => $CAN, 'teeth' => [36], 'price' => 1200, 'note' => 'Traitement endodontique 36 — 3 séances prévues'],
            ['act' => $COU, 'teeth' => [36], 'price' => 2500, 'note' => 'Couronne zircone 36'],
        ], null, 'Traitement canal + couronne 36 — séance 1/3');
        // RDV séance 2
        $makeConsult($ps[1], $today->copy()->subDays(15)->toDateString(), '10:00', '11:00', 'TERMINE', null, [], null, 'Séance 2 canal 36');
        // Paiement partiel : 1500 sur 3700
        $pay($ps[1], 1000, $today->copy()->subDays(28)->toDateString(), 'Acompte traitement canal 36');
        $pay($ps[1], 500,  $today->copy()->subDays(12)->toDateString(), 'Versement intermédiaire');
        // RDV séance finale
        $makeConsult($ps[1], $today->copy()->addDays(5)->toDateString(), '10:00', '11:00', 'PLANIFIE', null, [], 'Pose couronne définitive 36');

        // ── Khadija Ouazzani [2] ─ PAYÉ ────────────────────────────
        // Consultation : Blanchiment
        $makeConsult($ps[2], $today->copy()->subDays(45)->toDateString(), '14:00', '15:00', 'TERMINE', 'TERMINE', [
            ['act' => $BLA, 'teeth' => [], 'price' => 1500],
        ], null, 'Blanchiment au fauteuil');
        // Consultation : Détartrage de routine
        $makeConsult($ps[2], $today->copy()->subDays(10)->toDateString(), '14:00', '14:30', 'TERMINE', 'TERMINE', [
            ['act' => $DET, 'teeth' => [], 'price' => 400],
        ], null, 'Détartrage');
        // Payé en totalité : 1900
        $pay($ps[2], 1500, $today->copy()->subDays(42)->toDateString(), 'Règlement blanchiment');
        $pay($ps[2], 400,  $today->copy()->subDays(8)->toDateString(),  'Règlement détartrage');

        // ── Yassine Tahiri [3] ─ AVANCE (a trop payé) ───────────────
        // Consultation : EXT 18 + Radio
        $makeConsult($ps[3], $today->copy()->subDays(20)->toDateString(), '11:00', '11:30', 'TERMINE', 'TERMINE', [
            ['act' => $EXT1, 'teeth' => [18], 'price' => 300, 'note' => 'Extraction dent de sagesse 18'],
            ['act' => $RAD1, 'teeth' => [18], 'price' => 100],
        ], null, 'Extraction 18 + radio');
        // A payé 500 pour 400 → AVANCE de 100
        $pay($ps[3], 500, $today->copy()->subDays(18)->toDateString(), 'Paiement extraction — rendu monnaie prévu');

        // ── Nadia Berrada [4] ─ PARTIEL avec historique long ────────
        // Consultation 1 : Consultation + bilan (4 mois)
        $makeConsult($ps[4], $today->copy()->subDays(120)->toDateString(), '09:30', '10:00', 'TERMINE', 'TERMINE', [
            ['act' => $CONS, 'teeth' => [], 'price' => 150],
            ['act' => $RAD2, 'teeth' => [], 'price' => 250],
        ]);
        // Consultation 2 : Canal 46 (3 mois)
        $makeConsult($ps[4], $today->copy()->subDays(90)->toDateString(), '09:30', '10:30', 'TERMINE', 'TERMINE', [
            ['act' => $CAN, 'teeth' => [46], 'price' => 1200],
        ]);
        // Consultation 3 : Couronne 46 (2 mois)
        $makeConsult($ps[4], $today->copy()->subDays(60)->toDateString(), '09:30', '10:30', 'TERMINE', 'TERMINE', [
            ['act' => $COU, 'teeth' => [46], 'price' => 2500],
        ]);
        // Total : 4100, payé 2500 → PARTIEL
        $pay($ps[4], 1500, $today->copy()->subDays(115)->toDateString(), 'Acompte bilan');
        $pay($ps[4], 1000, $today->copy()->subDays(85)->toDateString(),  'Versement canal 46');

        // ── Omar Ziani [5] ─ PARTIEL (rien payé encore) ─────────────
        // Consultation : Détartrage + OBT 16
        $makeConsult($ps[5], $today->copy()->subDays(7)->toDateString(), '15:00', '15:30', 'TERMINE', 'TERMINE', [
            ['act' => $DET, 'teeth' => [], 'price' => 400],
            ['act' => $OBT, 'teeth' => [16], 'price' => 350],
        ], null, 'Rappeler pour règlement');
        // Aucun paiement → PARTIEL 750

        // ── Sanaa Bouchti [6] ─ PAYÉ avec multiple visites ──────────
        // Consultation 1 : Urgence douleur 24
        $makeConsult($ps[6], $today->copy()->subDays(50)->toDateString(), '08:30', '09:00', 'TERMINE', 'TERMINE', [
            ['act' => $CONS, 'teeth' => [24], 'price' => 150, 'note' => 'Consultation urgence douleur 24'],
            ['act' => $RAD1, 'teeth' => [24], 'price' => 100],
        ]);
        // Consultation 2 : OBT 24
        $makeConsult($ps[6], $today->copy()->subDays(45)->toDateString(), '08:30', '09:00', 'TERMINE', 'TERMINE', [
            ['act' => $OBT, 'teeth' => [24], 'price' => 350],
        ]);
        // Total : 600, payé 600 → PAYÉ
        $pay($ps[6], 600, $today->copy()->subDays(42)->toDateString(), 'Règlement total 2 consultations');

        // ── Rachid Alami [7] ─ EN_COURS (traitement long) ───────────
        // Consultation : Canal 47 (séance 1 — il y a 2 semaines)
        $makeConsult($ps[7], $today->copy()->subDays(14)->toDateString(), '11:00', '12:00', 'TERMINE', 'EN_COURS', [
            ['act' => $CAN, 'teeth' => [47], 'price' => 1200],
        ], null, 'Canal 47 — séance 1, médicament provisoire en place');
        // Acompte
        $pay($ps[7], 600, $today->copy()->subDays(12)->toDateString(), 'Acompte canal 47');
        // RDV séance 2 aujourd'hui
        $makeConsult($ps[7], $today->toDateString(), '11:00', '12:00', 'PLANIFIE', null, [], 'Séance 2 canal 47 — obturation définitive');

        // ── Houda Naciri [8] ─ AVANCE ────────────────────────────────
        // Consultation : Détartrage + Consultation
        $makeConsult($ps[8], $today->copy()->subDays(35)->toDateString(), '10:00', '10:30', 'TERMINE', 'TERMINE', [
            ['act' => $CONS, 'teeth' => [], 'price' => 150],
            ['act' => $DET,  'teeth' => [], 'price' => 400],
        ]);
        // A payé 700 pour 550 → AVANCE de 150
        $pay($ps[8], 700, $today->copy()->subDays(33)->toDateString(), 'Chèque pré-signé — trop perçu à créditer');

        // ── Hamza El Idrissi [9] ─ 3 visites, PARTIEL ───────────────
        $makeConsult($ps[9], $today->copy()->subDays(60)->toDateString(), '09:00', '09:30', 'TERMINE', 'TERMINE', [
            ['act' => $CONS, 'teeth' => [], 'price' => 150],
        ]);
        $makeConsult($ps[9], $today->copy()->subDays(40)->toDateString(), '09:00', '09:30', 'TERMINE', 'TERMINE', [
            ['act' => $DET, 'teeth' => [], 'price' => 400],
        ]);
        $makeConsult($ps[9], $today->copy()->subDays(20)->toDateString(), '09:00', '09:30', 'TERMINE', 'TERMINE', [
            ['act' => $OBT, 'teeth' => [15], 'price' => 350],
            ['act' => $OBT, 'teeth' => [25], 'price' => 350],
        ]);
        // Total 1250, payé 900 → PARTIEL
        $pay($ps[9], 550, $today->copy()->subDays(55)->toDateString());
        $pay($ps[9], 350, $today->copy()->subDays(35)->toDateString());

        // ── Zineb Moussaoui [10] ─ PAYÉ ──────────────────────────────
        $makeConsult($ps[10], $today->copy()->subDays(25)->toDateString(), '10:00', '11:00', 'TERMINE', 'TERMINE', [
            ['act' => $EXT2, 'teeth' => [28], 'price' => 600, 'note' => 'Extraction chirurgicale 28 — gants sans latex obligatoire'],
        ], null, 'Protocole allergie latex strict');
        $pay($ps[10], 600, $today->copy()->subDays(23)->toDateString(), 'Règlement extraction chirurgicale');

        // ── Karim Sebti [11] ─ BROUILLON ─────────────────────────────
        $makeConsult($ps[11], $today->copy()->subDays(2)->toDateString(), '14:00', '14:30', 'TERMINE', 'BROUILLON', [
            ['act' => $CONS, 'teeth' => [], 'price' => 150],
        ], null, 'Bilan initial — plan de traitement à définir');

        // ── Laila Boujemaa [12] ─ PAYÉ ───────────────────────────────
        $makeConsult($ps[12], $today->copy()->subDays(55)->toDateString(), '09:00', '10:00', 'TERMINE', 'TERMINE', [
            ['act' => $BLA, 'teeth' => [], 'price' => 1500],
        ]);
        $makeConsult($ps[12], $today->copy()->subDays(10)->toDateString(), '09:00', '09:30', 'TERMINE', 'TERMINE', [
            ['act' => $DET, 'teeth' => [], 'price' => 400],
        ]);
        $pay($ps[12], 1900, $today->copy()->subDays(50)->toDateString(), 'Règlement global blanchiment + détartrage');

        // ── Adil Kettani [13] ─ PARTIEL important ────────────────────
        $makeConsult($ps[13], $today->copy()->subDays(100)->toDateString(), '11:00', '12:00', 'TERMINE', 'TERMINE', [
            ['act' => $CAN, 'teeth' => [36], 'price' => 1200],
        ], null, 'INR vérifié avant intervention');
        $makeConsult($ps[13], $today->copy()->subDays(70)->toDateString(), '11:00', '12:00', 'TERMINE', 'TERMINE', [
            ['act' => $COU, 'teeth' => [36], 'price' => 2500],
        ]);
        $makeConsult($ps[13], $today->copy()->subDays(40)->toDateString(), '11:00', '11:30', 'TERMINE', 'TERMINE', [
            ['act' => $EXT1, 'teeth' => [48], 'price' => 300],
        ], null, 'INR < 2.5 validé');
        // Total 4000, payé 1800 → PARTIEL
        $pay($ps[13], 1000, $today->copy()->subDays(95)->toDateString());
        $pay($ps[13], 800,  $today->copy()->subDays(65)->toDateString());

        // ── Meryem Qabbaj [14] ─ PAYÉ (première consultation) ────────
        $makeConsult($ps[14], $today->copy()->subDays(12)->toDateString(), '09:30', '10:00', 'TERMINE', 'TERMINE', [
            ['act' => $CONS, 'teeth' => [], 'price' => 150],
            ['act' => $RAD2, 'teeth' => [], 'price' => 250],
        ], null, 'Premier bilan dentaire — très bonne hygiène bucco-dentaire');
        $pay($ps[14], 400, $today->copy()->subDays(10)->toDateString());

        // ── Amine Tazi [15] ─ PARTIEL ────────────────────────────────
        $makeConsult($ps[15], $today->copy()->subDays(30)->toDateString(), '11:00', '12:00', 'TERMINE', 'TERMINE', [
            ['act' => $CAN, 'teeth' => [26], 'price' => 1200],
            ['act' => $COU, 'teeth' => [26], 'price' => 2500],
        ]);
        $pay($ps[15], 2000, $today->copy()->subDays(27)->toDateString(), 'Acompte canal + couronne 26');

        // ── Samira Chorfi [16] ─ PAYÉ ─────────────────────────────────
        $makeConsult($ps[16], $today->copy()->subDays(80)->toDateString(), '14:00', '14:30', 'TERMINE', 'TERMINE', [
            ['act' => $DET, 'teeth' => [], 'price' => 400],
            ['act' => $OBT, 'teeth' => [37], 'price' => 350],
        ]);
        $pay($ps[16], 750, $today->copy()->subDays(78)->toDateString());

        // ── Idriss Hamdaoui [17] ─ NO_SHOW + RDV futur ────────────────
        $makeConsult($ps[17], $today->copy()->subDays(5)->toDateString(), '16:00', '16:30', 'NO_SHOW', null, [], 'N\'a pas appelé pour annuler');
        // Incrémenter no_show_count
        $ps[17]->increment('no_show_count');
        // Nouveau RDV planifié
        $makeConsult($ps[17], $today->copy()->addDays(3)->toDateString(), '16:00', '16:30', 'PLANIFIE', null, [], 'Second rendez-vous — confirmer la veille');

        // ── Nour Filali [18] ─ EN_COURS ─────────────────────────────
        $makeConsult($ps[18], $today->copy()->subDays(8)->toDateString(), '09:00', '09:30', 'TERMINE', 'TERMINE', [
            ['act' => $CONS, 'teeth' => [], 'price' => 150],
            ['act' => $RAD1, 'teeth' => [46], 'price' => 100],
        ]);
        $makeConsult($ps[18], $today->copy()->subDays(3)->toDateString(), '09:00', '10:00', 'TERMINE', 'EN_COURS', [
            ['act' => $CAN, 'teeth' => [46], 'price' => 1200, 'note' => 'Séance 1/2 — médicament intracanalaire en place'],
        ], null, 'Canal 46 en cours — RDV dans 10 jours');
        $pay($ps[18], 250, $today->copy()->subDays(6)->toDateString(), 'Consultation + radio');
        $makeConsult($ps[18], $today->copy()->addDays(10)->toDateString(), '09:00', '10:00', 'PLANIFIE', null, [], 'Obturation canal 46');

        // ── Khalid Amrani [19] ─ PARTIEL, profil à risque ─────────────
        $makeConsult($ps[19], $today->copy()->subDays(110)->toDateString(), '10:00', '11:00', 'TERMINE', 'TERMINE', [
            ['act' => $EXT1, 'teeth' => [38], 'price' => 300, 'note' => 'Sans vasoconstricteur'],
            ['act' => $RAD1, 'teeth' => [38], 'price' => 100],
        ], null, 'Protocole HTA : anesthésie sans épinéphrine');
        $makeConsult($ps[19], $today->copy()->subDays(85)->toDateString(), '10:00', '10:30', 'TERMINE', 'TERMINE', [
            ['act' => $DET, 'teeth' => [], 'price' => 400],
        ]);
        $makeConsult($ps[19], $today->copy()->subDays(40)->toDateString(), '10:00', '11:00', 'TERMINE', 'TERMINE', [
            ['act' => $CAN, 'teeth' => [37], 'price' => 1200],
        ]);
        // Total 2000, payé 1200 → PARTIEL
        $pay($ps[19], 400, $today->copy()->subDays(105)->toDateString());
        $pay($ps[19], 400, $today->copy()->subDays(80)->toDateString());
        $pay($ps[19], 400, $today->copy()->subDays(35)->toDateString());

        // ── Imane Boutaleb [20] ─ PAYÉ ───────────────────────────────
        $makeConsult($ps[20], $today->copy()->subDays(18)->toDateString(), '09:00', '09:30', 'TERMINE', 'TERMINE', [
            ['act' => $DET, 'teeth' => [], 'price' => 400],
        ]);
        $makeConsult($ps[20], $today->copy()->subDays(5)->toDateString(), '09:00', '09:30', 'TERMINE', 'TERMINE', [
            ['act' => $OBT, 'teeth' => [14], 'price' => 350],
        ]);
        $pay($ps[20], 750, $today->copy()->subDays(15)->toDateString(), 'Règlement total');

        // ── Tariq Mansouri [21] ─ NO_SHOW ─────────────────────────────
        $makeConsult($ps[21], $today->copy()->subDays(3)->toDateString(), '10:00', '10:30', 'NO_SHOW', null, [], 'Patient injoignable');
        $ps[21]->increment('no_show_count');
        $makeConsult($ps[21], $today->copy()->subDays(30)->toDateString(), '10:00', '10:30', 'NO_SHOW', null, [], 'Deuxième absence non signalée');
        $ps[21]->increment('no_show_count');

        // ── Souad Bekkali [22] ─ PAYÉ ────────────────────────────────
        $makeConsult($ps[22], $today->copy()->subDays(70)->toDateString(), '10:00', '11:00', 'TERMINE', 'TERMINE', [
            ['act' => $EXT2, 'teeth' => [18], 'price' => 600, 'note' => 'Dent de sagesse incluse'],
        ]);
        $pay($ps[22], 600, $today->copy()->subDays(68)->toDateString());

        // ── Bilal Errachidi [23] ─ PARTIEL (premier suivi) ────────────
        $makeConsult($ps[23], $today->copy()->subDays(6)->toDateString(), '14:00', '14:30', 'TERMINE', 'TERMINE', [
            ['act' => $CONS, 'teeth' => [], 'price' => 150],
            ['act' => $DET,  'teeth' => [], 'price' => 400],
        ], null, 'Première visite — hygiène à améliorer');
        $pay($ps[23], 200, $today->copy()->subDays(4)->toDateString(), 'Acompte première consultation');

        // ── Hasnaa Benkirane [24] ─ PAYÉ ─────────────────────────────
        $makeConsult($ps[24], $today->copy()->subDays(22)->toDateString(), '09:00', '10:00', 'TERMINE', 'TERMINE', [
            ['act' => $BLA, 'teeth' => [], 'price' => 1500],
        ]);
        $pay($ps[24], 1500, $today->copy()->subDays(20)->toDateString());

        // ── Ayoub Chraibi [25] ─ RDV aujourd'hui (PLANIFIE) ──────────
        $makeConsult($ps[25], $today->toDateString(), '14:00', '14:30', 'PLANIFIE', null, [], 'Contrôle post-extraction');
        // Historique : extraction il y a 3 semaines
        $makeConsult($ps[25], $today->copy()->subDays(21)->toDateString(), '14:00', '14:30', 'TERMINE', 'TERMINE', [
            ['act' => $EXT1, 'teeth' => [34], 'price' => 300],
        ]);
        $pay($ps[25], 300, $today->copy()->subDays(19)->toDateString());

        // ── Widad Saadouni [26] ─ PAYÉ ────────────────────────────────
        $makeConsult($ps[26], $today->copy()->subDays(95)->toDateString(), '10:00', '11:00', 'TERMINE', 'TERMINE', [
            ['act' => $CAN, 'teeth' => [16], 'price' => 1200],
        ]);
        $makeConsult($ps[26], $today->copy()->subDays(65)->toDateString(), '10:00', '11:00', 'TERMINE', 'TERMINE', [
            ['act' => $COU, 'teeth' => [16], 'price' => 2500],
        ]);
        $pay($ps[26], 3700, $today->copy()->subDays(60)->toDateString(), 'Paiement intégral canal + couronne 16');

        // ── Mehdi Benbrahim [27] ─ PARTIEL ────────────────────────────
        $makeConsult($ps[27], $today->copy()->subDays(50)->toDateString(), '15:00', '15:30', 'TERMINE', 'TERMINE', [
            ['act' => $DET,  'teeth' => [], 'price' => 400],
            ['act' => $OBT,  'teeth' => [45], 'price' => 350],
            ['act' => $RAD1, 'teeth' => [45], 'price' => 100],
        ]);
        $pay($ps[27], 500, $today->copy()->subDays(48)->toDateString());

        // ── Ghizlane Lahlou [28] ─ AVANCE ─────────────────────────────
        $makeConsult($ps[28], $today->copy()->subDays(15)->toDateString(), '09:00', '09:30', 'TERMINE', 'TERMINE', [
            ['act' => $CONS, 'teeth' => [], 'price' => 150],
        ]);
        $pay($ps[28], 500, $today->copy()->subDays(13)->toDateString(), 'Paiement en avance pour prochains soins');

        // ── Driss Benchekroun [29] ─ PARTIEL important ────────────────
        $makeConsult($ps[29], $today->copy()->subDays(130)->toDateString(), '11:00', '12:00', 'TERMINE', 'TERMINE', [
            ['act' => $CAN, 'teeth' => [46], 'price' => 1200],
        ], null, 'Antibioprophylaxie : Amoxicilline 2g 1h avant');
        $makeConsult($ps[29], $today->copy()->subDays(100)->toDateString(), '11:00', '12:00', 'TERMINE', 'TERMINE', [
            ['act' => $COU, 'teeth' => [46], 'price' => 2500],
        ]);
        $makeConsult($ps[29], $today->copy()->subDays(60)->toDateString(), '11:00', '11:30', 'TERMINE', 'TERMINE', [
            ['act' => $DET, 'teeth' => [], 'price' => 400],
        ]);
        $makeConsult($ps[29], $today->copy()->subDays(20)->toDateString(), '11:00', '12:00', 'TERMINE', 'TERMINE', [
            ['act' => $CAN, 'teeth' => [26], 'price' => 1200],
        ], null, 'Antibioprophylaxie administrée');
        // Total 5300, payé 3000 → PARTIEL
        $pay($ps[29], 2000, $today->copy()->subDays(125)->toDateString(), 'Chèque canal + couronne 46');
        $pay($ps[29], 1000, $today->copy()->subDays(55)->toDateString(), 'Versement détartrage');
        // RDV futur : pose couronne 26
        $makeConsult($ps[29], $today->copy()->addDays(14)->toDateString(), '11:00', '12:00', 'PLANIFIE', null, [], 'Pose couronne 26 — antibioprophylaxie à prévoir');

        // ── Nouveaux patients (30-32) : RDV futurs seulement ──────────
        $makeConsult($ps[30], $today->copy()->addDays(2)->toDateString(), '09:00', '09:30', 'PLANIFIE', null, [], 'Premier rendez-vous');
        $makeConsult($ps[31], $today->copy()->addDays(4)->toDateString(), '10:00', '10:30', 'PLANIFIE', null, [], 'Douleur signalée à droite haut');
        $makeConsult($ps[32], $today->copy()->addDays(6)->toDateString(), '14:00', '15:00', 'PLANIFIE', null, [], 'Bilan complet + panoramique');

        // ── RDV d'aujourd'hui supplémentaires ────────────────────────
        $makeConsult($ps[14], $today->toDateString(), '09:30', '10:00', 'PLANIFIE', null, [], 'Suite plan de traitement');
        $makeConsult($ps[20], $today->toDateString(), '11:00', '11:30', 'PLANIFIE', null, [], 'Contrôle obturation 14');
        $makeConsult($ps[26], $today->toDateString(), '15:00', '15:30', 'PLANIFIE', null, [], 'Contrôle couronne 16');
        $makeConsult($ps[29], $today->toDateString(), '16:00', '16:30', 'PLANIFIE', null, [], 'Séance canal 26 — séance 2');

        $this->command->info('✓ ' . count($patientsData) . ' patients créés (dont 2 archivés)');
        $this->command->info('✓ RDV, consultations, actes et paiements générés');
        $this->command->info('');
        $this->command->info('  Connexion : dentiste@demo.com / password');
        $this->command->info('');
        $this->command->info('  Scénarios couverts :');
        $this->command->info('  - PAYÉ        : Fatima, Khadija, Sanaa, Laila, Meryem, Samira, Imane, Souad, Hasnaa, Widad, Ayoub, Ghizlane*');
        $this->command->info('  - PARTIEL     : Mohamed, Nadia, Omar (0 payé), Hamza, Adil, Amine, Idriss*, Bilal, Mehdi, Khalid, Driss');
        $this->command->info('  - AVANCE      : Yassine, Houda, Ghizlane');
        $this->command->info('  - EN_COURS    : Mohamed, Rachid, Nour');
        $this->command->info('  - BROUILLON   : Karim');
        $this->command->info('  - NO_SHOW     : Idriss (x1), Tariq (x2)');
        $this->command->info('  - Alertes     : Fatima, Mohamed, Rachid, Zineb, Adil, Khalid, Houda, Driss');
        $this->command->info('  - Archivés    : Yousra Bennis, Hassan Oulhaj');
        $this->command->info('  - Nouveaux    : Rania, Otmane, Loubna (RDV futurs uniquement)');
    }
}
