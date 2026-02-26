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
        Schema::table('packages', function (Blueprint $table) {
            // Rename existing image_path to thumbnail_image_path for clarity
            $table->renameColumn('image_path', 'thumbnail_image_path');
        });

        Schema::table('packages', function (Blueprint $table) {
            // Add hero/cover image
            $table->string('hero_image_path')->nullable()->after('thumbnail_image_path');
            // Optional video for hero section
            $table->string('video_path')->nullable()->after('hero_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['hero_image_path', 'video_path']);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->renameColumn('thumbnail_image_path', 'image_path');
        });
    }
};
