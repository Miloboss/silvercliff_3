<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Room Types table
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code_prefix', 10); // D, OC, B, F
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->decimal('base_price_thb', 12, 2)->default(1500);
            $table->unsignedTinyInteger('capacity_adults')->default(2);
            $table->unsignedTinyInteger('capacity_children')->default(0);
            $table->string('cover_image')->nullable(); // storage path
            $table->json('highlights')->nullable(); // [{icon, label}]
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Room Images table (gallery per room type)
        Schema::create('room_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade');
            $table->string('image_path');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        // 3. Amenities table
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique(); // wifi, ac, shower, river, etc.
            $table->string('icon_key')->default('🏠'); // emoji or heroicon name
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Pivot: room_type_amenities
        Schema::create('room_type_amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade');
            $table->foreignId('amenity_id')->constrained('amenities')->onDelete('cascade');
            $table->unique(['room_type_id', 'amenity_id']);
        });

        // 5. Add room_type_id to existing rooms table (nullable first for migration safety)
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('room_type_id')
                ->nullable()
                ->after('id')
                ->constrained('room_types')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['room_type_id']);
            $table->dropColumn('room_type_id');
        });
        Schema::dropIfExists('room_type_amenities');
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('room_images');
        Schema::dropIfExists('room_types');
    }
};
