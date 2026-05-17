<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_acts', function (Blueprint $table) {
            $table->id();

            // ─── Lien vers le RDV ─────────────────────────────────
            $table->foreignId('appointment_id')
                  ->constrained('appointments')
                  ->onDelete('cascade'); // Si RDV supprimé → actes supprimés

            // ─── Lien vers l'acte du catalogue ────────────────────
            // On prévoit QUELS actes seront faits, sans préciser la dent
            // La dent sera précisée lors de la consultation réelle
            $table->foreignId('catalog_act_id')
                  ->constrained('catalog_acts')
                  ->onDelete('cascade');

            // ─── Empêche d'ajouter le même acte deux fois ─────────
            $table->unique(['appointment_id', 'catalog_act_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_acts');
    }
};