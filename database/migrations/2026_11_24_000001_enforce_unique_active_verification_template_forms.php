<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX vtv_one_active_full ON verification_template_versions (scope, COALESCE(clinic_id, 0), template_key) WHERE active_full_form = 1');
            DB::statement('CREATE UNIQUE INDEX vtv_one_active_short ON verification_template_versions (scope, COALESCE(clinic_id, 0), template_key) WHERE active_short_form = 1');

            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE verification_template_versions
                ADD COLUMN full_active_clinic_key BIGINT UNSIGNED
                    GENERATED ALWAYS AS (CASE WHEN active_full_form = 1 THEN COALESCE(clinic_id, 0) END) VIRTUAL,
                ADD COLUMN short_active_clinic_key BIGINT UNSIGNED
                    GENERATED ALWAYS AS (CASE WHEN active_short_form = 1 THEN COALESCE(clinic_id, 0) END) VIRTUAL,
                ADD UNIQUE INDEX vtv_one_active_full (scope, full_active_clinic_key, template_key),
                ADD UNIQUE INDEX vtv_one_active_short (scope, short_active_clinic_key, template_key)
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX vtv_one_active_full');
            DB::statement('DROP INDEX vtv_one_active_short');

            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE verification_template_versions
                DROP INDEX vtv_one_active_full,
                DROP INDEX vtv_one_active_short,
                DROP COLUMN full_active_clinic_key,
                DROP COLUMN short_active_clinic_key
            SQL);
    }
};
