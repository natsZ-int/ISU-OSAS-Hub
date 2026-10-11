<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guidance_appointments', function (Blueprint $table) {
            $table->index(
                ['counselor_id', 'appointment_date', 'status'],
                'idx_counselor_appointments_dashboard'
            );
        });

        Schema::table('counselor_availabilities', function (Blueprint $table) {
            $table->index(
                ['counselor_id', 'is_available'],
                'idx_counselor_availabilities_dashboard'
            );
        });
    }

    public function down(): void
    {
        Schema::table('guidance_appointments', function (Blueprint $table) {
            $table->dropIndex('idx_counselor_appointments_dashboard');
        });

        Schema::table('counselor_availabilities', function (Blueprint $table) {
            $table->dropIndex('idx_counselor_availabilities_dashboard');
        });
    }
};