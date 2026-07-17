<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('verification_form_questions')) {
            return;
        }

        $onlyExistingColumns = function (array $values): array {
            return collect($values)
                ->filter(fn ($value, string $column): bool => Schema::hasColumn('verification_form_questions', $column))
                ->all();
        };

        $now = now();
        $templateKey = 'template_3';
        $sectionKey = 'template_3_maximums_deductibles';

        foreach ([
            ['Annual Maximum Used?', 'vf_annual_maximum_used_display', 15],
            ['Deductible Met - Individual', 'vf_individual_deductible_met_display', 45],
            ['Deductible Met - Family', 'vf_family_deductible_met_display', 65],
        ] as [$prompt, $fieldKey, $sortOrder]) {
            DB::table('verification_form_questions')->updateOrInsert(
                $onlyExistingColumns([
                    'organization_id' => null,
                    'clinic_id' => null,
                    'template_key' => $templateKey,
                    'section_key' => $sectionKey,
                    'field_key' => $fieldKey,
                ]),
                $onlyExistingColumns([
                    'organization_id' => null,
                    'clinic_id' => null,
                    'template_key' => $templateKey,
                    'section_key' => $sectionKey,
                    'prompt' => $prompt,
                    'question_kind' => 'normal',
                    'form_type' => 'both',
                    'input_type' => 'currency',
                    'field_key' => $fieldKey,
                    'sort_order' => $sortOrder,
                    'is_builtin' => true,
                    'is_locked_by_admin' => true,
                    'is_required_for_audit' => false,
                    'is_active' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            );
        }

        foreach ([
            'vf_individual_deductible_remaining' => 'Individual Deductible Remaining',
            'vf_family_deductible_remaining' => 'Family Deductible Remaining',
        ] as $fieldKey => $prompt) {
            DB::table('verification_form_questions')
                ->where('template_key', $templateKey)
                ->where('section_key', $sectionKey)
                ->where('field_key', $fieldKey)
                ->update($onlyExistingColumns([
                    'prompt' => $prompt,
                    'updated_at' => $now,
                ]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('verification_form_questions')) {
            return;
        }

        DB::table('verification_form_questions')
            ->where('template_key', 'template_3')
            ->where('section_key', 'template_3_maximums_deductibles')
            ->whereIn('field_key', [
                'vf_annual_maximum_used_display',
                'vf_individual_deductible_met_display',
                'vf_family_deductible_met_display',
            ])
            ->delete();
    }
};
