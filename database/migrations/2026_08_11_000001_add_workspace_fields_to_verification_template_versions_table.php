<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_template_versions', function (Blueprint $table): void {
            if (! Schema::hasColumn('verification_template_versions', 'form_type')) {
                $table->string('form_type', 30)->default('both')->after('name')->index();
            }

            if (! Schema::hasColumn('verification_template_versions', 'clinic_visibility')) {
                $table->string('clinic_visibility', 40)->default('hidden')->after('form_type')->index();
            }

            if (! Schema::hasColumn('verification_template_versions', 'is_working_draft')) {
                $table->boolean('is_working_draft')->default(false)->after('is_active')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('verification_template_versions', function (Blueprint $table): void {
            foreach (['form_type', 'clinic_visibility', 'is_working_draft'] as $column) {
                if (Schema::hasColumn('verification_template_versions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
