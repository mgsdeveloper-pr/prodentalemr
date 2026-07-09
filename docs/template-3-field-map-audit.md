# Template 3 Field Map Audit

Last reviewed: 2026-07-07

This audit maps Template 3 verification form fields to their storage and reload paths. The goal is to keep the verification flow stable:

form input -> normalize -> save profile fields -> save custom answers -> save coverage rows -> reload from DB.

## Storage Pipeline

Template 3 currently uses four storage lanes.

| Lane | UI field shape | Saves to | Save method | Reload method |
| --- | --- | --- | --- | --- |
| Built-in verification fields | `data.vf_*` | `verification_profiles.*` after removing `vf_` prefix | `buildTemplateThreeSavePayload()` -> `splitVerificationProfileData()` -> `afterSave()` -> `verificationProfile()->updateOrCreate()` | `mutateFormDataBeforeFill()` loads `verificationProfile` attributes back into `data.vf_*` |
| Work item fields | native `billing_work_items` keys | `billing_work_items.*` | `mutateFormDataBeforeSave()` returns filtered work item data | Filament resource record fill |
| Custom template questions | `data.custom_question_{id}` and `data.custom_question_note_{id}` | `verification_form_answers.answer_value`, `verification_form_answers.note_value` | `extractTemplateThreeCustomAnswerPayloads()` -> `syncVerificationFormAnswers()` | `mutateFormDataBeforeFill()` loads answers back into matching custom keys |
| Frequency / ADA-CDT rows | `codeCoverageData.{index}.*` | `verification_coverage_codes.*` | `normalizeCodeCoverageRows()` -> `syncVerificationCoverageCodes()` | `resolveCodeCoverageRows()` loads saved rows and merges with configured template rows |

## Question Builder Safety Model

Template 3 question configuration now follows a two-level model.

| Builder concept | Storage | Scope rule | Runtime result |
| --- | --- | --- | --- |
| Master question | `verification_form_questions` with `clinic_id = null` | Global when `organization_id = null`; organization master when `organization_id` is set | Inherited by matching clinics and visible in the builder preview |
| Clinic override question | `verification_form_questions` with `clinic_id` set | Applies only to that clinic | Shows alongside inherited master questions for that clinic |
| Template section | Built-in `template_3_*` section keys plus `verification_template_sections` | Built-ins are universal; custom sections are clinic-scoped today | Question Builder places questions into section/sub-section hierarchy |
| Formal question row | `input_type` such as `text`, `select`, `multi_select`, `date`, etc. | Can be master or clinic-specific | Saves as `verification_form_answers` when it is not bound to a fixed profile field |
| Frequency ADA/CDT row | `input_type = frequency_row`, `code` filled from `ada_procedure_codes` | Must live under `template_3_frequency_*` sections | Renders as code-level coverage row and saves through `verification_coverage_codes` |
| Frequency formal row | `input_type = frequency_row`, `code = null` | Must live under `template_3_frequency_*` sections | Renders as a plain frequency/coverage question row |

Builder guardrails:

- Template 3 section keys map directly to the field binding audit groups, so new questions no longer rely on legacy Template 1/2 section names.
- A blank clinic creates a master question. Selecting a clinic creates a clinic-specific customization.
- The Created Questions preview includes inherited master questions and clinic-specific questions so admins can see the actual question set a clinic will receive.
- Frequency rows always save as `frequency_row`; the selected response mode controls whether the form asks for current or advanced response fields.

## Fixed Template 3 Fields

### Patient & Subscriber Information

