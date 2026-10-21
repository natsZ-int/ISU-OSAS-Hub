<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_matching_weights', function (Blueprint $table) {
            $table->id();
            $table->decimal('academic_weight', 5, 2)->default(30.00);
            $table->decimal('financial_weight', 5, 2)->default(25.00);
            $table->decimal('program_weight', 5, 2)->default(20.00);
            $table->decimal('year_level_weight', 5, 2)->default(15.00);
            $table->decimal('document_readiness_weight', 5, 2)->default(10.00);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_matching_weights');
    }
};
