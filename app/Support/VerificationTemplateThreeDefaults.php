<?php

namespace App\Support;

use App\Models\VerificationFormQuestion;

class VerificationTemplateThreeDefaults
{
    public static function syncMasterQuestions(): void
    {
        foreach (self::questions() as $question) {
            $lookup = [
                'organization_id' => null,
                'clinic_id' => null,
                'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
                'section_key' => $question['section_key'],
            ];

            if (filled($question['field_key'] ?? null)) {
                $lookup['field_key'] = $question['field_key'];
            } else {
                $lookup['prompt'] = $question['prompt'];
            }

            VerificationFormQuestion::query()->updateOrCreate(
                $lookup,
                [
                    'prompt' => $question['prompt'],
                    'question_kind' => VerificationFormQuestion::QUESTION_KIND_NORMAL,
                    'form_type' => $question['form_type'] ?? 'both',
                    'input_type' => $question['input_type'] ?? 'text',
                    'field_key' => $question['field_key'] ?? null,
                    'secondary_field_key' => $question['secondary_field_key'] ?? null,
                    'secondary_input_type' => $question['secondary_input_type'] ?? null,
                    'help_text' => $question['help_text'] ?? null,
                    'placeholder' => $question['placeholder'] ?? null,
                    'select_options' => $question['select_options'] ?? null,
                    'frequency_response_mode' => $question['frequency_response_mode'] ?? null,
                    'frequency_response_fields' => $question['frequency_response_fields'] ?? null,
                    'has_note' => $question['has_note'] ?? false,
                    'note_label' => $question['note_label'] ?? null,
                    'note_placeholder' => $question['note_placeholder'] ?? null,
                    'sort_order' => $question['sort_order'],
                    'is_builtin' => true,
                    'is_locked_by_admin' => true,
                    'is_required_for_audit' => false,
                    'is_active' => $question['is_active'] ?? true,
                ],
            );
        }
    }

    public static function questions(): array
    {
        return [
            ...self::patientSubscriberQuestions(),
            ...self::insuranceQuestions(),
            ...self::maximumsAndDeductiblesQuestions(),
            ...self::coverageCategoryQuestions(),
            ...self::planProvisionQuestions(),
            ...self::serviceHistoryQuestions(),
            ...self::verificationInformationQuestions(),
        ];
    }

