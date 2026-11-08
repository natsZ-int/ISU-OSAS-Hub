<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table) {
            $table->index('status', 'idx_sa_status');
            $table->index('submitted_at', 'idx_sa_submitted_at');
            $table->index(['scholarship_id', 'status'], 'idx_sa_scholarship_status');
        });

        Schema::table('guidance_appointments', function (Blueprint $table) {
            $table->index('status', 'idx_ga_status');
            $table->index('appointment_date', 'idx_ga_appointment_date');
            $table->index('appointment_type_id', 'idx_ga_type');
            $table->index('counselor_id', 'idx_ga_counselor');
        });

        Schema::table('student_service_requests', function (Blueprint $table) {
            $table->index('status', 'idx_ssr_status');
            $table->index('submitted_at', 'idx_ssr_submitted_at');
            $table->index('completed_at', 'idx_ssr_completed_at');
            $table->index('service_type_id', 'idx_ssr_service_type');
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table) {
            $table->dropIndex('idx_sa_status');
            $table->dropIndex('idx_sa_submitted_at');
            $table->dropIndex('idx_sa_scholarship_status');
        });

        Schema::table('guidance_appointments', function (Blueprint $table) {
            $table->dropIndex('idx_ga_status');
            $table->dropIndex('idx_ga_appointment_date');
            $table->dropIndex('idx_ga_type');
            $table->dropIndex('idx_ga_counselor');
        });

        Schema::table('student_service_requests', function (Blueprint $table) {
            $table->dropIndex('idx_ssr_status');
            $table->dropIndex('idx_ssr_submitted_at');
            $table->dropIndex('idx_ssr_completed_at');
            $table->dropIndex('idx_ssr_service_type');
        });
    }
};
