<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // ─── Lien patient ─────────────────────────────────────
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->onDelete('cascade');

            // ─── Qui a créé le RDV (dentiste ou assistant) ────────
            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('cascade');

            // ─── Date et horaires ─────────────────────────────────
            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time'); // début + 30min par défaut, modifiable

            // ─── Statut du RDV ────────────────────────────────────
            // PLANIFIE  → créé mais pas encore confirmé
            // CONFIRME  → patient a confirmé sa présence
            // EN_COURS  → patient est dans le cabinet
            // TERMINE   → consultation effectuée
            // ANNULE    → annulé par le cabinet ou le patient
            // NO_SHOW   → patient absent sans prévenir
            $table->enum('status', [
                'PLANIFIE',
                'CONFIRME',
                'TERMINE',
                'NO_SHOW'
            ])->default('PLANIFIE');
            // ─── Notes optionnelles ───────────────────────────────
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
