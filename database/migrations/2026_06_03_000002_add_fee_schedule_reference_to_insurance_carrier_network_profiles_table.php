<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_carrier_network_profiles', function (Blueprint $table): void {
            $table->string('fee_schedule_reference_name')->nullable()->after('specialist_rule_notes');
            $table->string('fee_schedule_reference_file_path')->nullable()->after('fee_schedule_reference_name');
            $table->string('fee_schedule_reference_external_url')->nullable()->after('fee_schedule_reference_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_carrier_network_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'fee_schedule_reference_name',
                'fee_schedule_reference_file_path',
                'fee_schedule_reference_external_url',
            ]);
        });
    }
};
