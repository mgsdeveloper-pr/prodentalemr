<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->string('coverage_diagnostic_deductible_applies')->nullable()->after('deductible_applies_notes');
            $table->string('coverage_basic_restorative_deductible_applies')->nullable()->after('coverage_diagnostic_deductible_applies');
            $table->string('coverage_endodontics_deductible_applies')->nullable()->after('coverage_basic_restorative_deductible_applies');
            $table->string('coverage_periodontics_deductible_applies')->nullable()->after('coverage_endodontics_deductible_applies');
            $table->string('coverage_oral_surgery_deductible_applies')->nullable()->after('coverage_periodontics_deductible_applies');
            $table->string('coverage_major_restorative_deductible_applies')->nullable()->after('coverage_oral_surgery_deductible_applies');
            $table->string('coverage_orthodontics_deductible_applies')->nullable()->after('coverage_major_restorative_deductible_applies');
        });
    }

    public function down(): void
    {
        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'coverage_diagnostic_deductible_applies',
                'coverage_basic_restorative_deductible_applies',
                'coverage_endodontics_deductible_applies',
                'coverage_periodontics_deductible_applies',
                'coverage_oral_surgery_deductible_applies',
                'coverage_major_restorative_deductible_applies',
                'coverage_orthodontics_deductible_applies',
            ]);
        });
    }
};
