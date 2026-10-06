<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guidance_appointments', function (Blueprint $table) {
            // Add foreign key to appointment types
            $table->foreignId('appointment_type_id')
                  ->nullable()
                  ->after('counselor_id')
                  ->constrained('guidance_appointment_types')
                  ->nullOnDelete();

            // Add rejection reason
            $table->text('rejection_reason')->nullable()->after('status');

            // Add cancellation tracking
            $table->timestamp('cancelled_at')->nullable()->after('rejection_reason');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();

            // Change default status from 'pending' to 'requested'
            $table->string('status', 20)->default('requested')->change();

            // Add indexes for conflict-check queries
            $table->index(['counselor_id', 'appointment_date', 'status'], 'idx_appt_counselor_date_status');
            $table->index(['student_profile_id', 'appointment_date', 'status'], 'idx_appt_student_date_status');
            $table->index(['appointment_date', 'start_time', 'end_time'], 'idx_appt_date_times');
        });
    }

    public function down(): void
    {
        Schema::table('guidance_appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appt_counselor_date_status');
            $table->dropIndex('idx_appt_student_date_status');
            $table->dropIndex('idx_appt_date_times');
            $table->dropColumn(['appointment_type_id', 'rejection_reason', 'cancelled_at', 'cancelled_by']);
            $table->string('status', 20)->default('pending')->change();
        });
    }
};