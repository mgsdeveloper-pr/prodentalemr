<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_work_items', function (Blueprint $table): void {
            $table->json('coverage_assessment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('billing_work_items', fn (Blueprint $table) => $table->dropColumn('coverage_assessment'));
    }
};