| Label | Field key | Input type | DB target | Save path | Reload path | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Patient Name | `data.vf_patient_full_name` | Text | `verification_profiles.patient_full_name` | `vf_*` profile split | profile -> `data.vf_patient_full_name` | Also used for Self relationship subscriber copy |
| Date of Birth | `data.vf_patient_dob` | Date | `verification_profiles.patient_dob` | date normalization -> profile split | profile/patient fallback -> browser date value | Browser-safe `Y-m-d` display path is required |
| Member ID | `data.vf_patient_identifier` | Text | `verification_profiles.patient_identifier` | `vf_*` profile split | profile -> `data.vf_patient_identifier` | Also used for Self relationship subscriber ID copy |
| Relationship | `data.vf_insured_relation` | Select | `verification_profiles.insured_relation` | live relationship update + profile split | profile -> `data.vf_insured_relation` | If `Self`, subscriber name/DOB/ID are normalized from patient values |
| Subscriber Name | `data.vf_subscriber_name` | Text | `verification_profiles.subscriber_name` | self-normalization + profile split | profile/insurance fallback -> `data.vf_subscriber_name` | Auto-filled for Self |
| Subscriber DOB | `data.vf_subscriber_dob` | Date | `verification_profiles.subscriber_dob` | self-normalization + date normalization + profile split | profile/insurance/snapshot fallback -> browser date value | For Self, should display patient DOB first |
| Subscriber ID | `data.vf_subscriber_id` | Text | `verification_profiles.subscriber_id` | self-normalization + profile split | profile -> `data.vf_subscriber_id` | Auto-filled for Self |
| COB / Coverage Role | `data.vf_coverage_role` | Select | `verification_profiles.coverage_role` | `normalizeTemplateThreeCobFields()` + profile split | profile -> `data.vf_coverage_role` | Split from old `vf_cob`; separate from Plan Provisions COB |

### Insurance Information

| Label | Field key | Input type | DB target | Save path | Reload path | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Insurance Provider | `data.vf_insurance_provider_name` | Select | `verification_profiles.insurance_provider_name` | profile split | profile/insurance fallback | Options come from `getInsuranceCarrierOptions()` |
| Group Number | `data.vf_group_number` | Text | `verification_profiles.group_number` | profile split | profile -> `data.vf_group_number` |  |
| Plan Type | `data.vf_plan_type` | Select | `verification_profiles.plan_type` | profile split | profile -> `data.vf_plan_type` | Current options: PPO, DHMO, Indemnity |
| Network Status | `data.vf_network_status` | Select | `verification_profiles.network_status` | profile split | profile -> `resolveNetworkStatus()` | Values are normalized from network status/provider-in-network |
| Effective Date | `data.vf_effective_date` | Date | `verification_profiles.effective_date` | date normalization + profile split | profile/insurance fallback -> browser date value | Browser-safe `Y-m-d` display path is required |
| Future Termination Date | `data.vf_future_termination_date` | Date | `verification_profiles.future_termination_date` | date normalization + profile split | profile/insurance termination fallback -> browser date value | Browser-safe `Y-m-d` display path is required |
| Plan Renewal Month | `data.vf_plan_renewal_month` | Text | `verification_profiles.plan_renewal_month` | profile split | profile -> `data.vf_plan_renewal_month` | Expected format: `MM/YYYY` |
| Claims Address | `data.vf_insurance_claim_mailing_address` | Text | `verification_profiles.insurance_claim_mailing_address` | profile split | profile -> `data.vf_insurance_claim_mailing_address` |  |
| Payer ID | `data.vf_payer_id` | Text | `verification_profiles.payer_id` | profile split | profile/insurance fallback |  |
| Phone Number | `data.vf_insurance_company_phone_number` | Text | `verification_profiles.insurance_company_phone_number` | profile split | profile/insurance fallback |  |
| Fee Schedule | `data.vf_fee_schedule` | Text + reference info button | `verification_profiles.fee_schedule` | profile split | profile -> `data.vf_fee_schedule` | Uses Template 1-like reference viewer |
| Employer / Group Name | `data.vf_group_name` | Text | `verification_profiles.group_name` | profile split | profile -> `data.vf_group_name` |  |

### Maximums & Deductibles

