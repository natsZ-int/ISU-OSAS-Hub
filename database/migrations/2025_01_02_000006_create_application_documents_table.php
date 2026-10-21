<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')
                ->constrained('scholarship_applications')
                ->cascadeOnDelete();
            $table->foreignId('scholarship_requirement_id')
                ->nullable()
                ->constrained('scholarship_requirements')
                ->nullOnDelete();
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('verification_status', 20)->default('pending');
            $table->text('verification_remarks')->nullable();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('scholarship_application_id');
            $table->index('scholarship_requirement_id');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
