<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['status', 'balance_due'], 'invoices_status_balance_due_idx');
            $table->index('created_at', 'invoices_created_at_idx');
            $table->index(['organization_id', 'created_at'], 'invoices_org_created_at_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index('payment_date', 'payments_payment_date_idx');
            $table->index('payment_method', 'payments_payment_method_idx');
            $table->index(['organization_id', 'payment_date'], 'payments_org_payment_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_org_payment_date_idx');
            $table->dropIndex('payments_payment_method_idx');
            $table->dropIndex('payments_payment_date_idx');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_org_created_at_idx');
            $table->dropIndex('invoices_created_at_idx');
            $table->dropIndex('invoices_status_balance_due_idx');
        });
    }
};
