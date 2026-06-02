<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->string('requested_by_name')->nullable()->after('form_type');
            $table->string('requested_by_role_slug')->nullable()->after('requested_by_name');
            $table->string('requested_from_panel')->nullable()->after('requested_by_role_slug');
            $table->string('patient_full_name')->nullable()->after('requested_from_panel');
            $table->date('patient_dob')->nullable()->after('patient_full_name');
            $table->string('patient_identifier')->nullable()->after('patient_dob');
            $table->string('patient_zip')->nullable()->after('patient_identifier');
            $table->string('appointment_time')->nullable()->after('patient_zip');
            $table->string('pms_id')->nullable()->after('appointment_time');
            $table->boolean('is_pre_registered')->default(false)->after('pms_id');
        });
    }

    public function down(): void
    {
        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'requested_by_name',
                'requested_by_role_slug',
                'requested_from_panel',
                'patient_full_name',
                'patient_dob',
                'patient_identifier',
                'patient_zip',
                'appointment_time',
                'pms_id',
                'is_pre_registered',
            ]);
        });
    }
};
