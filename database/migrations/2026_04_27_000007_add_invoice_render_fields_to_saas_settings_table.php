<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->string('invoice_number_style')->default('prefixed_period_sequence')->after('invoice_language');
            $table->string('invoice_tax_number_value')->nullable()->after('invoice_show_tax_message');
            $table->text('invoice_tax_message_text')->nullable()->after('invoice_tax_number_value');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'invoice_number_style',
                'invoice_tax_number_value',
                'invoice_tax_message_text',
            ]);
        });
    }
};
