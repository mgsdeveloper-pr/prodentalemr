<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_services', function (Blueprint $table): void {
            $table->unsignedSmallInteger('default_duration_minutes')->default(30)->after('default_fee');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('clinic_service_id')->nullable()->after('clinic_operatory_id')->constrained('clinic_services')->nullOnDelete();
            $table->foreignId('patient_insurance_policy_id')->nullable()->after('patient_id')->constrained('patient_insurance_policies')->nullOnDelete();
            $table->foreignId('parent_appointment_id')->nullable()->after('patient_insurance_policy_id')->constrained('appointments')->nullOnDelete();
            $table->boolean('verification_required')->default(true)->after('verification_status');
            $table->string('verification_processing_mode')->nullable()->after('verification_required');
            $table->string('source')->default('manual')->after('appointment_type');
            $table->text('reason_for_visit')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('clinic_service_id');
            $table->dropConstrainedForeignId('patient_insurance_policy_id');
            $table->dropConstrainedForeignId('parent_appointment_id');
            $table->dropColumn([
                'verification_required',
                'verification_processing_mode',
                'source',
                'reason_for_visit',
            ]);
        });

        Schema::table('clinic_services', function (Blueprint $table): void {
            $table->dropColumn('default_duration_minutes');
        });
    }
};
