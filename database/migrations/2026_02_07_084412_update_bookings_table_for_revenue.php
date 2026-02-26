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
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->after('booking_type')->default(0);
            $table->decimal('total_amount', 12, 2)->after('subtotal')->default(0);
            $table->string('currency', 3)->after('total_amount')->default('THB');
            $table->enum('payment_status', ['unpaid', 'paid', 'cancelled'])->after('currency')->default('unpaid');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            
            // Update source to include direct/partner
            // Note: We'll change the type to string to be more flexible, or use change() if supported.
            // For dev ease, we'll just define it as string with default 'direct'.
            $table->string('source')->default('direct')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'total_amount', 'currency', 'payment_status', 'paid_at']);
            // Reverting source is tricky with SQLite/some DBs, so we'll leave it as string.
        });
    }
};
