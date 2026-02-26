<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('day_no')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('schedule_date')->nullable();
            $table->string('schedule_time')->nullable();
            $table->string('status')->default('planned');
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['booking_id', 'sort_order']);
            $table->index('schedule_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_schedules');
    }
};
