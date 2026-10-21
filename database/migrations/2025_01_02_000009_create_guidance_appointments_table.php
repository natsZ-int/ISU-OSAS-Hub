<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guidance_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')
                ->constrained('student_profiles')
                ->restrictOnDelete();
            $table->foreignId('counselor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('appointment_type', 100);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('counselor_notes')->nullable();
            $table->text('student_notes')->nullable();
            $table->timestamps();

            $table->index(['counselor_id', 'appointment_date']);
            $table->index(['student_profile_id', 'appointment_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidance_appointments');
    }
};