| Label | Field key | Input type | DB target | Save path | Reload path | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Annual Maximum on the Plan? | `data.vf_annual_maximum` | Number decimal | `verification_profiles.annual_maximum` | decimal normalization + profile split | profile -> `data.vf_annual_maximum` | Stored as decimal |
| Annual Maximum Remaining? | `data.vf_annual_maximum_remaining` | Number decimal | `verification_profiles.annual_maximum_remaining` | decimal normalization + profile split | profile -> `data.vf_annual_maximum_remaining` | Stored as decimal |
| Annual Deductible - Individual | `data.vf_individual_deductible` | Number decimal | `verification_profiles.individual_deductible` | decimal normalization + profile split | profile -> `data.vf_individual_deductible` | Stored as decimal |
| Individual Deductible Remaining | `data.vf_individual_deductible_remaining` | Number decimal | `verification_profiles.individual_deductible_remaining` | decimal normalization + profile split | profile -> `data.vf_individual_deductible_remaining` | Stored as decimal |
| Annual Deductible - Family | `data.vf_family_deductible` | Number decimal | `verification_profiles.family_deductible` | decimal normalization + profile split | profile -> `data.vf_family_deductible` | Stored as decimal |
| Family Deductible Remaining | `data.vf_family_deductible_remaining` | Number decimal | `verification_profiles.family_deductible_remaining` | decimal normalization + profile split | profile -> `data.vf_family_deductible_remaining` | Stored as decimal |

### Deductible & Coverage Category

| Label | Field key | Input type | DB target | Save path | Reload path | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Diagnostic & Preventive DED Applied? | `data.vf_coverage_diagnostic_deductible_applies` | Select | `verification_profiles.coverage_diagnostic_deductible_applies` | profile split | profile -> `data.vf_coverage_diagnostic_deductible_applies` | Yes/No |
| Diagnostic & Preventive % | `data.vf_coverage_diagnostic` | Number/text percent | `verification_profiles.coverage_diagnostic` | integer normalization + profile split | profile -> `data.vf_coverage_diagnostic` | Stored as integer |
| Basic Restorative DED Applied? | `data.vf_coverage_basic_restorative_deductible_applies` | Select | `verification_profiles.coverage_basic_restorative_deductible_applies` | profile split | profile -> key | Yes/No |
| Basic Restorative % | `data.vf_coverage_basic_restorative` | Number/text percent | `verification_profiles.coverage_basic_restorative` | integer normalization + profile split | profile -> key | Stored as integer |
| Endodontics DED Applied? | `data.vf_coverage_endodontics_deductible_applies` | Select | `verification_profiles.coverage_endodontics_deductible_applies` | profile split | profile -> key | Yes/No |
| Endodontics % | `data.vf_coverage_endodontics` | Number/text percent | `verification_profiles.coverage_endodontics` | integer normalization + profile split | profile -> key | Stored as integer |
| Periodontics DED Applied? | `data.vf_coverage_periodontics_deductible_applies` | Select | `verification_profiles.coverage_periodontics_deductible_applies` | profile split | profile -> key | Yes/No |
| Periodontics % | `data.vf_coverage_periodontics` | Number/text percent | `verification_profiles.coverage_periodontics` | integer normalization + profile split | profile -> key | Stored as integer |
| Oral Surgery DED Applied? | `data.vf_coverage_oral_surgery_deductible_applies` | Select | `verification_profiles.coverage_oral_surgery_deductible_applies` | profile split | profile -> key | Yes/No |
| Oral Surgery % | `data.vf_coverage_oral_surgery` | Number/text percent | `verification_profiles.coverage_oral_surgery` | integer normalization + profile split | profile -> key | Stored as integer |
| Major Restorative DED Applied? | `data.vf_coverage_major_restorative_deductible_applies` | Select | `verification_profiles.coverage_major_restorative_deductible_applies` | profile split | profile -> key | Yes/No |
| Major Restorative % | `data.vf_coverage_major_restorative` | Number/text percent | `verification_profiles.coverage_major_restorative` | integer normalization + profile split | profile -> key | Stored as integer |
| Orthodontics DED Applied? | `data.vf_coverage_orthodontics_deductible_applies` | Select | `verification_profiles.coverage_orthodontics_deductible_applies` | profile split | profile -> key | Yes/No |
| Orthodontics % | `data.vf_ortho_benefit` | Text | `verification_profiles.ortho_benefit` | profile split | profile -> `data.vf_ortho_benefit` | Text field, not integer-normalized |
| Coverage Notes | `data.vf_deductible_applies_notes` | Textarea | `verification_profiles.deductible_applies_notes` | profile split | profile -> `data.vf_deductible_applies_notes` |  |

### Plan Provisions

