<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->string('verification_assignment_method', 20)->default('unassigned');
        });
        Schema::table('billing_work_items', function (Blueprint $table): void {
            $table->string('assignment_method', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('billing_work_items', fn (Blueprint $table) => $table->dropColumn('assignment_method'));
        Schema::table('clinics', fn (Blueprint $table) => $table->dropColumn('verification_assignment_method'));
    }
};
