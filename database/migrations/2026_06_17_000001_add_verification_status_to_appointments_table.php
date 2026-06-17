<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('verification_status')->default('not_sent')->after('status');
            $table->foreignId('verification_work_item_id')->nullable()->after('verification_status')->constrained('billing_work_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('verification_work_item_id');
            $table->dropColumn('verification_status');
        });
    }
};
