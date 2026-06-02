<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $globalQuestions = DB::table('verification_form_questions')
            ->whereNull('clinic_id')
            ->get();

        if ($globalQuestions->isEmpty()) {
            return;
        }

        $clinics = DB::table('clinics')
            ->select('id', 'organization_id')
            ->get();

        $now = now();

        foreach ($clinics as $clinic) {
            foreach ($globalQuestions as $question) {
                DB::table('verification_form_questions')->updateOrInsert(
                    [
                        'clinic_id' => $clinic->id,
                        'section_key' => $question->section_key,
                        'prompt' => $question->prompt,
                        'code' => $question->code,
                        'is_builtin' => (bool) $question->is_builtin,
                    ],
                    [
                        'organization_id' => $clinic->organization_id,
                        'form_type' => $question->form_type,
                        'input_type' => $question->input_type,
                        'field_key' => $question->field_key,
                        'secondary_field_key' => $question->secondary_field_key,
                        'secondary_input_type' => $question->secondary_input_type,
                        'help_text' => $question->help_text,
                        'placeholder' => $question->placeholder,
                        'sort_order' => $question->sort_order,
                        'is_active' => $question->is_active,
                        'updated_at' => $now,
                        'created_at' => $question->created_at ?? $now,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('verification_form_questions')
            ->whereNotNull('clinic_id')
            ->delete();
    }
};
