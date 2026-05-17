<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprime EN_COURS et ANNULE de l'enum
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status 
        ENUM('PLANIFIE','CONFIRME','TERMINE','NO_SHOW') 
        DEFAULT 'PLANIFIE'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status 
        ENUM('PLANIFIE','CONFIRME','EN_COURS','TERMINE','ANNULE','NO_SHOW') 
        DEFAULT 'PLANIFIE'");
    }
};
