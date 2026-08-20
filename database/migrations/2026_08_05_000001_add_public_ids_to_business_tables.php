<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'organizations',
        'dsos',
        'clinics',
        'locations',
        'users',
        'providers',
        'staff',
        'patients',
        'appointments',
        'appointment_import_batches',
        'encounters',
        'treatment_plans',
        'treatment_plan_items',
        'dental_chart_entries',
        'perio_charts',
        'perio_chart_entries',
        'patient_insurance_policies',
        'patient_insurance_claims',
        'patient_insurance_claim_line_items',
        'patient_ledger_entries',
        'patient_statements',
        'patient_documents',
        'patient_consent_forms',
        'managed_billing_services',
        'client_service_enrollments',
        'billing_work_items',
        'billing_work_item_notes',
        'billing_work_item_attachments',
        'billing_work_item_activities',
        'verification_profiles',
        'verification_plan_snapshots',
        'verification_form_questions',
        'verification_form_answers',
        'verification_form_submissions',
        'verification_template_versions',
        'verification_template_sections',
        'verification_coverage_codes',
        'verification_notifications',
        'verification_inbox_messages',
        'verification_inbox_attachments',
        'verification_inbox_mailboxes',
        'portal_credentials',
        'clinic_portal_credential_overrides',
        'portal_credential_password_histories',
        'user_mailboxes',
        'invoices',
        'invoice_items',
        'payments',
        'subscriptions',
        'subscription_plans',
        'service_items',
        'insurance_carriers',
        'insurance_carrier_network_profiles',
        'clinic_insurance_carrier_overrides',
        'clinic_operatories',
        'clinic_services',
        'audit_logs',
        'saas_entitlement_audit_logs',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'public_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table): void {
                $table->ulid('public_id')->nullable()->after('id');
            });

            $this->backfillPublicIds($table);

            Schema::table($table, function (Blueprint $table): void {
                $table->unique('public_id');
            });

            $this->makePublicIdRequiredWhenSafe($table);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropUnique($table . '_public_id_unique');
                $blueprint->dropColumn('public_id');
            });
        }
    }

    private function backfillPublicIds(string $table): void
    {
        DB::table($table)
            ->whereNull('public_id')
            ->orderBy('id')
            ->select('id')
            ->chunkById(500, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->whereNull('public_id')
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });
    }

    private function makePublicIdRequiredWhenSafe(string $table): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $missing = DB::table($table)->whereNull('public_id')->exists();

        if (! $missing) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `public_id` CHAR(26) NOT NULL");
        }
    }
};
