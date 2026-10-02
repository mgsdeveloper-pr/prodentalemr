<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_template_sections', function (Blueprint $table) {
            $table->boolean('allow_empty')->default(false);
        });

        \App\Models\VerificationTemplateVersion::where('status', 'draft')->each(function ($version) {
            if (! $version->canEditDirectly()) return;
            $version->questions()
                ->where('field_key', 'vf_coverage_orthodontics_deductible_applies')
                ->where('secondary_field_key', 'vf_ortho_lifetime_maximum')
                ->update(['secondary_field_key' => 'vf_ortho_benefit']);
        });
    }

    public function down(): void
    {
        // Never restore a known incorrect binding or reinterpret historical answers.
        Schema::table('verification_template_sections', fn (Blueprint $table) => $table->dropColumn('allow_empty'));
    }
};
