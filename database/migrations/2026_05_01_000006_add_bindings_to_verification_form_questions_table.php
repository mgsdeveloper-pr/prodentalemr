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
            $table->string('field_key')->nullable()->after('input_type');
            $table->string('secondary_field_key')->nullable()->after('field_key');
            $table->string('secondary_input_type')->nullable()->after('secondary_field_key');
            $table->string('code')->nullable()->after('secondary_input_type');
            $table->boolean('is_builtin')->default(false)->after('sort_order');
        });

        $now = now();

        $questions = [
            ['prompt' => 'Clinic name', 'section_key' => 'core_details', 'input_type' => 'text', 'field_key' => 'vf_quick_reference', 'sort_order' => 10],
            ['prompt' => 'Is the provider in network with this plan?', 'section_key' => 'core_details', 'input_type' => 'text', 'field_key' => 'vf_network_status', 'sort_order' => 20],
            ['prompt' => 'Appointment Date', 'section_key' => 'core_details', 'input_type' => 'date', 'field_key' => 'vf_appointment_date', 'sort_order' => 30],
            ['prompt' => 'Insurance Name & Tel#', 'section_key' => 'core_details', 'input_type' => 'text', 'field_key' => 'vf_insurance_provider_name', 'sort_order' => 40],
            ['prompt' => 'Insurance Claim Mailing Address', 'section_key' => 'core_details', 'input_type' => 'text', 'field_key' => 'vf_insurance_claim_mailing_address', 'sort_order' => 50],
            ['prompt' => 'Electronic Payer ID#', 'section_key' => 'core_details', 'input_type' => 'text', 'field_key' => 'vf_payer_id', 'sort_order' => 60],
            ['prompt' => 'Effective Date', 'section_key' => 'core_details', 'input_type' => 'date', 'field_key' => 'vf_effective_date', 'sort_order' => 70],
            ['prompt' => 'Plan renewal month?', 'section_key' => 'core_details', 'input_type' => 'text', 'field_key' => 'vf_plan_renewal_month', 'sort_order' => 80],
            ['prompt' => 'Future Termination Date', 'section_key' => 'core_details', 'input_type' => 'date', 'field_key' => 'vf_future_termination_date', 'sort_order' => 90],
            ['prompt' => 'Employer / Group Name?', 'section_key' => 'core_details', 'input_type' => 'text', 'field_key' => 'vf_group_name', 'sort_order' => 100],
            ['prompt' => 'Group Number?', 'section_key' => 'core_details', 'input_type' => 'text', 'field_key' => 'vf_group_number', 'sort_order' => 110],
            ['prompt' => 'Which Fee Schedule shall we use?', 'section_key' => 'core_details', 'input_type' => 'text', 'field_key' => 'vf_fee_schedule', 'sort_order' => 120],
            ['prompt' => 'Annual Maximum on the plan?', 'section_key' => 'core_details', 'input_type' => 'currency', 'field_key' => 'vf_annual_maximum', 'sort_order' => 130],
            ['prompt' => 'Remaining amount from the maximum?', 'section_key' => 'core_details', 'input_type' => 'currency', 'field_key' => 'vf_annual_maximum_remaining', 'sort_order' => 140],
            ['prompt' => 'Annual Deductible (Individual | Family)?', 'section_key' => 'core_details', 'input_type' => 'currency', 'field_key' => 'vf_individual_deductible', 'sort_order' => 150],
            ['prompt' => 'Deductible met (Individual | Family)?', 'section_key' => 'core_details', 'input_type' => 'currency', 'field_key' => 'vf_individual_deductible_remaining', 'sort_order' => 160],

            ['prompt' => 'Diagnostic & Preventive', 'section_key' => 'coverage_matrix', 'input_type' => 'yes_no', 'field_key' => 'vf_coverage_diagnostic_deductible_applies', 'secondary_field_key' => 'vf_coverage_diagnostic', 'secondary_input_type' => 'percent', 'sort_order' => 10],
            ['prompt' => 'Basic Restorative', 'section_key' => 'coverage_matrix', 'input_type' => 'yes_no', 'field_key' => 'vf_coverage_basic_restorative_deductible_applies', 'secondary_field_key' => 'vf_coverage_basic_restorative', 'secondary_input_type' => 'percent', 'sort_order' => 20],
            ['prompt' => 'Endodontics', 'section_key' => 'coverage_matrix', 'input_type' => 'yes_no', 'field_key' => 'vf_coverage_endodontics_deductible_applies', 'secondary_field_key' => 'vf_coverage_endodontics', 'secondary_input_type' => 'percent', 'sort_order' => 30],
            ['prompt' => 'Periodontics', 'section_key' => 'coverage_matrix', 'input_type' => 'yes_no', 'field_key' => 'vf_coverage_periodontics_deductible_applies', 'secondary_field_key' => 'vf_coverage_periodontics', 'secondary_input_type' => 'percent', 'sort_order' => 40],
            ['prompt' => 'Oral Surgery', 'section_key' => 'coverage_matrix', 'input_type' => 'yes_no', 'field_key' => 'vf_coverage_oral_surgery_deductible_applies', 'secondary_field_key' => 'vf_coverage_oral_surgery', 'secondary_input_type' => 'percent', 'sort_order' => 50],
            ['prompt' => 'Major Restorative', 'section_key' => 'coverage_matrix', 'input_type' => 'yes_no', 'field_key' => 'vf_coverage_major_restorative_deductible_applies', 'secondary_field_key' => 'vf_coverage_major_restorative', 'secondary_input_type' => 'percent', 'sort_order' => 60],
            ['prompt' => 'Orthodontics', 'section_key' => 'coverage_matrix', 'input_type' => 'yes_no', 'field_key' => 'vf_coverage_orthodontics_deductible_applies', 'secondary_field_key' => 'vf_ortho_benefit', 'secondary_input_type' => 'percent', 'sort_order' => 70],

            ['prompt' => 'Is there any Waiting Period? (If YES: Detail of WP)', 'section_key' => 'plan_provisions', 'input_type' => 'textarea', 'field_key' => 'vf_waiting_periods', 'sort_order' => 10],
            ['prompt' => 'Missing tooth clause', 'section_key' => 'plan_provisions', 'input_type' => 'text', 'field_key' => 'vf_missing_tooth_clause', 'sort_order' => 20],
            ['prompt' => 'Crowns are paid on Prep Date or Seat Date?', 'section_key' => 'plan_provisions', 'input_type' => 'text', 'field_key' => 'vf_crowns_paid_on', 'sort_order' => 30],
            ['prompt' => 'Prosthetic Replacement Year/Month', 'section_key' => 'plan_provisions', 'input_type' => 'text', 'field_key' => 'vf_prosthetic_replacement_period', 'sort_order' => 40],
            ['prompt' => 'Coordination of Benefits (Standard or Non-Dup)', 'section_key' => 'plan_provisions', 'input_type' => 'text', 'field_key' => 'vf_cob', 'sort_order' => 50],

            ['prompt' => 'Exams (Write specific code)', 'section_key' => 'history', 'input_type' => 'text', 'field_key' => 'vf_history_exams', 'sort_order' => 10],
            ['prompt' => 'Prophylaxis', 'section_key' => 'history', 'input_type' => 'text', 'field_key' => 'vf_history_prophylaxis', 'sort_order' => 20],
            ['prompt' => 'Bitewings', 'section_key' => 'history', 'input_type' => 'text', 'field_key' => 'vf_history_bitewings', 'sort_order' => 30],
            ['prompt' => 'Full Mouth X-Ray/Panoramic X-Ray', 'section_key' => 'history', 'input_type' => 'text', 'field_key' => 'vf_history_full_mouth_xray', 'sort_order' => 40],
            ['prompt' => 'Any Basic or Major History (Which Might affect the Eligibility)', 'section_key' => 'history', 'input_type' => 'textarea', 'field_key' => 'vf_history_basic_or_major', 'sort_order' => 50],

            ['prompt' => 'Regular Oral Exams (D0120)', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_regular_oral_exams', 'sort_order' => 10],
            ['prompt' => 'Limited Exam (D0140)', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_limited_exam', 'sort_order' => 20],
            ['prompt' => 'Comprehensive Exam (D0150)', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_comprehensive_exam', 'sort_order' => 30],
            ['prompt' => 'Does (D0120, D0140, D0150) Share Freq?', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_exam_shared', 'sort_order' => 40],
            ['prompt' => 'Oral Cancer Screening (D0431)', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_oral_cancer_screening', 'sort_order' => 50],
            ['prompt' => 'Can we bill Oral D0431 in conjunction with D0150 or D0120?', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_oral_cancer_conjunction', 'sort_order' => 60],
            ['prompt' => 'Prophylaxis (D1110/D1120)', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_prophylaxis', 'sort_order' => 70],
            ['prompt' => 'Bitewings X-Ray (D0272/D0274)', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_bitewings', 'sort_order' => 80],
            ['prompt' => 'Full Mouth X-Ray / Panoramic X-Ray (D0210 / D0330) Share Freq', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_full_mouth_pano_shared', 'sort_order' => 90],
            ['prompt' => 'Pa\'s (D0220 / D0230)', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_pas', 'sort_order' => 100],
            ['prompt' => 'Sealants (D1351) & Age Limit', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_sealants', 'sort_order' => 110],
            ['prompt' => 'If yes ask guideline)', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_sealants_guideline', 'sort_order' => 120],
            ['prompt' => 'Caries-Arresting Medicament (D1354) & Age Limit', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_caries_arresting', 'sort_order' => 130],
            ['prompt' => 'Fluoride (D1206/D1208) & Age Limit', 'section_key' => 'frequency_diagnostic_preventative', 'input_type' => 'text', 'field_key' => 'vf_frequency_fluoride', 'sort_order' => 140],

            ['prompt' => 'Scaling & Root Planning (D4341/ D4342)', 'section_key' => 'frequency_basic', 'input_type' => 'text', 'field_key' => 'vf_basic_scaling_root_planing', 'sort_order' => 10],
            ['prompt' => 'Can all 4 quads be done on the same visit? (Guidelines)', 'section_key' => 'frequency_basic', 'input_type' => 'text', 'field_key' => 'vf_basic_all_quads_same_visit', 'sort_order' => 20],
            ['prompt' => 'Perio Maintenance (D4910) Share frq (Yes/No)?', 'section_key' => 'frequency_basic', 'input_type' => 'text', 'field_key' => 'vf_basic_perio_maintenance_share_freq', 'sort_order' => 30],
            ['prompt' => 'FMD (D4355)', 'section_key' => 'frequency_basic', 'input_type' => 'text', 'field_key' => 'vf_basic_fmd', 'sort_order' => 40],
            ['prompt' => 'Root Canal (D3310 / D3320 / D3330)', 'section_key' => 'frequency_basic', 'input_type' => 'text', 'field_key' => 'vf_basic_root_canal', 'sort_order' => 50],
            ['prompt' => 'Simple Extraction (D7140)', 'section_key' => 'frequency_basic', 'input_type' => 'text', 'field_key' => 'vf_basic_simple_extraction', 'sort_order' => 60],
            ['prompt' => 'Surgical Extraction (D7210)', 'section_key' => 'frequency_basic', 'input_type' => 'text', 'field_key' => 'vf_basic_surgical_extraction', 'sort_order' => 70],
            ['prompt' => 'Posterior Composites (D2391/D2392/D2393/D2394)', 'section_key' => 'frequency_basic', 'input_type' => 'text', 'field_key' => 'vf_basic_posterior_composites', 'sort_order' => 80],
            ['prompt' => 'Post Composites downgraded to Amalgam?', 'section_key' => 'frequency_basic', 'input_type' => 'text', 'field_key' => 'vf_basic_composites_downgrade', 'sort_order' => 90],
            ['prompt' => 'Occlusal Guard (D9944/D9945) "Bruxism only OR osseous surgery only"', 'section_key' => 'frequency_basic', 'input_type' => 'text', 'field_key' => 'vf_basic_occlusal_guard', 'sort_order' => 100],

            ['prompt' => 'Crowns (D2740) Downgrade (Yes/No)? If Yes need Code.', 'section_key' => 'frequency_major', 'input_type' => 'text', 'field_key' => 'vf_major_crowns_downgrade', 'sort_order' => 10],
            ['prompt' => 'Porcelain Fused To High Noble Metal Crown (D2750)', 'section_key' => 'frequency_major', 'input_type' => 'text', 'field_key' => 'vf_major_pf_high_noble', 'sort_order' => 20],
            ['prompt' => 'Application of hydroxyapatite regeneration medicament (D2991)', 'section_key' => 'frequency_major', 'input_type' => 'text', 'field_key' => 'vf_major_hydroxyapatite', 'sort_order' => 30],
            ['prompt' => 'Dentures (D5110)', 'section_key' => 'frequency_major', 'input_type' => 'text', 'field_key' => 'vf_major_dentures', 'sort_order' => 40],
            ['prompt' => 'Implant (D6010)', 'section_key' => 'frequency_major', 'input_type' => 'text', 'field_key' => 'vf_major_implant', 'sort_order' => 50],
            ['prompt' => 'Implant abutment (D6057)', 'section_key' => 'frequency_major', 'input_type' => 'text', 'field_key' => 'vf_major_implant_abutment', 'sort_order' => 60],
            ['prompt' => 'Implant Crown (D6058)', 'section_key' => 'frequency_major', 'input_type' => 'text', 'field_key' => 'vf_major_implant_crown', 'sort_order' => 70],
            ['prompt' => 'Bone Graft Performed At The Same Time As A Dental Implant (D6104)', 'section_key' => 'frequency_major', 'input_type' => 'text', 'field_key' => 'vf_major_bone_graft_same_time_implant', 'sort_order' => 80],
            ['prompt' => 'BoneGrafts (D7953)', 'section_key' => 'frequency_major', 'input_type' => 'text', 'field_key' => 'vf_major_bone_grafts', 'sort_order' => 90],

            ['prompt' => 'Orthodontic Retention (D8680)', 'section_key' => 'frequency_orthodontics_benefit', 'input_type' => 'text', 'field_key' => 'vf_ortho_retention', 'sort_order' => 10],
            ['prompt' => 'Ortho Lifetime Maximum?', 'section_key' => 'frequency_orthodontics_benefit', 'input_type' => 'text', 'field_key' => 'vf_ortho_lifetime_maximum', 'sort_order' => 20],
            ['prompt' => 'Remaining Ortho maximum?', 'section_key' => 'frequency_orthodontics_benefit', 'input_type' => 'text', 'field_key' => 'vf_ortho_remaining_maximum', 'sort_order' => 30],
            ['prompt' => 'Ortho Deductibles?', 'section_key' => 'frequency_orthodontics_benefit', 'input_type' => 'text', 'field_key' => 'vf_ortho_deductibles', 'sort_order' => 40],
            ['prompt' => 'Ortho Age Limit?', 'section_key' => 'frequency_orthodontics_benefit', 'input_type' => 'text', 'field_key' => 'vf_ortho_age_limit', 'sort_order' => 50],

            ['prompt' => 'Four Bitewing X-rays', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_bitewings', 'code' => 'D0274', 'sort_order' => 10],
            ['prompt' => 'Panoramic X-ray', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_full_mouth_xray', 'code' => 'D0330', 'sort_order' => 20],
            ['prompt' => 'Full Mouth X-rays', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_full_mouth_xray', 'code' => 'D0210', 'sort_order' => 30],
            ['prompt' => 'Regular Checkup', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_exams', 'code' => 'D0120', 'sort_order' => 40],
            ['prompt' => 'Adult Cleaning / Prophy', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_prophylaxis', 'code' => 'D1110', 'sort_order' => 50],
            ['prompt' => 'Perio Scaling Upper Right Quad', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_basic_or_major', 'code' => 'D4341UR', 'sort_order' => 60],
            ['prompt' => 'Perio Scaling Upper Left Quad', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_basic_or_major', 'code' => 'D4341UL', 'sort_order' => 70],
            ['prompt' => 'Perio Scaling Lower Right Quad', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_basic_or_major', 'code' => 'D4341LR', 'sort_order' => 80],
            ['prompt' => 'Perio Scaling Lower Left Quad', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_basic_or_major', 'code' => 'D4341LL', 'sort_order' => 90],
            ['prompt' => 'Perio Maintenance', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_basic_or_major', 'code' => 'D4910', 'sort_order' => 100],
            ['prompt' => 'Full Mouth Debridement', 'section_key' => 'service_history', 'input_type' => 'text', 'field_key' => 'vf_history_basic_or_major', 'code' => 'D4355', 'sort_order' => 110],

            ['prompt' => 'Verification Date', 'section_key' => 'verification_information', 'input_type' => 'date', 'field_key' => 'vf_verification_date', 'sort_order' => 10],
            ['prompt' => 'Verified By', 'section_key' => 'verification_information', 'input_type' => 'text', 'field_key' => 'vf_verified_by', 'sort_order' => 20],
            ['prompt' => 'Insurance Representative Name', 'section_key' => 'verification_information', 'input_type' => 'text', 'field_key' => 'vf_insurance_representative_name', 'sort_order' => 30],
            ['prompt' => 'Quick Reference', 'section_key' => 'verification_information', 'input_type' => 'text', 'field_key' => 'vf_quick_reference', 'sort_order' => 40],
            ['prompt' => 'Verification Notes', 'section_key' => 'verification_information', 'input_type' => 'textarea', 'field_key' => 'vf_verification_notes', 'sort_order' => 50],
            ['prompt' => 'Queue Notes', 'section_key' => 'verification_information', 'input_type' => 'textarea', 'field_key' => 'notes', 'sort_order' => 60],
            ['prompt' => 'Internal Summary', 'section_key' => 'verification_information', 'input_type' => 'textarea', 'field_key' => 'internal_summary', 'sort_order' => 70],
        ];

        foreach ($questions as $question) {
            DB::table('verification_form_questions')->updateOrInsert(
                [
                    'prompt' => $question['prompt'],
                    'section_key' => $question['section_key'],
                    'is_builtin' => true,
                ],
                array_merge($question, [
                    'form_type' => 'full_form',
                    'help_text' => null,
                    'placeholder' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            );
        }
    }

    public function down(): void
    {
        Schema::table('verification_form_questions', function (Blueprint $table): void {
            $table->dropColumn([
                'field_key',
                'secondary_field_key',
                'secondary_input_type',
                'code',
                'is_builtin',
            ]);
        });
    }
};
