<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // ─── Relations ────────────────────────────────────────
            $table->foreignId('patient_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('consultation_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // ─── Informations facture ─────────────────────────────
            $table->string('label');              // ex: "Détartrage + Radio"
            $table->decimal('total_amount', 10, 2);    // montant total
            $table->decimal('paid_amount', 10, 2)->default(0);      // somme versements
            $table->decimal('remaining_amount', 10, 2)->default(0); // total - paid

            // ─── Statut ───────────────────────────────────────────
            // EN_ATTENTE : aucun versement
            // PARTIEL    : versement(s) mais pas soldé
            // PAYÉ       : soldé complètement
            // AVANCE     : paiement sans consultation liée
            $table->enum('status', ['EN_ATTENTE', 'PARTIEL', 'PAYÉ', 'AVANCE'])
                  ->default('EN_ATTENTE');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};