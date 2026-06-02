<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->boolean('verification_notify_admin_all')->default(true)->after('billing_overdue_reminder_days');
            $table->boolean('verification_notify_assigned_user')->default(true)->after('verification_notify_admin_all');
            $table->boolean('verification_notify_clinic_self_service')->default(true)->after('verification_notify_assigned_user');
            $table->boolean('verification_notify_clinic_workspace')->default(false)->after('verification_notify_clinic_self_service');
            $table->boolean('verification_notify_on_managed_service_requested')->default(true)->after('verification_notify_clinic_workspace');
            $table->boolean('verification_notify_on_clinic_self_service_created')->default(true)->after('verification_notify_on_managed_service_requested');
            $table->boolean('verification_notify_on_verification_request_created')->default(true)->after('verification_notify_on_clinic_self_service_created');
            $table->boolean('verification_notify_on_admin_import_created')->default(true)->after('verification_notify_on_verification_request_created');
            $table->boolean('verification_notify_on_assignment_changed')->default(true)->after('verification_notify_on_admin_import_created');
            $table->boolean('verification_notify_on_status_changed')->default(true)->after('verification_notify_on_assignment_changed');
            $table->boolean('verification_notify_on_outcome_changed')->default(true)->after('verification_notify_on_status_changed');
            $table->boolean('verification_notify_on_clinic_verification_updated')->default(true)->after('verification_notify_on_outcome_changed');
            $table->boolean('verification_notify_on_verification_profile_saved')->default(false)->after('verification_notify_on_clinic_verification_updated');
            $table->boolean('verification_notify_on_verification_pdf_download')->default(false)->after('verification_notify_on_verification_profile_saved');
            $table->boolean('verification_notify_on_verification_pdf_preview')->default(false)->after('verification_notify_on_verification_pdf_download');
            $table->boolean('verification_notify_on_urgent_flagged')->default(true)->after('verification_notify_on_verification_pdf_preview');
            $table->boolean('verification_notify_on_urgent_assigned')->default(true)->after('verification_notify_on_urgent_flagged');
        });
    }

    public function down(): void
    {
        Schema::table('saas_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'verification_notify_admin_all',
                'verification_notify_assigned_user',
                'verification_notify_clinic_self_service',
                'verification_notify_clinic_workspace',
                'verification_notify_on_managed_service_requested',
                'verification_notify_on_clinic_self_service_created',
                'verification_notify_on_verification_request_created',
                'verification_notify_on_admin_import_created',
                'verification_notify_on_assignment_changed',
                'verification_notify_on_status_changed',
                'verification_notify_on_outcome_changed',
                'verification_notify_on_clinic_verification_updated',
                'verification_notify_on_verification_profile_saved',
                'verification_notify_on_verification_pdf_download',
                'verification_notify_on_verification_pdf_preview',
                'verification_notify_on_urgent_flagged',
                'verification_notify_on_urgent_assigned',
            ]);
        });
    }
};
