<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counselor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counselor_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // 0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday,
            // 4=Thursday, 5=Friday, 6=Saturday
            $table->tinyInteger('day_of_week')->unsigned();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index('counselor_id');
            $table->index('day_of_week');
            $table->index(['counselor_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counselor_availabilities');
    }
};
