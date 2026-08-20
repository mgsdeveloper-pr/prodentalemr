<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            if (! Schema::hasColumn('clinics', 'allow_verification_manager_template_edits')) {
                $table->boolean('allow_verification_manager_template_edits')
                    ->default(false)
                    ->after('verification_default_form_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            if (Schema::hasColumn('clinics', 'allow_verification_manager_template_edits')) {
                $table->dropColumn('allow_verification_manager_template_edits');
            }
        });
    }
};
