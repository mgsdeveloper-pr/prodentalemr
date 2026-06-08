<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_credentials', function (Blueprint $table): void {
            $table->boolean('visible_to_clinic')
                ->default(true)
                ->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('portal_credentials', function (Blueprint $table): void {
            $table->dropColumn('visible_to_clinic');
        });
    }
};
