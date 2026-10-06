<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            // Speed up type filter
            $table->index('scholarship_type', 'idx_scholarships_type');

            // Speed up "newest" sort
            $table->index('created_at', 'idx_scholarships_created_at');

            // Speed up the availableForStudents scope
            $table->index('application_start_date', 'idx_scholarships_start_date');

            // Combined index for status + date range queries
            $table->index(
                ['status', 'application_start_date', 'application_deadline'],
                'idx_scholarships_status_dates'
            );
        });
    }

    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->dropIndex('idx_scholarships_type');
            $table->dropIndex('idx_scholarships_created_at');
            $table->dropIndex('idx_scholarships_start_date');
            $table->dropIndex('idx_scholarships_status_dates');
        });
    }
};