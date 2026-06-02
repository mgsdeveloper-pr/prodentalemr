<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->string('pms_patient_id')->nullable()->after('created_by')->index();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->dropColumn('pms_patient_id');
        });
    }
};
