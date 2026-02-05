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
        Schema::dropIfExists('bookings'); // Reguard against previous state

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->enum('booking_type', ['room', 'tour', 'package']);
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->string('full_name');
            $table->string('whatsapp');
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->enum('source', ['website', 'bookingcom', 'walkin'])->default('website');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
