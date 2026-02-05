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
        Schema::create('booking_room_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('guests_adults');
            $table->integer('guests_children');
            $table->timestamps();
        });

        Schema::create('booking_tour_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('activity_id')->constrained()->onDelete('cascade');
            $table->date('tour_date');
            $table->time('tour_time')->nullable();
            $table->integer('guests_adults');
            $table->integer('guests_children');
            $table->timestamps();
        });

        Schema::create('booking_package_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('guests_adults');
            $table->integer('guests_children');
            $table->timestamps();
        });

        Schema::create('booking_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->boolean('editable_by_admin')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_schedule_items');
        Schema::dropIfExists('booking_package_details');
        Schema::dropIfExists('booking_tour_details');
        Schema::dropIfExists('booking_room_details');
    }
};
