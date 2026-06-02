<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->string('invoice_logo_path')->nullable()->after('bank_payment_notes');
            $table->string('invoice_signature_path')->nullable()->after('invoice_logo_path');
            $table->string('invoice_language')->default('en')->after('invoice_signature_path');
            $table->unsignedSmallInteger('invoice_due_after_days')->default(3)->after('invoice_language');
            $table->boolean('invoice_show_status')->default(true)->after('invoice_due_after_days');
            $table->boolean('invoice_show_tax_number')->default(false)->after('invoice_show_status');
            $table->boolean('invoice_show_tax_message')->default(false)->after('invoice_show_tax_number');
            $table->boolean('invoice_show_authorised_signatory')->default(false)->after('invoice_show_tax_message');
            $table->boolean('invoice_show_client_name')->default(true)->after('invoice_show_authorised_signatory');
            $table->boolean('invoice_show_client_email')->default(true)->after('invoice_show_client_name');
            $table->boolean('invoice_show_client_phone')->default(true)->after('invoice_show_client_email');
            $table->boolean('invoice_show_client_address')->default(true)->after('invoice_show_client_phone');
            $table->text('invoice_terms_conditions')->nullable()->after('invoice_show_client_address');
            $table->string('invoice_template_style')->default('modern')->after('invoice_terms_conditions');
            $table->boolean('invoice_show_payment_instructions')->default(true)->after('invoice_template_style');
            $table->boolean('invoice_show_invoice_notice')->default(true)->after('invoice_show_payment_instructions');
            $table->boolean('invoice_compact_layout')->default(true)->after('invoice_show_invoice_notice');
            $table->string('invoice_prefix')->default('INV')->after('invoice_compact_layout');
            $table->string('invoice_number_separator')->default('-')->after('invoice_prefix');
            $table->unsignedTinyInteger('invoice_number_digits')->default(4)->after('invoice_number_separator');
            $table->boolean('invoice_include_period_prefix')->default(true)->after('invoice_number_digits');
            $table->string('invoice_unit_label')->default('Qty')->after('invoice_include_period_prefix');
            $table->unsignedTinyInteger('invoice_quantity_precision')->default(2)->after('invoice_unit_label');
            $table->boolean('quickbooks_enabled')->default(false)->after('invoice_quantity_precision');
            $table->string('quickbooks_company_id')->nullable()->after('quickbooks_enabled');
            $table->string('quickbooks_client_id')->nullable()->after('quickbooks_company_id');
            $table->text('quickbooks_client_secret')->nullable()->after('quickbooks_client_id');
            $table->boolean('quickbooks_auto_sync')->default(false)->after('quickbooks_client_secret');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'invoice_logo_path',
                'invoice_signature_path',
                'invoice_language',
                'invoice_due_after_days',
                'invoice_show_status',
                'invoice_show_tax_number',
                'invoice_show_tax_message',
                'invoice_show_authorised_signatory',
                'invoice_show_client_name',
                'invoice_show_client_email',
                'invoice_show_client_phone',
                'invoice_show_client_address',
                'invoice_terms_conditions',
                'invoice_template_style',
                'invoice_show_payment_instructions',
                'invoice_show_invoice_notice',
                'invoice_compact_layout',
                'invoice_prefix',
                'invoice_number_separator',
                'invoice_number_digits',
                'invoice_include_period_prefix',
                'invoice_unit_label',
                'invoice_quantity_precision',
                'quickbooks_enabled',
                'quickbooks_company_id',
                'quickbooks_client_id',
                'quickbooks_client_secret',
                'quickbooks_auto_sync',
            ]);
        });
    }
};
