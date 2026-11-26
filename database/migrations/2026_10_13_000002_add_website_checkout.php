<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('website_checkouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('website_registration_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->json('plan_snapshot');
            $table->timestamp('initial_payment_confirmed_at');
            $table->string('status', 30)->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('website_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('website_checkout_id')->constrained()->restrictOnDelete();
            $table->string('gateway', 30);
            $table->string('environment', 10);
            $table->string('status', 30)->default('creating');
            $table->string('remote_id')->nullable();
            $table->string('payment_reference')->nullable()->index();
            $table->text('checkout_url')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('capture_started_at')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'environment', 'remote_id'], 'website_attempt_provider_reference');
        });
        Schema::create('website_payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 30);
            $table->string('event_id');
            $table->foreignId('website_payment_attempt_id')->constrained()->restrictOnDelete();
            $table->timestamp('processed_at');
            $table->unique(['gateway', 'event_id']);
        });
    }

    public function down(): void
    {
        // Forward-only: preserve payment and reconciliation records.
    }
};
