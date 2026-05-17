<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_acts', function (Blueprint $table) {
            $table->id();

            // Code unique de l'acte (ex: DET001, EXT001)
            $table->string('code')->unique();

            // Nom de l'acte
            $table->string('name');

            // Prix de base en MAD
            $table->decimal('base_price', 10, 2)->default(0);

            // Durée estimée en minutes (30 par défaut)
            $table->integer('duration_minutes')->default(30);

            // Actif ou désactivé (on ne supprime pas un acte)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_acts');
    }
};