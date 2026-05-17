<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_acts', function (Blueprint $table) {
            $table->id();

            // ─── Relations ────────────────────────────────────────────
            $table->foreignId('consultation_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('catalog_act_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // ─── Dents concernées (optionnel) ─────────────────────────
            // [] = acte global (détartrage, radio panoramique…)
            // [46] = une dent, [11, 12] = plusieurs dents
            // Numérotation ISO/FDI standard
            $table->json('teeth')->nullable();

            // ─── Prix réel (pré-rempli depuis catalog_acts.base_price) ─
            // Modifiable par le dentiste
            $table->decimal('price', 10, 2)->default(0);

            // ─── Note spécifique à cet acte ───────────────────────────
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_acts');
    }
};