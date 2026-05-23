<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── appointments ──────────────────────────────────────────────
        // Filtrée par date à chaque chargement de l'agenda
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('scheduled_date',              'idx_appt_date');
            $table->index('status',                      'idx_appt_status');
            $table->index(['scheduled_date', 'status'],  'idx_appt_date_status');
        });

        // ── consultations ─────────────────────────────────────────────
        // patient_id déjà indexé via FK
        Schema::table('consultations', function (Blueprint $table) {
            $table->index('status',                    'idx_consult_status');
            $table->index(['patient_id', 'status'],    'idx_consult_patient_status');
        });

        // ── payment_transactions ──────────────────────────────────────
        // patient_id déjà indexé via FK
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->index('date',                  'idx_tx_date');
            $table->index(['patient_id', 'date'],  'idx_tx_patient_date');
        });

        // ── patients ──────────────────────────────────────────────────
        // scope active() filtre is_archived à chaque requête liste
        Schema::table('patients', function (Blueprint $table) {
            $table->index('is_archived',                   'idx_patient_archived');
            $table->index(['is_archived', 'status'],       'idx_patient_archived_status');
        });

        // ── medical_alerts ────────────────────────────────────────────
        // severity utilisée dans withCount criticalAlerts
        Schema::table('medical_alerts', function (Blueprint $table) {
            $table->index(['patient_id', 'severity'], 'idx_alert_patient_severity');
        });
    }

    public function down(): void
    {
        Schema::table('appointments',        fn($t) => $t->dropIndex(['idx_appt_date', 'idx_appt_status', 'idx_appt_date_status']));
        Schema::table('consultations',       fn($t) => $t->dropIndex(['idx_consult_status', 'idx_consult_patient_status']));
        Schema::table('payment_transactions',fn($t) => $t->dropIndex(['idx_tx_date', 'idx_tx_patient_date']));
        Schema::table('patients',            fn($t) => $t->dropIndex(['idx_patient_archived', 'idx_patient_archived_status']));
        Schema::table('medical_alerts',      fn($t) => $t->dropIndex(['idx_alert_patient_severity']));
    }
};
