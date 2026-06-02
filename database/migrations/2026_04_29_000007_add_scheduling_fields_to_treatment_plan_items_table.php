<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->after('service_item_id')->constrained()->nullOnDelete();
            $table->date('target_date')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('treatment_plan_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appointment_id');
            $table->dropColumn('target_date');
        });
    }
};
