<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')
                  ->constrained('scholarship_applications')
                  ->cascadeOnDelete();
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->foreignId('changed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('scholarship_application_id');
            $table->index('new_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
    }
};