<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->string('waiting_period_perio')->nullable()->after('waiting_periods');
            $table->string('waiting_period_oral_surgery')->nullable()->after('waiting_period_perio');
            $table->string('waiting_period_crowns')->nullable()->after('waiting_period_oral_surgery');
            $table->string('waiting_period_prosthodontics')->nullable()->after('waiting_period_crowns');
            $table->string('waiting_period_implant_services')->nullable()->after('waiting_period_prosthodontics');
            $table->string('allowed_same_day_extraction')->nullable()->after('crowns_paid_on');
            $table->string('ortho_individual_deductible_amount')->nullable()->after('ortho_retention');
        });
    }

    public function down(): void
    {
        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'waiting_period_perio',
                'waiting_period_oral_surgery',
                'waiting_period_crowns',
                'waiting_period_prosthodontics',
                'waiting_period_implant_services',
                'allowed_same_day_extraction',
                'ortho_individual_deductible_amount',
            ]);
        });
    }
};
