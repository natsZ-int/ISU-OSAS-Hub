<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_service_requests')) {
            Schema::create('student_service_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_number', 30)->unique();
                $table->foreignId('student_profile_id')
                      ->constrained('student_profiles')
                      ->restrictOnDelete();
                $table->foreignId('service_type_id')
                      ->constrained('service_types')
                      ->restrictOnDelete();
                $table->text('description');
                $table->string('status', 30)->default('submitted');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->foreignId('cancelled_by')
                      ->nullable()
                      ->constrained('users')
                      ->nullOnDelete();
                $table->text('remarks')->nullable(); // Student-visible remarks
                $table->text('internal_remarks')->nullable(); // Admin/handler internal notes
                $table->foreignId('handled_by')
                      ->nullable()
                      ->constrained('users')
                      ->nullOnDelete();
                $table->timestamps();

                $table->index('student_profile_id');
                $table->index('service_type_id');
                $table->index('status');
                $table->index('submitted_at');
                $table->index('handled_by');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_service_requests');
    }
};