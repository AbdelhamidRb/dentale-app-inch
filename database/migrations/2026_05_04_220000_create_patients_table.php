<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            // Numéro de dossier unique généré automatiquement (PAT-0001)
            $table->string('numero_dossier')->unique();

            // Identité
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['M', 'F'])->nullable();

            // Couverture médicale
            $table->enum('couverture', ['CNSS', 'CNOPS', 'RAMED', 'ASSURANCE', 'AUCUNE'])
                  ->default('AUCUNE');

            // Notes générales (pas les notes cliniques)
            $table->text('notes')->nullable();

            // Gestion du statut
            $table->enum('status', ['ACTIF', 'INACTIF', 'DECEDE'])->default('ACTIF');
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();

            // Compteur no-show
            $table->integer('no_show_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};