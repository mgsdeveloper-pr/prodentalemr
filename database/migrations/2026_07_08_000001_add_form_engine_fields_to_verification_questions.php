<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table): void {
            if (! Schema::hasColumn('verification_form_questions', 'question_kind')) {
                $table->string('question_kind')->default('normal')->after('template_key');
            }

            if (! Schema::hasColumn('verification_form_questions', 'parent_question_id')) {
                $table
                    ->foreignId('parent_question_id')
                    ->nullable()
                    ->after('question_kind')
                    ->constrained('verification_form_questions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('verification_form_questions', 'trigger_answer')) {
                $table->string('trigger_answer')->nullable()->after('parent_question_id');
            }

            if (! Schema::hasColumn('verification_form_questions', 'is_locked_by_admin')) {
                $table->boolean('is_locked_by_admin')->default(false)->after('is_builtin');
            }
        });

        if (Schema::hasTable('verification_template_sections')) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE verification_template_sections MODIFY clinic_id BIGINT UNSIGNED NULL');
            }

            Schema::table('verification_template_sections', function (Blueprint $table): void {
                if (! Schema::hasColumn('verification_template_sections', 'is_locked_by_admin')) {
                    $table->boolean('is_locked_by_admin')->default(false)->after('is_builtin');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table): void {
            if (Schema::hasColumn('verification_form_questions', 'parent_question_id')) {
                $table->dropConstrainedForeignId('parent_question_id');
            }

            foreach (['question_kind', 'trigger_answer', 'is_locked_by_admin'] as $column) {
                if (Schema::hasColumn('verification_form_questions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasTable('verification_template_sections')) {
            Schema::table('verification_template_sections', function (Blueprint $table): void {
                if (Schema::hasColumn('verification_template_sections', 'is_locked_by_admin')) {
                    $table->dropColumn('is_locked_by_admin');
                }
            });
        }
    }
};
