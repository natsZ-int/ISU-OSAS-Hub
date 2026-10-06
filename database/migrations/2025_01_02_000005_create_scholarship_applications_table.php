<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 30)->unique();
            $table->foreignId('student_profile_id')
                  ->constrained('student_profiles')
                  ->restrictOnDelete();
            $table->foreignId('scholarship_id')
                  ->constrained('scholarships')
                  ->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('student_profile_id');
            $table->index('scholarship_id');
            $table->index('status');
            $table->index('submitted_at');

            // Prevent duplicate applications per student per scholarship.
            // Limitation: this blocks ALL duplicates including withdrawn/rejected.
            // Future business logic may need to relax this at the application layer.
            $table->unique(['student_profile_id', 'scholarship_id'], 'uniq_student_scholarship');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_applications');
    }
};