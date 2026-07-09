<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->string('coverage_role')->nullable()->after('insured_relation');
            $table->string('coordination_of_benefits')->nullable()->after('coverage_role');
        });
    }

    public function down(): void
    {
        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'coverage_role',
                'coordination_of_benefits',
            ]);
        });
    }
};
