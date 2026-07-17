<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table): void {
            if (! Schema::hasColumn('verification_form_questions', 'template_version_id')) {
                $table->foreignId('template_version_id')
                    ->nullable()
                    ->after('clinic_id')
                    ->constrained('verification_template_versions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('verification_form_questions', 'source_question_id')) {
                $table->foreignId('source_question_id')
                    ->nullable()
                    ->after('template_version_id')
                    ->constrained('verification_form_questions')
                    ->nullOnDelete();
            }
        });

        Schema::table('verification_template_sections', function (Blueprint $table): void {
            if (! Schema::hasColumn('verification_template_sections', 'template_version_id')) {
                $table->foreignId('template_version_id')
                    ->nullable()
                    ->after('clinic_id')
                    ->constrained('verification_template_versions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('verification_template_sections', 'source_section_id')) {
                $table->foreignId('source_section_id')
                    ->nullable()
                    ->after('template_version_id')
                    ->constrained('verification_template_sections')
                    ->nullOnDelete();
            }
        });

        Schema::table('billing_work_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('billing_work_items', 'verification_template_version_id')) {
                $table->foreignId('verification_template_version_id')
                    ->nullable()
                    ->after('patient_insurance_claim_id')
                    ->constrained('verification_template_versions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('billing_work_items', 'verification_template_snapshot')) {
                $table->json('verification_template_snapshot')
                    ->nullable()
                    ->after('verification_template_version_id');
            }

            if (! Schema::hasColumn('billing_work_items', 'verification_template_snapshot_at')) {
                $table->timestamp('verification_template_snapshot_at')
                    ->nullable()
                    ->after('verification_template_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('billing_work_items', function (Blueprint $table): void {
            if (Schema::hasColumn('billing_work_items', 'verification_template_version_id')) {
                $table->dropConstrainedForeignId('verification_template_version_id');
            }

            if (Schema::hasColumn('billing_work_items', 'verification_template_snapshot')) {
                $table->dropColumn('verification_template_snapshot');
            }

            if (Schema::hasColumn('billing_work_items', 'verification_template_snapshot_at')) {
                $table->dropColumn('verification_template_snapshot_at');
            }
        });

        Schema::table('verification_template_sections', function (Blueprint $table): void {
            if (Schema::hasColumn('verification_template_sections', 'source_section_id')) {
                $table->dropConstrainedForeignId('source_section_id');
            }

            if (Schema::hasColumn('verification_template_sections', 'template_version_id')) {
                $table->dropConstrainedForeignId('template_version_id');
            }
        });

        Schema::table('verification_form_questions', function (Blueprint $table): void {
            if (Schema::hasColumn('verification_form_questions', 'source_question_id')) {
                $table->dropConstrainedForeignId('source_question_id');
            }

            if (Schema::hasColumn('verification_form_questions', 'template_version_id')) {
                $table->dropConstrainedForeignId('template_version_id');
            }
        });
    }
};
