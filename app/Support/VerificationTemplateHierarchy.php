<?php

namespace App\Support;

use App\Models\VerificationFormQuestion as Question;
use App\Models\VerificationTemplateVersion as Version;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerificationTemplateHierarchy
{
    public static function definitions(string $form): array
    {
        $short = $form === 'short_form';
        return [
            'patient' => [$short ? 'Patient & Appointment Information' : 'Patient / Insurance Information', ['patient_provider' => $short ? 'Patient / Provider' : 'Patient & Provider', ...($short ? [] : ['insurance_details' => 'Insurance'])]],
            ...($short ? ['insurance' => ['Insurance Information', ['insurance_details' => 'Insurance Details']]] : []),
            'maximums' => ['Maximums & Deductibles', ['annual_maximum' => $short ? 'Annual Maximum' : 'Plan Maximums', 'deductibles' => $short ? 'Deductible' : 'Deductibles', ...($short ? ['orthodontics' => 'Orthodontics'] : ['coverage_category' => 'Coverage by Category'])]],
            'provisions' => ['Plan Provisions', ['plan_provisions' => $short ? 'Waiting Period' : 'General Plan Provisions']],
            'history' => [$short ? 'Service History' : 'History', $short ? ['diagnostic_history' => 'Diagnostic & Preventive History', 'other_history' => 'Other History'] : ['service_history' => 'Service History']],
            ...($short ? [] : ['frequency' => ['Frequency & Percentage', ['diagnostic' => 'Diagnostic & Preventative', 'basic' => 'Basic', 'major' => 'Major', 'orthodontics' => 'Orthodontics']]]),
            'verification' => ['Verification Information', ['verification_details' => 'Verification Details']],
            'additional' => ['Additional Information', ['comments' => 'Comments']],
        ];
    }

    public function arrangeDraft(Version $draft): void
    {
        abort_unless($draft->canEditDirectly(), 403);
        app(VerificationTemplateImport::class)->authorize(auth()->user(), $draft->clinic);
        if ($draft->uses_section_layout) return;
        if (! in_array($draft->form_type, ['short_form', 'full_form'], true)) {
            throw ValidationException::withMessages(['form_type' => 'Choose Short Form or Full Form for a structured layout.']);
        }
        DB::transaction(function () use ($draft) {
            $short = $draft->form_type === 'short_form';
            $questions = $draft->questions()->orderBy('sort_order')->orderBy('id')->get();
            $draft->sections()->delete();
            $targets = []; $order = 0;
            foreach (self::definitions($draft->form_type) as $key => [$label, $children]) {
                $parent = 'custom_layout_'.$key;
                $draft->sections()->create(['template_key' => $draft->template_key, 'organization_id' => $draft->organization_id, 'clinic_id' => $draft->clinic_id, 'section_key' => $parent, 'label' => $label, 'sort_order' => ++$order * 10, 'is_active' => true, 'is_builtin' => false]);
                foreach ($children as $child => $title) {
                    $targets[$child] = 'custom_layout_'.$child.'_answers';
                    $draft->sections()->create(['template_key' => $draft->template_key, 'organization_id' => $draft->organization_id, 'clinic_id' => $draft->clinic_id, 'section_key' => $targets[$child], 'parent_section_key' => $parent, 'label' => $title, 'sort_order' => ++$order * 10, 'is_active' => true, 'is_builtin' => false]);
                }
            }
            foreach ($questions as $question) {
                $field = $question->field_key ?? '';
                $section = $question->section_key;
                $target = match (true) {
                    $field === 'vf_verification_notes' => 'comments',
                    str_starts_with($field, 'vf_ortho_') || $section === 'template_3_frequency_orthodontics' => 'orthodontics',
                    $section === 'template_3_patient_subscriber' || in_array($field, ['context_clinic_name', 'vf_appointment_date', 'vf_network_status'], true) => 'patient_provider',
                    $section === 'template_3_insurance' => 'insurance_details',
                    $section === 'template_3_coverage_category' => $short ? 'deductibles' : 'coverage_category',
                    $section === 'template_3_maximums_deductibles' => str_contains($field, 'annual_maximum') ? 'annual_maximum' : 'deductibles',
                    $section === 'template_3_plan_provisions' => 'plan_provisions',
                    $section === 'template_3_service_history' => $short ? ($field === 'vf_history_basic_or_major' ? 'other_history' : 'diagnostic_history') : 'service_history',
                    $section === 'template_3_frequency_diagnostic_preventative' => 'diagnostic',
                    $section === 'template_3_frequency_basic' => 'basic',
                    $section === 'template_3_frequency_major' => 'major',
                    $section === 'template_3_verification_information' => 'verification_details',
                    default => 'needs_mapping',
                };
                // Keep every answer definition, even when its source group is not in the chosen form.
                if ($question->input_type === 'frequency_row') {
                    $question->answer_layout = $question->answer_layout ?: $question->inferredAnswerLayout();
                    $question->response_category = $question->frequencyCategory();
                }
                if (! isset($targets[$target])) {
                    $target = 'needs_mapping';
                    if (! isset($targets[$target])) {
                        $targets[$target] = 'custom_layout_needs_mapping';
                        $draft->sections()->create([
                            'template_key' => $draft->template_key,
                            'organization_id' => $draft->organization_id,
                            'clinic_id' => $draft->clinic_id,
                            'section_key' => $targets[$target],
                            'label' => 'Needs Mapping',
                            'sort_order' => ++$order * 10,
                            'is_active' => true,
                            'is_builtin' => false,
                        ]);
                    }
                }
                $question->section_key = $targets[$target];
                $question->save();
            }
            $draft->update(['uses_section_layout' => true]);
        });
    }
}
