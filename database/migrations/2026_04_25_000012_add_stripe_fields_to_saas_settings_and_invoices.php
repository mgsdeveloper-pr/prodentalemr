<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('stripe_enabled')->default(false)->after('email_on_invoice_sent');
            $table->string('stripe_environment')->default('test')->after('stripe_enabled');
            $table->text('stripe_publishable_key')->nullable()->after('stripe_environment');
            $table->text('stripe_secret_key')->nullable()->after('stripe_publishable_key');
            $table->text('stripe_webhook_secret')->nullable()->after('stripe_secret_key');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('stripe_checkout_session_id')->nullable()->after('notes')->index();
            $table->text('stripe_checkout_url')->nullable()->after('stripe_checkout_session_id');
            $table->timestamp('stripe_checkout_expires_at')->nullable()->after('stripe_checkout_url');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_checkout_expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'stripe_checkout_session_id',
                'stripe_checkout_url',
                'stripe_checkout_expires_at',
                'stripe_payment_intent_id',
            ]);
        });

        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'stripe_enabled',
                'stripe_environment',
                'stripe_publishable_key',
                'stripe_secret_key',
                'stripe_webhook_secret',
            ]);
        });
    }
};
