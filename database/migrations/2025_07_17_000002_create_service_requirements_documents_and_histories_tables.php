<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Safely extend service_types
        if (Schema::hasTable('service_types')) {
            Schema::table('service_types', function (Blueprint $table) {
                if (!Schema::hasColumn('service_types', 'instructions')) {
                    $table->text('instructions')->nullable()->after('description');
                }
                if (!Schema::hasColumn('service_types', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('instructions');
                }
                if (!Schema::hasColumn('service_types', 'display_order')) {
                    $table->integer('display_order')->default(0)->after('is_active');
                }
            });
        }

        // 2. Safely create service_type_requirements
        if (!Schema::hasTable('service_type_requirements')) {
            Schema::create('service_type_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_type_id')
                      ->constrained('service_types')
                      ->cascadeOnDelete();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->boolean('is_required')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->index('service_type_id');
            });
        }

        // 3. Create service_request_documents with short constraint names
        if (!Schema::hasTable('service_request_documents')) {
            Schema::create('service_request_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_service_request_id');
                $table->foreignId('service_type_requirement_id')->nullable();
                $table->string('file_path');
                $table->string('original_filename');
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->foreign('student_service_request_id', 'fk_srd_ssr_id')
                      ->references('id')
                      ->on('student_service_requests')
                      ->cascadeOnDelete();

                $table->foreign('service_type_requirement_id', 'fk_srd_str_id')
                      ->references('id')
                      ->on('service_type_requirements')
                      ->nullOnDelete();

                $table->index('student_service_request_id', 'idx_srd_ssr_id');
                $table->index('service_type_requirement_id', 'idx_srd_str_id');
            });
        }

        // 4. Create student_service_request_histories with short constraint names
        if (!Schema::hasTable('student_service_request_histories')) {
            Schema::create('student_service_request_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_service_request_id');
                $table->string('previous_status', 30)->nullable();
                $table->string('new_status', 30);
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->foreign('student_service_request_id', 'fk_ssrh_ssr_id')
                      ->references('id')
                      ->on('student_service_requests')
                      ->cascadeOnDelete();

                $table->index('student_service_request_id', 'idx_ssrh_ssr_id');
                $table->index('new_status', 'idx_ssrh_new_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_service_request_histories');
        Schema::dropIfExists('service_request_documents');
        Schema::dropIfExists('service_type_requirements');

        if (Schema::hasTable('service_types')) {
            Schema::table('service_types', function (Blueprint $table) {
                if (Schema::hasColumn('service_types', 'display_order')) {
                    $table->dropColumn('display_order');
                }
                if (Schema::hasColumn('service_types', 'is_active')) {
                    $table->dropColumn('is_active');
                }
                if (Schema::hasColumn('service_types', 'instructions')) {
                    $table->dropColumn('instructions');
                }
            });
        }
    }
};