| Label | Field key | Input type | DB target | Save path | Reload path | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Waiting Period Answer | `waitingPeriodAnswer` | Select live | derived into `verification_profiles.waiting_periods` | `formatWaitingPeriodDetails()` into `data.vf_waiting_periods` | `initializeWaitingPeriodDetails()` parses `data.vf_waiting_periods` | Not a direct DB column |
| Waiting Period Details | `waitingPeriodDetails.*` | Repeating rows | derived into `verification_profiles.waiting_periods` | formatted text summary | parsed text summary | Detail rows are not stored as JSON/table today |
| Missing Tooth Clause | `data.vf_missing_tooth_clause` | Select | `verification_profiles.missing_tooth_clause` | profile split | profile -> key |  |
| Crowns are paid on Prep Date or Seat Date? | `data.vf_crowns_paid_on` | Select | `verification_profiles.crowns_paid_on` | profile split | profile -> key |  |
| Prosthetic Replacement Year / Month | `data.vf_prosthetic_replacement_period` | Text | `verification_profiles.prosthetic_replacement_period` | profile split | profile -> key |  |
| Coordination of Benefits | `data.vf_coordination_of_benefits` | Select | `verification_profiles.coordination_of_benefits` | `normalizeTemplateThreeCobFields()` + profile split | profile -> key | Separate from `vf_coverage_role` |
| Plan Provision Notes | `data.vf_plan_provisions` | Textarea | `verification_profiles.plan_provisions` | profile split | profile -> key |  |

### Service History

| Label | Field key | Input type | DB target | Save path | Reload path | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Exams | `data.vf_history_exams` | Text | `verification_profiles.history_exams` | profile split | profile -> key |  |
| Prophylaxis | `data.vf_history_prophylaxis` | Text | `verification_profiles.history_prophylaxis` | profile split | profile -> key |  |
| Bitewings | `data.vf_history_bitewings` | Text | `verification_profiles.history_bitewings` | profile split | profile -> key |  |
| Full Mouth X-Ray / Panoramic X-Ray | `data.vf_history_full_mouth_xray` | Text | `verification_profiles.history_full_mouth_xray` | profile split | profile -> key |  |
| Other Major History | `data.vf_history_basic_or_major` | Textarea | `verification_profiles.history_basic_or_major` | profile split | profile -> key |  |

### Frequency & Percentage

These rows are dynamic and are configured through Template Question Management with `input_type = frequency_row`.

| UI value | Field key | Input type | DB target | Save path | Reload path | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Code system | `codeCoverageData.{i}.code_system` | Hidden/default | `verification_coverage_codes.code_system` | `normalizeCodeCoverageRows()` -> coverage sync | `resolveCodeCoverageRows()` | Defaults to `ada` |
| Category | `codeCoverageData.{i}.category` | Derived text | `verification_coverage_codes.category` | coverage sync | coverage reload/merge | General, Basic, Major, Orthodontics |
| ADA/CDT Code | `codeCoverageData.{i}.code` | Derived text | `verification_coverage_codes.code` | coverage sync | coverage reload/merge | Comes from template question config |
| Description | `codeCoverageData.{i}.description` | Derived text | `verification_coverage_codes.description` | coverage sync | coverage reload/merge | Comes from template question config |
| Coverage Status | `codeCoverageData.{i}.coverage_status` | Select where enabled | `verification_coverage_codes.coverage_status` | coverage sync | coverage reload | Current UI mainly uses percent/frequency/pre-auth/notes |
| % | `codeCoverageData.{i}.coverage_percent` | Number | `verification_coverage_codes.coverage_percent` | coverage sync | coverage reload | Stored as decimal |
| Frequency | `codeCoverageData.{i}.frequency` | Text | `verification_coverage_codes.frequency` | coverage sync | coverage reload |  |
| Age Limit | `codeCoverageData.{i}.age_limit` | Text where advanced | `verification_coverage_codes.age_limit` | coverage sync | coverage reload | Advanced response mode |
| Waiting Period | `codeCoverageData.{i}.waiting_period` | Text where advanced | `verification_coverage_codes.waiting_period` | coverage sync | coverage reload | Advanced response mode |
| Service History | `codeCoverageData.{i}.service_history` | Text where advanced | `verification_coverage_codes.service_history` | coverage sync | coverage reload | Advanced response mode |
| Pre-Auth Required | `codeCoverageData.{i}.pre_auth_required` | Select | `verification_coverage_codes.pre_auth_required` | coverage sync | coverage reload | Clears pre-auth detail unless Yes |
| Pre-Auth Details | `codeCoverageData.{i}.pre_auth_details` | Text where advanced | `verification_coverage_codes.pre_auth_details` | coverage sync | coverage reload | Advanced response mode |
| Downgrade Applies | `codeCoverageData.{i}.downgrade_applies` | Select where advanced | `verification_coverage_codes.downgrade_applies` | coverage sync | coverage reload | Clears downgrade target unless Yes |
| Downgrade To | `codeCoverageData.{i}.downgrade_to` | Text where advanced | `verification_coverage_codes.downgrade_to` | coverage sync | coverage reload | Advanced response mode |
| Payment Guideline | `codeCoverageData.{i}.payment_guideline` | Textarea where advanced | `verification_coverage_codes.payment_guideline` | coverage sync | coverage reload | Advanced response mode |
| Notes | `codeCoverageData.{i}.notes` | Text | `verification_coverage_codes.notes` | coverage sync | coverage reload |  |

