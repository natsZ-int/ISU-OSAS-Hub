<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guidance_appointment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidance_appointment_types');
    }
};
