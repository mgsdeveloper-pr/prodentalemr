<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('paypal_enabled')->default(false)->after('stripe_webhook_secret');
            $table->string('paypal_environment')->default('sandbox')->after('paypal_enabled');
            $table->text('paypal_client_id')->nullable()->after('paypal_environment');
            $table->text('paypal_client_secret')->nullable()->after('paypal_client_id');
            $table->string('paypal_webhook_id')->nullable()->after('paypal_client_secret');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('paypal_order_id')->nullable()->after('stripe_payment_intent_id')->index();
            $table->text('paypal_approval_url')->nullable()->after('paypal_order_id');
            $table->string('paypal_capture_id')->nullable()->after('paypal_approval_url')->index();
            $table->string('paypal_order_status')->nullable()->after('paypal_capture_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'paypal_order_id',
                'paypal_approval_url',
                'paypal_capture_id',
                'paypal_order_status',
            ]);
        });

        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'paypal_enabled',
                'paypal_environment',
                'paypal_client_id',
                'paypal_client_secret',
                'paypal_webhook_id',
            ]);
        });
    }
};
