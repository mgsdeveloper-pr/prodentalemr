<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('managed_billing_services', function (Blueprint $table): void {
            $table->index(['category', 'status', 'deleted_at'], 'mbs_category_status_deleted_idx');
        });

        Schema::table('client_service_enrollments', function (Blueprint $table): void {
            $table->index(
                ['organization_id', 'clinic_id', 'managed_billing_service_id', 'status'],
                'cse_org_clinic_service_status_idx',
            );
            $table->index(['clinic_id', 'status'], 'cse_clinic_status_idx');
            $table->index(['location_id', 'status'], 'cse_location_status_idx');
        });

        Schema::table('billing_work_items', function (Blueprint $table): void {
            $table->index(
                ['clinic_id', 'managed_billing_service_id', 'deleted_at'],
                'bwi_clinic_service_deleted_idx',
            );
            $table->index(['clinic_id', 'status', 'due_at'], 'bwi_clinic_status_due_idx');
            $table->index(['clinic_id', 'priority', 'due_at'], 'bwi_clinic_priority_due_idx');
            $table->index(['clinic_id', 'assigned_to', 'status'], 'bwi_clinic_assignee_status_idx');
            $table->index(['clinic_id', 'pms_sync_status'], 'bwi_clinic_pms_sync_idx');
            $table->index(['clinic_id', 'outcome_status'], 'bwi_clinic_outcome_idx');
            $table->index(['managed_billing_service_id', 'status', 'due_at'], 'bwi_service_status_due_idx');
            $table->index(['organization_id', 'clinic_id', 'created_at'], 'bwi_org_clinic_created_idx');
        });

        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->index('appointment_date', 'vp_appointment_date_idx');
            $table->index('pms_id', 'vp_pms_id_idx');
            $table->index('patient_identifier', 'vp_patient_identifier_idx');
            $table->index('patient_dob', 'vp_patient_dob_idx');
            $table->index(['insurance_provider_name', 'group_number'], 'vp_provider_group_idx');
        });

        Schema::table('verification_form_questions', function (Blueprint $table): void {
            $table->index(
                ['clinic_id', 'is_active', 'section_key', 'form_type', 'sort_order'],
                'vfq_clinic_active_section_form_order_idx',
            );
            $table->index(
                ['clinic_id', 'is_builtin', 'section_key', 'sort_order'],
                'vfq_clinic_builtin_section_order_idx',
            );
        });

        Schema::table('patients', function (Blueprint $table): void {
            $table->index(['organization_id', 'clinic_id', 'location_id'], 'patients_org_clinic_location_idx');
            $table->index(['clinic_id', 'pms_patient_id'], 'patients_clinic_pms_id_idx');
            $table->index(['clinic_id', 'insurance_number'], 'patients_clinic_insurance_number_idx');
            $table->index(['clinic_id', 'dob'], 'patients_clinic_dob_idx');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->index(['clinic_id', 'appointment_date', 'status'], 'appointments_clinic_date_status_idx');
            $table->index(['patient_id', 'appointment_date'], 'appointments_patient_date_idx');
            $table->index(['provider_id', 'appointment_date'], 'appointments_provider_date_idx');
        });

        Schema::table('patient_insurance_policies', function (Blueprint $table): void {
            $table->index(['clinic_id', 'patient_id', 'status'], 'pip_clinic_patient_status_idx');
            $table->index(['clinic_id', 'member_id'], 'pip_clinic_member_id_idx');
            $table->index(['patient_id', 'coverage_priority', 'status'], 'pip_patient_priority_status_idx');
        });

        Schema::table('patient_insurance_claims', function (Blueprint $table): void {
            $table->index(['clinic_id', 'status', 'claim_date'], 'pic_clinic_status_claim_date_idx');
            $table->index(['patient_insurance_policy_id', 'status'], 'pic_policy_status_idx');
            $table->index(['appointment_id', 'status'], 'pic_appointment_status_idx');
            $table->index(['patient_id', 'service_date'], 'pic_patient_service_date_idx');
        });

        Schema::table('patient_ledger_entries', function (Blueprint $table): void {
            $table->index(['clinic_id', 'patient_id', 'posted_on'], 'ple_clinic_patient_posted_idx');
            $table->index(['clinic_id', 'entry_type', 'posted_on'], 'ple_clinic_entry_type_posted_idx');
            $table->index(['patient_id', 'status', 'posted_on'], 'ple_patient_status_posted_idx');
        });
    }

    public function down(): void
    {
        Schema::table('patient_ledger_entries', function (Blueprint $table): void {
            $table->dropIndex('ple_patient_status_posted_idx');
            $table->dropIndex('ple_clinic_entry_type_posted_idx');
            $table->dropIndex('ple_clinic_patient_posted_idx');
        });

        Schema::table('patient_insurance_claims', function (Blueprint $table): void {
            $table->dropIndex('pic_patient_service_date_idx');
            $table->dropIndex('pic_appointment_status_idx');
            $table->dropIndex('pic_policy_status_idx');
            $table->dropIndex('pic_clinic_status_claim_date_idx');
        });

        Schema::table('patient_insurance_policies', function (Blueprint $table): void {
            $table->dropIndex('pip_patient_priority_status_idx');
            $table->dropIndex('pip_clinic_member_id_idx');
            $table->dropIndex('pip_clinic_patient_status_idx');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_provider_date_idx');
            $table->dropIndex('appointments_patient_date_idx');
            $table->dropIndex('appointments_clinic_date_status_idx');
        });

        Schema::table('patients', function (Blueprint $table): void {
            $table->dropIndex('patients_clinic_dob_idx');
            $table->dropIndex('patients_clinic_insurance_number_idx');
            $table->dropIndex('patients_clinic_pms_id_idx');
            $table->dropIndex('patients_org_clinic_location_idx');
        });

        Schema::table('verification_form_questions', function (Blueprint $table): void {
            $table->dropIndex('vfq_clinic_builtin_section_order_idx');
            $table->dropIndex('vfq_clinic_active_section_form_order_idx');
        });

        Schema::table('verification_profiles', function (Blueprint $table): void {
            $table->dropIndex('vp_provider_group_idx');
            $table->dropIndex('vp_patient_dob_idx');
            $table->dropIndex('vp_patient_identifier_idx');
            $table->dropIndex('vp_pms_id_idx');
            $table->dropIndex('vp_appointment_date_idx');
        });

        Schema::table('billing_work_items', function (Blueprint $table): void {
            $table->dropIndex('bwi_org_clinic_created_idx');
            $table->dropIndex('bwi_service_status_due_idx');
            $table->dropIndex('bwi_clinic_outcome_idx');
            $table->dropIndex('bwi_clinic_pms_sync_idx');
            $table->dropIndex('bwi_clinic_assignee_status_idx');
            $table->dropIndex('bwi_clinic_priority_due_idx');
            $table->dropIndex('bwi_clinic_status_due_idx');
            $table->dropIndex('bwi_clinic_service_deleted_idx');
        });

        Schema::table('client_service_enrollments', function (Blueprint $table): void {
            $table->dropIndex('cse_location_status_idx');
            $table->dropIndex('cse_clinic_status_idx');
            $table->dropIndex('cse_org_clinic_service_status_idx');
        });

        Schema::table('managed_billing_services', function (Blueprint $table): void {
            $table->dropIndex('mbs_category_status_deleted_idx');
        });
    }
};
