<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (!Schema::hasColumn('bookings', 'guest_confirmation_sent_at')) {
                $table->timestamp('guest_confirmation_sent_at')->nullable()->after('admin_notification_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('bookings', 'guest_confirmation_sent_at')) {
                $table->dropColumn('guest_confirmation_sent_at');
            }
        });
    }
};