### Verification Information

| Label | Field key | Input type | DB target | Save path | Reload path | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Reference Number | record value | Readonly | `billing_work_items.reference_number` | work item save path | record reload | Not a `vf_*` profile field |
| Insurance Representative | `data.vf_insurance_representative_name` | Text | `verification_profiles.insurance_representative_name` | profile split | profile -> key |  |
| Verified By | `data.vf_verified_by` | Readonly/autofill | `verification_profiles.verified_by` | profile split | profile/auth fallback |  |
| Verification Date | `data.vf_verification_date` | Date/readonly depending UI | `verification_profiles.verification_date` | date normalization + profile split | profile -> browser date value |  |
| Additional Information | `data.vf_verification_notes` | Textarea | `verification_profiles.verification_notes` | profile split | profile -> key |  |

## Context / Sidebar Only Fields

These appear in Quick Reference or sidebar groups. They are not direct Template 3 inputs unless also listed above.

| Display value | Source |
| --- | --- |
| Organization | `billing_work_items.organization` |
| Clinic | `billing_work_items.clinic` |
| Location | `billing_work_items.location` or profile/location fallback |
| Enrollment | `billing_work_items.clientServiceEnrollment` |
| Provider / Doctor | `billing_work_items.provider.user` or `verification_profiles.provider_name` |
| Provider NPI | provider/profile context |
| Practice NPI | clinic/profile context |
| Insurance / TPA | `verification_profiles.insurance_provider_name` or policy context |
| Insurance / TPA Phone | `verification_profiles.insurance_company_phone_number` or policy/carrier context |

## Current Risk Notes

1. `vf_coverage_role` and `vf_coordination_of_benefits` are now separate, which is correct. Legacy `vf_cob` is normalized and unset during save.
2. Date fields must always pass through one browser-safe display path. The critical fields are `vf_patient_dob`, `vf_subscriber_dob`, `vf_effective_date`, `vf_future_termination_date`, and `vf_verification_date`.
3. `waitingPeriodDetails` is stored as formatted text in `verification_profiles.waiting_periods`, not as structured JSON/table rows. It works, but it is more fragile than structured storage.
4. Frequency rows are configured from `verification_form_questions` and saved in `verification_coverage_codes`. They should not be duplicated as static `verification_profiles.frequency_*` fields for Template 3.
5. Custom questions save only if they use `custom_question_{id}` keys. Built-in questions save through their resolved `vf_*` key.
6. Numeric percent fields on `verification_profiles` are integer-normalized, so text like `n` becomes `null`. Coverage row percent fields are decimal fields in `verification_coverage_codes`.

## Recommended Next Hardening Step

Create a small automated verification check for Template 3:

1. Fill each Template 3 field with a test value.
2. Run the Template 3 draft save pipeline.
3. Reload using `mutateFormDataBeforeFill()`.
4. Assert every value is present in the expected `data.*`, `codeCoverageData.*`, or `waitingPeriodDetails.*` path.

This should be done before any more Template 3 UI polish.
