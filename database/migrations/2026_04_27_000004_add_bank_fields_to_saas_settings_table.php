<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->string('bank_account_name')->nullable()->after('address');
            $table->string('bank_name')->nullable()->after('bank_account_name');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_routing_number')->nullable()->after('bank_account_number');
            $table->string('bank_swift_code')->nullable()->after('bank_routing_number');
            $table->string('bank_ifsc_code')->nullable()->after('bank_swift_code');
            $table->string('bank_branch')->nullable()->after('bank_ifsc_code');
            $table->text('bank_payment_notes')->nullable()->after('bank_branch');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'bank_account_name',
                'bank_name',
                'bank_account_number',
                'bank_routing_number',
                'bank_swift_code',
                'bank_ifsc_code',
                'bank_branch',
                'bank_payment_notes',
            ]);
        });
    }
};
