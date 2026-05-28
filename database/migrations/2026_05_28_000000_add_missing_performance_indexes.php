<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->index('created_at',            'idx_consult_created_at');
            $table->index(['created_at', 'status'], 'idx_consult_created_at_status');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index('created_by', 'idx_appt_created_by');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropIndex('idx_consult_created_at');
            $table->dropIndex('idx_consult_created_at_status');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appt_created_by');
        });
    }
};
