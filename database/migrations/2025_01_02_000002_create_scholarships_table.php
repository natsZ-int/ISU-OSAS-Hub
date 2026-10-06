<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description');
            $table->string('provider', 255);
            $table->string('scholarship_type', 100);
            $table->decimal('minimum_gwa', 4, 2)->nullable();
            $table->decimal('maximum_family_income', 12, 2)->nullable();
            $table->json('eligible_programs')->nullable();
            $table->json('eligible_year_levels')->nullable();
            $table->date('application_start_date');
            $table->date('application_deadline');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('application_deadline');
            $table->index(['status', 'application_deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};