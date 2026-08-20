<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_work_items', function (Blueprint $table): void {
            $table->string('processing_mode', 32)
                ->default('managed_service')
                ->after('source')
                ->index();
        });

        DB::table('billing_work_items')
            ->where('source', 'clinic_self_service')
            ->update(['processing_mode' => 'self_managed']);
    }

    public function down(): void
    {
        Schema::table('billing_work_items', function (Blueprint $table): void {
            $table->dropIndex(['processing_mode']);
            $table->dropColumn('processing_mode');
        });
    }
};
