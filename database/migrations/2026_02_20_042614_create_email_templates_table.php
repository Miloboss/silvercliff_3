<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // booking_confirmation_guest, booking_confirmation_admin, voucher_pdf_email
            $table->string('name');          // Human readable label
            $table->string('subject_template');
            $table->string('header_title')->nullable();
            $table->string('header_tagline')->nullable();
            $table->text('body_intro');
            $table->text('policies_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->string('accent_color')->default('#1e3a1a');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_draft')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('email_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot'); // Full JSON of the template at that point
            $table->timestamp('saved_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_versions');
        Schema::dropIfExists('email_templates');
    }
};
