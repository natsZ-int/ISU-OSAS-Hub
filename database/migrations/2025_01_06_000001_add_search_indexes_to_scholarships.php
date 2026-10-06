<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes to support common student search/filter/sort patterns:
     *  - Filtering by scholarship_type (equality)
     *  - Filtering by provider (equality/prefix)
     *  - Sorting by created_at (newest sort)
     *  - Sorting by title (name sort with tie-breaker)
     *
     * Note: Substring '%keyword%' LIKE queries on title/description/provider
     * cannot fully use B-tree indexes because the leading wildcard prevents
     * index seeks. These indexes primarily help exact-match filters and
     * ORDER BY clauses.
     */
    public function up(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->index('scholarship_type', 'scholarships_type_idx');
            $table->index('provider', 'scholarships_provider_idx');
            $table->index('created_at', 'scholarships_created_at_idx');
            $table->index('title', 'scholarships_title_idx');
        });
    }

    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->dropIndex('scholarships_type_idx');
            $table->dropIndex('scholarships_provider_idx');
            $table->dropIndex('scholarships_created_at_idx');
            $table->dropIndex('scholarships_title_idx');
        });
    }
};