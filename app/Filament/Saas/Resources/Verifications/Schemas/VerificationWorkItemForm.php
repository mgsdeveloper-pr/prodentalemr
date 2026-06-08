<?php

namespace App\Filament\Saas\Resources\Verifications\Schemas;

use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\InsuranceCarrierNetworkProfile;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Models\User;
use App\Models\VerificationProfile;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

class VerificationWorkItemForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $accessibleClinicIds = $user && ! $user->hasFullVerificationClinicAccess()
            ? $user->verificationAccessibleClinicIds()
            : [];

        return $schema
            ->columns(12)
            ->components([
                Hidden::make('created_by')
                    ->default(fn () => auth()->id()),

                Section::make('Queue Control')
                    ->description('Manage ownership, SLA timing, and the current verification queue state.')
                    ->columnSpan(12)
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                TextInput::make('reference_number')
                                    ->label('Reference #')
                                    ->default(fn (): string => BillingWorkItem::generateReferenceNumber())
                                    ->readOnly()
                                    ->dehydrated()
                                    ->columnSpan(3),
                                Select::make('status')
                                    ->options(BillingWorkItem::STATUS_OPTIONS)
                                    ->default(BillingWorkItem::STATUS_PENDING)
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(3),
                                Select::make('outcome_status')
                                    ->label('Verification result')
                                    ->options(BillingWorkItem::OUTCOME_STATUS_OPTIONS)
                                    ->default('pending')
                                    ->native(false)
                                    ->columnSpan(3),
                                Select::make('priority')
                                    ->options(BillingWorkItem::PRIORITY_OPTIONS)
                                    ->default('normal')
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(3),
                                Select::make('assigned_to')
                                    ->label('Assignee')
                                    ->options(fn (Get $get): array => \App\Support\VerificationAutoAssigner::optionList(
                                        filled($get('clinic_id')) ? (int) $get('clinic_id') : null
                                    ))
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(3),
                                Select::make('reviewed_by')
                                    ->label('Reviewer')
                                    ->options(fn (Get $get): array => \App\Support\VerificationAutoAssigner::optionList(
                                        filled($get('clinic_id')) ? (int) $get('clinic_id') : null
                                    ))
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(3),
                                DateTimePicker::make('due_at')
                                    ->label('Due at')
                                    ->seconds(false)
                                    ->columnSpan(3),
                                Select::make('pms_sync_status')
                                    ->label('PMS sync')
                                    ->options(BillingWorkItem::PMS_SYNC_STATUS_OPTIONS)
                                    ->default('pending')
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(3),
                                Select::make('writeback_status')
                                    ->label('Writeback')
                                    ->options(BillingWorkItem::WRITEBACK_STATUS_OPTIONS)
                                    ->default('not_requested')
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(3),
                                Select::make('source')
                                    ->label('Request source')
                                    ->options(BillingWorkItem::SOURCE_OPTIONS)
                                    ->default('appointment_sync')
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(3),
                                Select::make('managed_billing_service_id')
                                    ->label('Managed service')
                                    ->options(fn (): array => ManagedBillingService::query()
                                        ->where('category', 'verification')
                                        ->where('status', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(6),
                                Textarea::make('info_request_reason')
                                    ->label('Information request')
                                    ->helperText('Use this when the clinic must provide missing information before verification can continue.')
                                    ->rows(3)
                                    ->visible(fn ($get): bool => BillingWorkItem::normalizeStatus($get('status')) === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
                                    ->columnSpan(6),
                                Textarea::make('return_reason')
                                    ->label('Rework reason')
                                    ->helperText('Use this when the request is being returned for correction or rework.')
                                    ->rows(3)
                                    ->visible(fn ($get): bool => BillingWorkItem::normalizeStatus($get('status')) === BillingWorkItem::STATUS_RETURNED_FOR_REWORK)
                                    ->columnSpan(6),
                            ]),
                    ]),

                Section::make('Request Snapshot')
                    ->description('Client, practice, patient, and appointment context captured at intake.')
                    ->columnSpan(12)
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Request title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(12),
                                Select::make('organization_id')
                                    ->label('Organization')
                                    ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(3),
                                Select::make('clinic_id')
                                    ->label('Clinic')
                                    ->options(fn (): array => Clinic::query()
                                        ->when($accessibleClinicIds !== [], fn ($query) => $query->whereIn('id', $accessibleClinicIds))
                                        ->orderBy('clinic_name')
                                        ->pluck('clinic_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(3),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->when($accessibleClinicIds !== [], fn ($query) => $query->whereIn('clinic_id', $accessibleClinicIds))
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(3),
                                Select::make('client_service_enrollment_id')
                                    ->label('Enrollment')
                                    ->options(fn (): array => ClientServiceEnrollment::query()
                                        ->when($accessibleClinicIds !== [], fn ($query) => $query->whereIn('clinic_id', $accessibleClinicIds))
                                        ->where('status', 'active')
                                        ->whereHas('managedBillingService', fn ($query) => $query->where('category', 'verification'))
                                        ->get()
                                        ->mapWithKeys(fn (ClientServiceEnrollment $enrollment) => [$enrollment->id => $enrollment->display_title])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(3),
                                Select::make('patient_id')
                                    ->label('Matched patient')
                                    ->options(fn (): array => Patient::query()
                                        ->when($accessibleClinicIds !== [], fn ($query) => $query->whereIn('clinic_id', $accessibleClinicIds))
                                        ->orderBy('last_name')
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Patient $patient) => [$patient->id => $patient->full_name])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(4),
                                Select::make('provider_id')
                                    ->label('Provider')
                                    ->options(fn (): array => Provider::query()
                                        ->with('user')
                                        ->when($accessibleClinicIds !== [], fn ($query) => $query->whereIn('clinic_id', $accessibleClinicIds))
                                        ->orderBy('id')
                                        ->get()
                                        ->mapWithKeys(fn (Provider $provider) => [$provider->id => $provider->display_name])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(4),
                                Select::make('appointment_id')
                                    ->label('Appointment')
                                    ->options(fn (): array => Appointment::query()
                                        ->with('patient')
                                        ->when($accessibleClinicIds !== [], fn ($query) => $query->whereIn('clinic_id', $accessibleClinicIds))
                                        ->orderByDesc('appointment_date')
                                        ->get()
                                        ->mapWithKeys(fn (Appointment $appointment) => [
                                            $appointment->id => collect([
                                                $appointment->appointment_date?->format('M d, Y'),
                                                $appointment->patient?->full_name,
                                                $appointment->start_time,
                                            ])->filter()->implode(' - '),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(4),
                                Select::make('patient_insurance_policy_id')
                                    ->label('Insurance policy')
                                    ->options(fn (): array => PatientInsurancePolicy::query()
                                        ->when($accessibleClinicIds !== [], fn ($query) => $query->whereIn('clinic_id', $accessibleClinicIds))
                                        ->orderBy('insurance_company')
                                        ->get()
                                        ->mapWithKeys(fn (PatientInsurancePolicy $policy) => [
                                            $policy->id => collect([
                                                $policy->insurance_company,
                                                $policy->member_id,
                                            ])->filter()->implode(' - '),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(4),
                                Placeholder::make('request_snapshot_hint')
                                    ->label('Request workflow')
                                    ->content('The intake snapshot above should normally stay stable once the verification enters the queue. The sections below capture the completed verification result.')
                                    ->columnSpan(12),
                            ]),
                    ]),

                Section::make('Patient and Subscriber Information')
                    ->description('Core patient, subscriber, and insurance identity used on the verification call or payer portal.')
                    ->columnSpan(12)
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Select::make('vf_form_type')
                                    ->label('Form type')
                                    ->options(VerificationProfile::FORM_TYPE_OPTIONS)
                                    ->default('full_form')
                                    ->native(false)
                                    ->columnSpan(3),
                                Select::make('vf_is_provider_in_network')
                                    ->label('Provider in network?')
                                    ->options(static::yesNoOptions())
                                    ->native(false)
                                    ->columnSpan(3),
                                TextInput::make('vf_plan_renewal_month')
                                    ->label('Plan renewal month')
                                    ->columnSpan(3),
                                DatePicker::make('vf_future_termination_date')
                                    ->label('Future termination date')
                                    ->native(false)
                                    ->columnSpan(3),
                                TextInput::make('vf_subscriber_name')
                                    ->label('Subscriber name')
                                    ->columnSpan(4),
                                DatePicker::make('vf_subscriber_dob')
                                    ->label('Subscriber DOB')
                                    ->native(false)
                                    ->columnSpan(2),
                                TextInput::make('vf_subscriber_id')
                                    ->label('Subscriber ID')
                                    ->columnSpan(2),
                                TextInput::make('vf_insured_relation')
                                    ->label('Relation with subscriber')
                                    ->columnSpan(2),
                                TextInput::make('vf_cob')
                                    ->label('COB')
                                    ->columnSpan(2),
                                TextInput::make('vf_insurance_provider_name')
                                    ->label('Insurance name')
                                    ->live(onBlur: true)
                                    ->columnSpan(4),
                                TextInput::make('vf_insurance_company_phone_number')
                                    ->label('Insurance phone')
                                    ->columnSpan(2),
                                TextInput::make('vf_insurance_claim_mailing_address')
                                    ->label('Claim mailing address')
                                    ->columnSpan(4),
                                TextInput::make('vf_payer_id')
                                    ->label('Electronic payer ID')
                                    ->live(onBlur: true)
                                    ->columnSpan(2),
                                Placeholder::make('provider_participation_guidance')
                                    ->label('Provider Participation Guidance')
                                    ->content(fn (Get $get): HtmlString => static::networkParticipationGuidance(
                                        (string) ($get('vf_insurance_provider_name') ?? ''),
                                        (string) ($get('vf_payer_id') ?? '')
                                    ))
                                    ->visible(fn (Get $get): bool => filled($get('vf_insurance_provider_name')) || filled($get('vf_payer_id')))
                                    ->columnSpan(12),
                                DatePicker::make('vf_effective_date')
                                    ->label('Effective date')
                                    ->native(false)
                                    ->columnSpan(2),
                                TextInput::make('vf_group_name')
                                    ->label('Employer / Group name')
                                    ->columnSpan(4),
                                TextInput::make('vf_group_number')
                                    ->label('Group number')
                                    ->columnSpan(2),
                                TextInput::make('vf_fee_schedule')
                                    ->label('Fee schedule')
                                    ->suffixAction(fn (Get $get): ?Action => static::feeScheduleReferenceSuffixAction(
                                        (string) ($get('vf_insurance_provider_name') ?? ''),
                                        (string) ($get('vf_payer_id') ?? '')
                                    ), true)
                                    ->columnSpan(2),
                                TextInput::make('vf_network_status')
                                    ->label('Network status')
                                    ->columnSpan(3),
                                TextInput::make('vf_plan_type')
                                    ->label('Plan type')
                                    ->columnSpan(3),
                            ]),
                    ]),

                Section::make('Maximums and Deductibles')
                    ->description('Benefit year maximums, deductible values, and whether deductibles apply to treatment categories.')
                    ->columnSpan(12)
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                TextInput::make('vf_annual_maximum')
                                    ->label('Annual maximum')
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpan(3),
                                TextInput::make('vf_annual_maximum_remaining')
                                    ->label('Remaining maximum')
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpan(3),
                                TextInput::make('vf_individual_deductible')
                                    ->label('Individual deductible')
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpan(3),
                                TextInput::make('vf_individual_deductible_remaining')
                                    ->label('Deductible met / remaining (individual)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpan(3),
                                TextInput::make('vf_family_deductible')
                                    ->label('Family deductible')
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpan(3),
                                TextInput::make('vf_family_deductible_remaining')
                                    ->label('Deductible met / remaining (family)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpan(3),
                                Textarea::make('vf_deductible_applies_notes')
                                    ->label('Deductible applied by category')
                                    ->helperText('Example: Diagnostic/Preventive - No, Basic - Yes, Major - Yes.')
                                    ->rows(3)
                                    ->columnSpan(6),
                                TextInput::make('vf_coverage_diagnostic_deductible_applies')->label('Diagnostic deductible applies')->columnSpan(2),
                                TextInput::make('vf_coverage_basic_restorative_deductible_applies')->label('Basic deductible applies')->columnSpan(2),
                                TextInput::make('vf_coverage_endodontics_deductible_applies')->label('Endodontics deductible applies')->columnSpan(2),
                                TextInput::make('vf_coverage_periodontics_deductible_applies')->label('Periodontics deductible applies')->columnSpan(2),
                                TextInput::make('vf_coverage_oral_surgery_deductible_applies')->label('Oral surgery deductible applies')->columnSpan(2),
                                TextInput::make('vf_coverage_major_restorative_deductible_applies')->label('Major deductible applies')->columnSpan(2),
                                TextInput::make('vf_coverage_orthodontics_deductible_applies')->label('Orthodontics deductible applies')->columnSpan(2),
                                TextInput::make('vf_coverage_diagnostic')->label('Diagnostic & Preventive %')->numeric()->suffix('%')->columnSpan(2),
                                TextInput::make('vf_coverage_basic_restorative')->label('Basic Restorative %')->numeric()->suffix('%')->columnSpan(2),
                                TextInput::make('vf_coverage_endodontics')->label('Endodontics %')->numeric()->suffix('%')->columnSpan(2),
                                TextInput::make('vf_coverage_periodontics')->label('Periodontics %')->numeric()->suffix('%')->columnSpan(2),
                                TextInput::make('vf_coverage_oral_surgery')->label('Oral Surgery %')->numeric()->suffix('%')->columnSpan(2),
                                TextInput::make('vf_coverage_major_restorative')->label('Major Restorative %')->numeric()->suffix('%')->columnSpan(2),
                                TextInput::make('vf_coverage_prosthodontics')->label('Prosthodontics %')->numeric()->suffix('%')->columnSpan(2),
                                TextInput::make('vf_coverage_implant')->label('Implant %')->numeric()->suffix('%')->columnSpan(2),
                                TextInput::make('vf_ortho_lifetime_maximum')->label('Orthodontics % / lifetime max')->columnSpan(2),
                            ]),
                    ]),

                Section::make('Plan Provisions')
                    ->description('High-impact plan rules that affect treatment planning, eligibility, and financial estimation.')
                    ->columnSpan(12)
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Textarea::make('vf_plan_provisions')
                                    ->label('Plan provisions')
                                    ->rows(3)
                                    ->columnSpan(4),
                                Textarea::make('vf_waiting_periods')
                                    ->label('Waiting periods')
                                    ->rows(3)
                                    ->columnSpan(4),
                                Textarea::make('vf_missing_tooth_clause')
                                    ->label('Missing tooth clause')
                                    ->rows(3)
                                    ->columnSpan(4),
                                TextInput::make('vf_crowns_paid_on')
                                    ->label('Crowns paid on')
                                    ->helperText('Prep date or seat date')
                                    ->columnSpan(4),
                                TextInput::make('vf_prosthetic_replacement_period')
                                    ->label('Prosthetic replacement year/month')
                                    ->columnSpan(4),
                                TextInput::make('vf_cob')
                                    ->label('Coordination of benefits')
                                    ->columnSpan(4),
                            ]),
                    ]),

                Section::make('History')
                    ->description('Capture specific service history that can affect eligibility or downgrade logic.')
                    ->columnSpan(12)
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Textarea::make('vf_history_exams')->label('Exams history')->rows(3)->columnSpan(4),
                                Textarea::make('vf_history_prophylaxis')->label('Prophylaxis history')->rows(3)->columnSpan(4),
                                Textarea::make('vf_history_bitewings')->label('Bitewings history')->rows(3)->columnSpan(4),
                                Textarea::make('vf_history_full_mouth_xray')->label('Full mouth / panoramic x-ray history')->rows(3)->columnSpan(6),
                                Textarea::make('vf_history_basic_or_major')->label('Any basic or major history affecting eligibility')->rows(3)->columnSpan(6),
                            ]),
                    ]),

                Section::make('Frequency and Percentage')
                    ->description('Service-level frequencies, age limits, coverage rules, downgrades, and major treatment guidance from the payer.')
                    ->columnSpan(12)
                    ->schema([
                        Section::make('Diagnostic & Preventative')
                            ->compact()
                            ->columns(12)
                            ->schema([
                                Textarea::make('vf_frequency_regular_oral_exams')->label('Regular oral exams (D0120)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_frequency_limited_exam')->label('Limited exam (D0140)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_frequency_comprehensive_exam')->label('Comprehensive exam (D0150)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_frequency_exam_shared')->label('Do D0120/D0140/D0150 share frequency?')->rows(2)->columnSpan(4),
                                Textarea::make('vf_frequency_oral_cancer_screening')->label('Oral cancer screening (D0431)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_frequency_oral_cancer_conjunction')->label('Can D0431 bill with D0150/D0120?')->rows(2)->columnSpan(4),
                                Textarea::make('vf_frequency_prophylaxis')->label('Prophylaxis (D1110/D1120)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_frequency_bitewings')->label('Bitewings x-ray (D0272/D0274)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_frequency_full_mouth_pano_shared')->label('FMX / Pano share frequency')->rows(2)->columnSpan(4),
                                Textarea::make('vf_frequency_pas')->label('PAs (D0220 / D0230)')->rows(2)->columnSpan(3),
                                Textarea::make('vf_frequency_sealants')->label('Sealants (D1351) & age limit')->rows(2)->columnSpan(3),
                                Textarea::make('vf_frequency_sealants_guideline')->label('Sealants guideline')->rows(2)->columnSpan(3),
                                Textarea::make('vf_frequency_caries_arresting')->label('Caries-arresting medicament (D1354)')->rows(2)->columnSpan(3),
                                Textarea::make('vf_frequency_fluoride')->label('Fluoride (D1206 / D1208) & age limit')->rows(2)->columnSpan(6),
                            ]),
                        Section::make('Basic')
                            ->compact()
                            ->columns(12)
                            ->schema([
                                Textarea::make('vf_basic_scaling_root_planing')->label('Scaling & root planing (D4341 / D4342)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_basic_all_quads_same_visit')->label('Can all 4 quads be done same visit?')->rows(2)->columnSpan(4),
                                Textarea::make('vf_basic_perio_maintenance_share_freq')->label('Perio maintenance (D4910) share frequency?')->rows(2)->columnSpan(4),
                                Textarea::make('vf_basic_fmd')->label('FMD (D4355)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_basic_root_canal')->label('Root canal (D3310 / D3320 / D3330)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_basic_simple_extraction')->label('Simple extraction (D7140)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_basic_surgical_extraction')->label('Surgical extraction (D7210)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_basic_posterior_composites')->label('Posterior composites (D2391-D2394)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_basic_composites_downgrade')->label('Composites downgraded to amalgam?')->rows(2)->columnSpan(4),
                                Textarea::make('vf_basic_occlusal_guard')->label('Occlusal guard (D9944 / D9945)')->rows(2)->columnSpan(12),
                            ]),
                        Section::make('Major')
                            ->compact()
                            ->columns(12)
                            ->schema([
                                Textarea::make('vf_major_crowns_downgrade')->label('Crowns (D2740) downgrade?')->rows(2)->columnSpan(4),
                                Textarea::make('vf_major_pf_high_noble')->label('PFM high noble crown (D2750)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_major_hydroxyapatite')->label('Hydroxyapatite regeneration medicament (D2991)')->rows(2)->columnSpan(4),
                                Textarea::make('vf_major_dentures')->label('Dentures (D5110)')->rows(2)->columnSpan(3),
                                Textarea::make('vf_major_implant')->label('Implant (D6010)')->rows(2)->columnSpan(3),
                                Textarea::make('vf_major_implant_abutment')->label('Implant abutment (D6057)')->rows(2)->columnSpan(3),
                                Textarea::make('vf_major_implant_crown')->label('Implant crown (D6058)')->rows(2)->columnSpan(3),
                                Textarea::make('vf_major_bone_graft_same_time_implant')->label('Bone graft with implant (D6104)')->rows(2)->columnSpan(6),
                                Textarea::make('vf_major_bone_grafts')->label('Bone grafts (D7953)')->rows(2)->columnSpan(6),
                            ]),
                        Section::make('Orthodontics')
                            ->compact()
                            ->columns(12)
                            ->schema([
                                Textarea::make('vf_ortho_benefit')->label('Orthodontics benefit')->rows(2)->columnSpan(4),
                                Textarea::make('vf_ortho_retention')->label('Orthodontic retention (D8680)')->rows(2)->columnSpan(4),
                                TextInput::make('vf_ortho_lifetime_maximum')->label('Ortho lifetime maximum')->columnSpan(2),
                                TextInput::make('vf_ortho_remaining_maximum')->label('Remaining ortho maximum')->columnSpan(2),
                                TextInput::make('vf_ortho_deductibles')->label('Ortho deductibles')->columnSpan(3),
                                TextInput::make('vf_ortho_age_limit')->label('Ortho age limit')->columnSpan(3),
                                Textarea::make('vf_ortho_information')->label('Additional ortho information')->rows(3)->columnSpan(6),
                            ]),
                    ]),

                Section::make('Verification Information')
                    ->description('Final verification outcome, handoff notes, and payer contact reference.')
                    ->columnSpan(12)
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                DatePicker::make('vf_verification_date')
                                    ->label('Verification date')
                                    ->native(false)
                                    ->columnSpan(3),
                                TextInput::make('vf_verified_by')
                                    ->label('Verified by')
                                    ->columnSpan(3),
                                TextInput::make('vf_insurance_representative_name')
                                    ->label('Insurance representative')
                                    ->columnSpan(3),
                                TextInput::make('vf_quick_reference')
                                    ->label('Quick reference')
                                    ->columnSpan(3),
                                Textarea::make('vf_verification_notes')
                                    ->label('Verification notes')
                                    ->rows(5)
                                    ->columnSpan(6),
                                Textarea::make('notes')
                                    ->label('Queue notes')
                                    ->rows(5)
                                    ->columnSpan(3),
                                Textarea::make('internal_summary')
                                    ->label('Internal summary')
                                    ->rows(5)
                                    ->columnSpan(3),
                            ]),
                    ]),
            ]);
    }

    protected static function yesNoOptions(): array
    {
        return [
            '1' => 'Yes',
            '0' => 'No',
        ];
    }

    protected static function networkParticipationGuidance(?string $carrierName, ?string $payerId = null): HtmlString
    {
        $profile = InsuranceCarrierNetworkProfile::resolveFor($carrierName, $payerId);

        if (! $profile) {
            return new HtmlString(
                '<div style="border: 1px dashed #cbd5e1; border-radius: 16px; background: #f8fafc; padding: 14px 16px; font-size: 13px; line-height: 1.7; color: #64748b;">'
                . 'No saved participating / non-participating guidance exists for this payer yet. Add it from <strong>Verification &gt; Provider Participation</strong> if this carrier should have reusable network guidance.'
                . '</div>'
            );
        }

        $rows = collect($profile->summaryRows())
            ->map(fn (array $row): string => '<div style="padding: 10px 0; border-top: 1px solid #e2e8f0; display: grid; grid-template-columns: minmax(180px, 220px) minmax(0, 1fr); gap: 12px; align-items: start;">'
                . '<div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #475569;">' . e($row['label']) . '</div>'
                . '<div style="font-size: 13px; line-height: 1.65; color: #334155;">' . nl2br(e((string) $row['value'])) . '</div>'
                . '</div>')
            ->implode('');

        $sourceDocumentReference = static::sourceDocumentAction($profile, true);
        $feeScheduleReference = static::feeScheduleReferenceAction($profile, true);

        return new HtmlString(
            '<div style="border: 1px solid #c7d2fe; border-radius: 18px; background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%); padding: 16px 18px;">'
            . '<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 10px; flex-wrap: wrap;">'
            . '<div>'
            . '<div style="font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #4338ca;">Managed Participation Guidance</div>'
            . '<div style="margin-top: 6px; font-size: 16px; font-weight: 800; color: #0f172a;">' . e($profile->insuranceCarrier?->insurance_name ?? 'Matched Payer') . '</div>'
            . '</div>'
            . '<span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid #c7d2fe; background: #ffffff; color: #4338ca; font-size: 11px; font-weight: 800;">'
            . e($profile->insuranceCarrier?->payer_id ?: 'No payer ID')
            . '</span>'
            . '</div>'
            . '<div style="font-size: 12px; line-height: 1.7; color: #475569;">Use this as a reusable payer baseline, then confirm the actual plan-specific network behavior during the verification call or portal review.</div>'
            . $sourceDocumentReference
            . $feeScheduleReference
            . $rows
            . '</div>'
        );
    }

    protected static function feeScheduleReferenceAction(InsuranceCarrierNetworkProfile $profile, bool $compact = false): string
    {
        if (! $profile->hasFeeScheduleReference()) {
            return '';
        }

        $name = e($profile->feeScheduleReferenceName() ?: 'Saved fee schedule reference');
        $url = $profile->feeScheduleReferenceUrl();
        $wrapperStyle = $compact
            ? 'margin-top: 12px; padding-top: 12px; border-top: 1px solid #c7d2fe; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;'
            : 'margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;';

        $action = filled($url)
            ? static::feeScheduleReferenceButton($name, $url)
            : '<span style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 12px; font-weight: 800;">Reference name only</span>';

        return '<div style="' . $wrapperStyle . '">'
            . '<div>'
            . '<div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #475569;">Current Fee Schedule</div>'
            . '<div style="margin-top: 6px; font-size: 13px; font-weight: 800; line-height: 1.5; color: #0f172a;">' . $name . '</div>'
            . '</div>'
            . $action
            . '</div>';
    }

    protected static function feeScheduleReferenceSuffixAction(?string $carrierName, ?string $payerId = null): ?Action
    {
        $profile = InsuranceCarrierNetworkProfile::resolveFor($carrierName, $payerId);

        if (! $profile || ! $profile->hasFeeScheduleReference()) {
            return null;
        }

        $url = $profile->feeScheduleReferenceUrl();

        if (! filled($url)) {
            return null;
        }

        $name = $profile->feeScheduleReferenceName() ?: 'Saved fee schedule reference';

        return Action::make('viewFeeScheduleReference')
            ->label('View fee schedule reference')
            ->icon('heroicon-m-information-circle')
            ->color('gray')
            ->tooltip($name)
            ->modalHeading('Fee Schedule Reference')
            ->modalDescription('Review the current fee schedule reference without leaving the verification workflow.')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->extraModalFooterActions([
                Action::make('openFeeScheduleReferenceInNewTab')
                    ->label('Open in new tab')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url($url, true),
            ])
            ->modalContent(fn (): HtmlString => static::documentViewerModalContent($name, $url));
    }

    protected static function sourceDocumentAction(InsuranceCarrierNetworkProfile $profile, bool $compact = false): string
    {
        if (! $profile->hasSourceDocument()) {
            return '';
        }

        $name = e($profile->sourceDocumentName() ?: 'Saved source document');
        $url = $profile->sourceDocumentUrl();
        $typeLabel = e($profile->sourceDocumentTypeLabel() ?: 'Reference Document');
        $effectiveDate = $profile->source_document_effective_date?->format('M d, Y');
        $wrapperStyle = $compact
            ? 'margin-top: 12px; padding-top: 12px; border-top: 1px solid #c7d2fe; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;'
            : 'margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;';

        $meta = $effectiveDate
            ? '<div style="margin-top: 4px; font-size: 12px; line-height: 1.6; color: #64748b;">' . $typeLabel . ' &middot; Effective ' . e($effectiveDate) . '</div>'
            : '<div style="margin-top: 4px; font-size: 12px; line-height: 1.6; color: #64748b;">' . $typeLabel . '</div>';

        $action = filled($url)
            ? static::documentViewerButton('View source PDF', 'Source Document', $name, $url)
            : '<span style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 12px; font-weight: 800;">Name only</span>';

        return '<div style="' . $wrapperStyle . '">'
            . '<div>'
            . '<div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #475569;">Source Document</div>'
            . '<div style="margin-top: 6px; font-size: 13px; font-weight: 800; line-height: 1.5; color: #0f172a;">' . $name . '</div>'
            . $meta
            . '</div>'
            . $action
            . '</div>';
    }

    protected static function documentViewerModalContent(string $documentName, string $url): HtmlString
    {
        return new HtmlString(
            '<div style="display: flex; flex-direction: column; gap: 16px;">'
            . '<div style="border: 1px solid #e2e8f0; border-radius: 18px; background: #f8fafc; padding: 14px 16px;">'
            . '<div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Reference</div>'
            . '<div style="margin-top: 6px; font-size: 16px; font-weight: 800; line-height: 1.5; color: #0f172a;">' . e($documentName) . '</div>'
            . '</div>'
            . '<div style="border: 1px solid #dbe4ee; border-radius: 22px; overflow: hidden; background: #0f172a;">'
            . '<iframe src="' . e($url) . '" title="' . e($documentName) . '" style="width: 100%; height: 72vh; border: 0; background: #ffffff;"></iframe>'
            . '</div>'
            . '</div>'
        );
    }

    protected static function feeScheduleReferenceButton(string $name, string $url, bool $iconOnly = false): string
    {
        $trigger = $iconOnly
            ? '<button type="button" @click="open = true" title="' . $name . '" style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 14px; border: 1px solid #c7d2fe; background: #ffffff; color: #4338ca; font-size: 18px; cursor: pointer;">&#128065;</button>'
            : '<button type="button" @click="open = true" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; border: 1px solid #c7d2fe; background: #ffffff; color: #4338ca; font-size: 12px; font-weight: 800; cursor: pointer;">&#128065; View current schedule</button>';

        return static::documentViewerButton(
            $trigger,
            'Fee Schedule Reference',
            $name,
            $url,
            $iconOnly,
            'Review the current fee schedule reference without leaving the verification workflow.'
        );
    }

    protected static function documentViewerButton(
        string $triggerLabel,
        string $viewerLabel,
        string $documentName,
        string $url,
        bool $customTrigger = false,
        ?string $description = null
    ): string {
        $viewerId = 'document-viewer-' . substr(md5($viewerLabel . '|' . $documentName . '|' . $url), 0, 12);
        $trigger = $customTrigger
            ? $triggerLabel
            : '<button type="button" @click="open = true" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; border: 1px solid #c7d2fe; background: #ffffff; color: #4338ca; font-size: 12px; font-weight: 800; cursor: pointer;">&#128065; ' . e($triggerLabel) . '</button>';

        return '<div x-data="{ open: false }" style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">'
            . $trigger
            . ($customTrigger ? '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 12px; font-weight: 800; text-decoration: none;">Open in new tab</a>' : '')
            . '<div x-cloak x-show="open" x-transition.opacity style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 28px; background: rgba(15, 23, 42, 0.68);">'
            . '<div id="' . e($viewerId) . '" @click.away="open = false" style="width: min(1080px, 100%); max-height: 88vh; border-radius: 24px; overflow: hidden; background: #ffffff; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28); display: flex; flex-direction: column;">'
            . '<div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 18px 22px; border-bottom: 1px solid #e2e8f0;">'
            . '<div>'
            . '<div style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">' . e($viewerLabel) . '</div>'
            . '<div style="margin-top: 6px; font-size: 18px; font-weight: 800; line-height: 1.4; color: #0f172a;">' . $documentName . '</div>'
            . '</div>'
            . '<button type="button" @click="open = false" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 20px; cursor: pointer;">&times;</button>'
            . '</div>'
            . '<div style="padding: 16px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; background: #f8fafc;">'
            . '<div style="font-size: 13px; line-height: 1.7; color: #64748b;">' . e($description ?: 'Review the saved document without leaving the verification workflow.') . '</div>'
            . '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; border: 1px solid #c7d2fe; background: #ffffff; color: #4338ca; font-size: 12px; font-weight: 800; text-decoration: none;">Download / open separately</a>'
            . '</div>'
            . '<div style="flex: 1 1 auto; min-height: 68vh; background: #0f172a;">'
            . '<iframe src="' . e($url) . '" title="' . $documentName . '" style="width: 100%; height: 68vh; border: 0; background: #ffffff;"></iframe>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }
}
