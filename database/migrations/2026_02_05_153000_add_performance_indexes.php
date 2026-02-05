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
        Schema::table('booking_room_details', function (Blueprint $table) {
            $table->index('check_in');
            $table->index('check_out');
        });

        Schema::table('booking_package_details', function (Blueprint $table) {
            $table->index('check_in');
            $table->index('check_out');
        });

        Schema::table('booking_tour_details', function (Blueprint $table) {
            $table->index('tour_date');
        });

        Schema::table('booking_schedule_items', function (Blueprint $table) {
            $table->index('scheduled_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_room_details', function (Blueprint $table) {
            $table->dropIndex(['check_in']);
            $table->dropIndex(['check_out']);
        });

        Schema::table('booking_package_details', function (Blueprint $table) {
            $table->dropIndex(['check_in']);
            $table->dropIndex(['check_out']);
        });

        Schema::table('booking_tour_details', function (Blueprint $table) {
            $table->dropIndex(['tour_date']);
        });

        Schema::table('booking_schedule_items', function (Blueprint $table) {
            $table->dropIndex(['scheduled_date']);
        });
    }
};
