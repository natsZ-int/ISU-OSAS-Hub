<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('student_number', 20)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('college', 150)->nullable();
            $table->string('program', 150)->nullable();
            $table->tinyInteger('year_level')->unsigned();
            $table->decimal('gwa', 4, 2)->nullable();
            $table->decimal('family_income', 12, 2)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();

            $table->index('college');
            $table->index('program');
            $table->index('year_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
