<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->onDelete('cascade');
            // Lien optionnel vers une consultation
            $table->unsignedBigInteger('consultation_id')->nullable();

            // Type de document
            $table->enum('type', ['RADIO', 'PHOTO', 'PDF', 'CONSENTEMENT', 'AUTRE'])
                ->default('AUTRE');

            // Nom original du fichier (ce que l'utilisateur voit)
            $table->string('original_name');

            // Nom stocké en base (UUID pour éviter les conflits)
            $table->string('stored_name');

            // Chemin dans storage/app/public/
            $table->string('path');

            // Taille en octets
            $table->integer('size');

            // Qui a uploadé
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_documents');
    }
};
