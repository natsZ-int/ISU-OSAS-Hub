<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollment_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->string('academic_year', 20);
            $table->string('semester', 20);
            $table->string('status', 30)->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_profile_id', 'academic_year', 'semester'], 'sev_profile_year_semester_idx');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollment_verifications');
    }
};
