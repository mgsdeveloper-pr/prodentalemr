<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_carrier_network_profiles', function (Blueprint $table): void {
            $table->string('source_document_name')->nullable()->after('fee_schedule_reference_external_url');
            $table->string('source_document_file_path')->nullable()->after('source_document_name');
            $table->date('source_document_effective_date')->nullable()->after('source_document_file_path');
            $table->string('source_document_type')->nullable()->after('source_document_effective_date');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_carrier_network_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'source_document_name',
                'source_document_file_path',
                'source_document_effective_date',
                'source_document_type',
            ]);
        });
    }
};
