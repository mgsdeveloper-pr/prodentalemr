<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_plan_items', function (Blueprint $table): void {
            $table->foreignId('clinic_service_id')->nullable()->after('service_item_id')->constrained('clinic_services')->nullOnDelete();
        });

        Schema::table('dental_chart_entries', function (Blueprint $table): void {
            $table->foreignId('clinic_service_id')->nullable()->after('service_item_id')->constrained('clinic_services')->nullOnDelete();
        });

        Schema::table('patient_ledger_entries', function (Blueprint $table): void {
            $table->foreignId('clinic_service_id')->nullable()->after('service_item_id')->constrained('clinic_services')->nullOnDelete();
        });

        Schema::table('patient_insurance_claim_line_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('clinic_service_id')->nullable()->after('service_item_id');
            $table->foreign('clinic_service_id', 'picli_clinic_service_fk')
                ->references('id')
                ->on('clinic_services')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_insurance_claim_line_items', function (Blueprint $table): void {
            $table->dropForeign('picli_clinic_service_fk');
            $table->dropColumn('clinic_service_id');
        });

        Schema::table('patient_ledger_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('clinic_service_id');
        });

        Schema::table('dental_chart_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('clinic_service_id');
        });

        Schema::table('treatment_plan_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('clinic_service_id');
        });
    }
};
