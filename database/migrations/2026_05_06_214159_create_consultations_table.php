<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();

            // ─── Relations ────────────────────────────────────────────
            $table->foreignId('patient_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('appointment_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete(); // Si le RDV est supprimé, la consultation reste

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // ─── Statut ───────────────────────────────────────────────
            $table->enum('status', ['BROUILLON', 'EN_COURS', 'TERMINE'])
                  ->default('BROUILLON');

            // ─── Contenu ──────────────────────────────────────────────
            $table->text('notes')->nullable();

            // ─── Prix total (calculé = somme des consultation_acts) ───
            $table->decimal('total_price', 10, 2)->default(0);

            // ─── Dates des séances (JSON) ─────────────────────────────
            // Ex: ["2026-05-01", "2026-05-08", "2026-05-15"]
            // La première date = date de création de la consultation
            $table->json('session_dates')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};