<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table): void {
            if (! Schema::hasColumn('verification_form_questions', 'is_required_for_audit')) {
                $table->boolean('is_required_for_audit')->default(false)->after('is_locked_by_admin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table): void {
            if (Schema::hasColumn('verification_form_questions', 'is_required_for_audit')) {
                $table->dropColumn('is_required_for_audit');
            }
        });
    }
};
