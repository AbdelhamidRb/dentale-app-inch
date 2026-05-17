<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_alerts', function (Blueprint $table) {
            $table->id();

            // Lien vers le patient
            $table->foreignId('patient_id')
                  ->constrained('patients')
                  ->onDelete('cascade'); // Si patient supprimé → alertes supprimées

            // Type d'alerte
            $table->enum('type', ['ALLERGIE', 'MALADIE', 'MEDICATION']);

            // Description libre (ex: "Allergie à la pénicilline")
            $table->text('description');

            // Niveau de gravité
            $table->enum('severity', ['ROUGE', 'ORANGE', 'JAUNE'])->default('ORANGE');

            // Qui a créé l'alerte
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_alerts');
    }
};