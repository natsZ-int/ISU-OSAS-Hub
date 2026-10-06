<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guidance_scheduling_settings', function (Blueprint $table) {
            $table->id();
            $table->time('office_start_time')->default('08:00:00');
            $table->time('office_end_time')->default('17:00:00');
            $table->unsignedInteger('default_appointment_duration_minutes')->default(30);
            $table->unsignedInteger('minimum_booking_notice_minutes')->default(60);
            $table->unsignedInteger('student_cancellation_notice_minutes')->default(60);
            $table->unsignedInteger('maximum_future_booking_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidance_scheduling_settings');
    }
};