    /**
     * These rows belonged to the retired fixed Frequency worksheet. Frequency
     * questions are now added intentionally through the template builder.
     *
     * @return array<int, string>
     */
    public static function legacyFrequencyFieldKeys(): array
    {
        return collect([
            ...self::frequencyDiagnosticPreventativeQuestions(),
            ...self::frequencyBasicQuestions(),
            ...self::frequencyMajorQuestions(),
            ...self::frequencyOrthodonticsQuestions(),
        ])
            ->pluck('field_key')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected static function patientSubscriberQuestions(): array
    {
        return self::section('template_3_patient_subscriber', [
            ['Patient Name', 'vf_patient_full_name', 'text', 10],
            ['Date of Birth', 'vf_patient_dob', 'date', 20],
            ['Member ID', 'vf_patient_identifier', 'text', 30],
            ['Relationship', 'vf_insured_relation', 'select', 40, 'Self, Spouse, Son, Daughter, Dependent'],
            ['Subscriber Name', 'vf_subscriber_name', 'text', 50],
            ['Subscriber DOB', 'vf_subscriber_dob', 'date', 60],
            ['Subscriber ID', 'vf_subscriber_id', 'text', 70],
            ['COB', 'vf_coverage_role', 'select', 80, 'No COB, Primary, Secondary'],
        ]);
    }

    protected static function insuranceQuestions(): array
    {
        return self::section('template_3_insurance', [
            ['Insurance Provider', 'vf_insurance_provider_name', 'select', 10],
            ['Group Number', 'vf_group_number', 'text', 20],
            ['Plan Type', 'vf_plan_type', 'select', 30, 'PPO, DHMO, Indemnity'],
            ['Network Status', 'vf_network_status', 'select', 40, 'In Network, Out of Network'],
            ['Effective Date', 'vf_effective_date', 'date', 50],
            ['Future Termination Date', 'vf_future_termination_date', 'date', 60],
            ['Plan Renewal Month', 'vf_plan_renewal_month', 'month_year', 70],
            ['Claims Address', 'vf_insurance_claim_mailing_address', 'textarea', 80],
            ['Payer ID', 'vf_payer_id', 'text', 90],
            ['Phone Number', 'vf_insurance_company_phone_number', 'tel', 100],
            ['Fee Schedule', 'vf_fee_schedule', 'text', 110],
            ['Employer / Group Name', 'vf_group_name', 'text', 120],
        ]);
    }

    protected static function maximumsAndDeductiblesQuestions(): array
    {
        return self::section('template_3_maximums_deductibles', [
            ['Annual Maximum on the Plan?', 'vf_annual_maximum', 'currency', 10],
            ['Annual Maximum Used?', 'vf_annual_maximum_used_display', 'currency', 15, null, false],
            ['Annual Maximum Remaining?', 'vf_annual_maximum_remaining', 'currency', 20],
            ['Annual Deductible - Individual', 'vf_individual_deductible', 'currency', 30],
            ['Individual Deductible Remaining', 'vf_individual_deductible_remaining', 'currency', 40],
            ['Deductible Met - Individual', 'vf_individual_deductible_met_display', 'currency', 45, null, false],
            ['Annual Deductible - Family', 'vf_family_deductible', 'currency', 50],
            ['Family Deductible Remaining', 'vf_family_deductible_remaining', 'currency', 60],
            ['Deductible Met - Family', 'vf_family_deductible_met_display', 'currency', 65, null, false],
            ['Deductible Notes', 'vf_deductible_applies_notes', 'textarea', 70],
        ]);
    }

    protected static function coverageCategoryQuestions(): array
    {
        $rows = [
            ['Diagnostic & Preventive', 'vf_coverage_diagnostic_deductible_applies', 'vf_coverage_diagnostic', 10],
            ['Basic Restorative', 'vf_coverage_basic_restorative_deductible_applies', 'vf_coverage_basic_restorative', 20],
            ['Endodontics', 'vf_coverage_endodontics_deductible_applies', 'vf_coverage_endodontics', 30],
            ['Periodontics', 'vf_coverage_periodontics_deductible_applies', 'vf_coverage_periodontics', 40],
            ['Oral Surgery', 'vf_coverage_oral_surgery_deductible_applies', 'vf_coverage_oral_surgery', 50],
            ['Major Restorative', 'vf_coverage_major_restorative_deductible_applies', 'vf_coverage_major_restorative', 60],
            ['Orthodontics', 'vf_coverage_orthodontics_deductible_applies', 'vf_ortho_lifetime_maximum', 70],
        ];

        return array_map(fn (array $row): array => [
            'section_key' => 'template_3_coverage_category',
            'prompt' => $row[0],
            'field_key' => $row[1],
            'input_type' => 'yes_no',
            'secondary_field_key' => $row[2],
            'secondary_input_type' => 'percent',
            'sort_order' => $row[3],
        ], $rows);
    }

    protected static function planProvisionQuestions(): array
    {
        return self::section('template_3_plan_provisions', [
            ['Is there any Waiting Period on this plan?', 'vf_waiting_periods', 'yes_no', 10],
            ['Missing Tooth Clause', 'vf_missing_tooth_clause', 'yes_no', 20],
            ['Crowns are paid on Prep Date or Seat Date?', 'vf_crowns_paid_on', 'select', 30, 'Prep, Seat, Either-Or'],
            ['Prosthetic Replacement Year / Month', 'vf_prosthetic_replacement_period', 'month_year', 40],
            ['Coordination of Benefits', 'vf_coordination_of_benefits', 'select', 50, 'Standard, Non-Dup, Birthday Rule, No COB, Other'],
            ['Plan Provision Notes', 'vf_plan_provisions', 'textarea', 60],
        ]);
    }

    protected static function serviceHistoryQuestions(): array
    {
        return self::section('template_3_service_history', [
            ['Exams', 'vf_history_exams', 'text', 10],
            ['Prophylaxis', 'vf_history_prophylaxis', 'text', 20],
            ['Bitewings', 'vf_history_bitewings', 'text', 30],
            ['Full Mouth X-Ray / Panoramic X-Ray', 'vf_history_full_mouth_xray', 'text', 40],
            ['Other Major History Affecting Eligibility', 'vf_history_basic_or_major', 'textarea', 50],
        ]);
    }

    protected static function frequencyDiagnosticPreventativeQuestions(): array
    {
        return self::section('template_3_frequency_diagnostic_preventative', [
            ['Regular Oral Exams (D0120)', 'vf_frequency_regular_oral_exams', 'text', 10],
            ['Limited Exam (D0140)', 'vf_frequency_limited_exam', 'text', 20],
            ['Comprehensive Exam (D0150)', 'vf_frequency_comprehensive_exam', 'text', 30],
            ['Does (D0120, D0140, D0150) Share Freq?', 'vf_frequency_exam_shared', 'text', 40],
            ['Oral Cancer Screening (D0431)', 'vf_frequency_oral_cancer_screening', 'text', 50],
            ['Can we bill Oral D0431 in conjunction with D0150 or D0120?', 'vf_frequency_oral_cancer_conjunction', 'text', 60],
            ['Prophylaxis (D1110/D1120)', 'vf_frequency_prophylaxis', 'text', 70],
            ['Bitewings X-Ray (D0272/D0274)', 'vf_frequency_bitewings', 'text', 80],
            ['Full Mouth X-Ray / Panoramic X-Ray (D0210 / D0330) Share Freq', 'vf_frequency_full_mouth_pano_shared', 'text', 90],
            ['Pa\'s (D0220 / D0230)', 'vf_frequency_pas', 'text', 100],
            ['Sealants (D1351) & Age Limit', 'vf_frequency_sealants', 'text', 110],
            ['If yes ask guideline)', 'vf_frequency_sealants_guideline', 'text', 120],
            ['Caries-Arresting Medicament (D1354) & Age Limit', 'vf_frequency_caries_arresting', 'text', 130],
            ['Fluoride (D1206/D1208) & Age Limit', 'vf_frequency_fluoride', 'text', 140],
        ]);
    }

    protected static function frequencyBasicQuestions(): array
    {
        return self::section('template_3_frequency_basic', [
            ['Scaling & Root Planning (D4341/ D4342)', 'vf_basic_scaling_root_planing', 'text', 10],
            ['Can all 4 quads be done on the same visit? (Guidelines)', 'vf_basic_all_quads_same_visit', 'text', 20],
            ['Perio Maintenance (D4910) Share frq (Yes/No)?', 'vf_basic_perio_maintenance_share_freq', 'text', 30],
            ['FMD (D4355)', 'vf_basic_fmd', 'text', 40],
            ['Root Canal (D3310 / D3320 / D3330)', 'vf_basic_root_canal', 'text', 50],
            ['Simple Extraction (D7140)', 'vf_basic_simple_extraction', 'text', 60],
            ['Surgical Extraction (D7210)', 'vf_basic_surgical_extraction', 'text', 70],
            ['Posterior Composites (D2391/D2392/D2393/D2394)', 'vf_basic_posterior_composites', 'text', 80],
            ['Post Composites downgraded to Amalgam?', 'vf_basic_composites_downgrade', 'text', 90],
            ['Occlusal Guard (D9944/D9945) "Bruxism only OR osseous surgery only"', 'vf_basic_occlusal_guard', 'text', 100],
        ]);
    }

    protected static function frequencyMajorQuestions(): array
    {
        return self::section('template_3_frequency_major', [
            ['Crowns (D2740) Downgrade (Yes/No)? If Yes need Code.', 'vf_major_crowns_downgrade', 'text', 10],
            ['Porcelain Fused To High Noble Metal Crown (D2750)', 'vf_major_pf_high_noble', 'text', 20],
            ['Application of hydroxyapatite regeneration medicament (D2991)', 'vf_major_hydroxyapatite', 'text', 30],
            ['Dentures (D5110)', 'vf_major_dentures', 'text', 40],
            ['Implant (D6010)', 'vf_major_implant', 'text', 50],
            ['Implant abutment (D6057)', 'vf_major_implant_abutment', 'text', 60],
            ['Implant Crown (D6058)', 'vf_major_implant_crown', 'text', 70],
            ['Bone Graft Performed At The Same Time As A Dental Implant (D6104)', 'vf_major_bone_graft_same_time_implant', 'text', 80],
            ['BoneGrafts (D7953)', 'vf_major_bone_grafts', 'text', 90],
        ]);
    }

    protected static function frequencyOrthodonticsQuestions(): array
    {
        return self::section('template_3_frequency_orthodontics', [
            ['Orthodontic Retention (D8680)', 'vf_ortho_retention', 'text', 10],
            ['Ortho Lifetime Maximum?', 'vf_ortho_lifetime_maximum', 'text', 20],
            ['Remaining Ortho maximum?', 'vf_ortho_remaining_maximum', 'text', 30],
            ['Ortho Deductibles?', 'vf_ortho_deductibles', 'text', 40],
            ['Ortho Age Limit?', 'vf_ortho_age_limit', 'text', 50],
        ]);
    }

    protected static function verificationInformationQuestions(): array
    {
        return self::section('template_3_verification_information', [
            ['Insurance Reference #', null, 'text', 10],
            ['Insurance Representative', 'vf_insurance_representative_name', 'text', 20],
            ['Verified By', 'vf_verified_by', 'text', 30],
            ['Verification Date', 'vf_verification_date', 'date', 40],
            ['Additional Information', 'vf_verification_notes', 'textarea', 50],
        ]);
    }

    protected static function section(string $sectionKey, array $questions): array
    {
        return array_map(function (array $question) use ($sectionKey): array {
            return [
                'section_key' => $sectionKey,
                'prompt' => $question[0],
                'field_key' => $question[1],
                'input_type' => $question[2],
                'sort_order' => $question[3],
                'select_options' => $question[4] ?? null,
                'is_active' => $question[5] ?? true,
            ];
        }, $questions);
    }
}
