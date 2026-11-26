<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('onboarding_drafts', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
        Schema::create('website_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_draft_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email', 254)->unique();
            $table->string('resume_hash', 64)->nullable()->unique();
            $table->timestamp('resume_expires_at')->nullable();
            $table->string('access_hash', 64)->nullable()->unique();
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('website_published')->default(false);
            $table->string('verification_model', 30)->default('self_service');
            $table->string('billing_interval', 20)->default('monthly');
            $table->string('currency', 3)->default('USD');
            $table->boolean('quote_required')->default(false);
            $table->text('website_description')->nullable();
            $table->unsignedInteger('website_sort_order')->default(0);
        });
    }

    public function down(): void
    {
        // Forward-only migration: registration records must not be discarded.
    }
};
